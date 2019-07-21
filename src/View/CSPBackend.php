<?php

namespace Firesphere\CSPHeaders\View;

use DOMDocument;
use DOMElement;
use Firesphere\CSPHeaders\Builders\CSSBuilder;
use Firesphere\CSPHeaders\Builders\JSBuilder;
use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use Firesphere\CSPHeaders\Traits\CSPBackendTrait;
use GuzzleHttp\Exception\GuzzleException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\ValidationException;
use SilverStripe\View\Requirements_Backend;

class CSPBackend extends Requirements_Backend
{
    use Configurable;
    use CSPBackendTrait;

    public const SHA256 = 'sha256';
    public const SHA384 = 'sha384';

    public function __construct()
    {
        $this->setCssBuilder(new CSSBuilder($this));
        $this->setJsBuilder(new JSBuilder($this));
    }

    /**
     * Specific method for JS insertion
     *
     * @param $js
     * @param null|string $uniquenessID
     */
    public function customScript($js, $uniquenessID = null): void
    {
        ControllerCSPExtension::addJS($js);

        parent::customScript($js, $uniquenessID);
    }

    /**
     * @param $css
     * @param null|string $uniquenessID
     */
    public function customCSS($css, $uniquenessID = null): void
    {
        ControllerCSPExtension::addCSS($css);

        parent::customCSS($css, $uniquenessID);
    }

    /**
     * Add the following custom HTML code to the `<head>` section of the page
     * @todo include given options in opening tag
     * @param string $html Custom HTML code
     * @param string|null $uniquenessID A unique ID that ensures a piece of code is only added once
     */
    public function insertHeadTags($html, $uniquenessID = null): void
    {
        $uniquenessID = $uniquenessID ?: uniqid('tag', false);
        $type = $this->getTagType($html);
        if ($type === 'javascript') {
            $doc = simplexml_load_string($html); // SimpleXML does it's job here, we see the outcome
            $option = [];
            foreach ($doc->attributes() as $key => $attribute) {
                $option[$key] = (string)$attribute; // Add each option as a string
            }
            static::$headJS[$uniquenessID] = [strip_tags($html) => $option];
            ControllerCSPExtension::addJS(strip_tags($html));
        } elseif ($type === 'css') {
            $doc = simplexml_load_string($html); // SimpleXML does it's job here, we see the outcome
            $option = [];
            foreach ($doc->attributes() as $key => $attribute) {
                $option[$key] = (string)$attribute; // Add each option as a string
            }
            static::$headCSS[$uniquenessID] = strip_tags($html);
            ControllerCSPExtension::addCSS(strip_tags($html));
        } else {
            $this->customHeadTags[$uniquenessID] = $html;
        }
    }

    /**
     * Determine the type of the head tag if it's js or css
     * @param string $html
     * @return string|null
     */
    public function getTagType($html): ?string
    {
        $html = trim($html);
        if (strpos($html, '<script') === 0) {
            return 'javascript';
        }
        if (strpos($html, '<style') === 0) {
            return 'css';
        }

        return null;
    }

    /**
     * Register the given JavaScript file as required.
     *
     * @param string $file Either relative to docroot or in the form "vendor/package:resource"
     * @param array $options List of options. Available options include:
     * - 'provides' : List of scripts files included in this file
     * - 'async' : Boolean value to set async attribute to script tag
     * - 'defer' : Boolean value to set defer attribute to script tag
     * - 'type' : Override script type= value.
     */
    public function javascript($file, $options = []): void
    {
        $file = ModuleResourceLoader::singleton()->resolvePath($file);

        // Get type
        $type = $options['type'] ?? $this->javascript[$file]['type'] ?? 'text/javascript';
        $fallback = $options['fallback'] ?? false;

        $this->javascript[$file] = [
            'async'    => $this->isAsync($file, $options),
            'defer'    => $this->isDefer($file, $options),
            'type'     => $type,
            'fallback' => $fallback
        ];

        // Record scripts included in this file
        if (isset($options['provides'])) {
            $this->providedJavascript[$file] = array_values($options['provides']);
        }
    }

    /**
     * Copy-paste of the original backend code. There is no way to override this in a more clean way
     *
     * Update the given HTML content with the appropriate include tags for the registered
     * requirements. Needs to receive a valid HTML/XHTML template in the $content parameter,
     * including a head and body tag.
     *
     * We need to override the whole method to adjust for SRI in javascript
     *
     * @param string $content HTML content that has already been parsed from the $templateFile
     *                             through {@link SSViewer}
     * @return string HTML content augmented with the requirements tags
     * @throws ValidationException
     * @throws GuzzleException
     */
    public function includeInHTML($content): string
    {
        if (func_num_args() > 1) {
            Deprecation::notice(
                '5.0',
                '$templateFile argument is deprecated. includeInHTML takes a sole $content parameter now.'
            );
            $content = func_get_arg(1);
        }

        // Skip if content isn't injectable, or there is nothing to inject
        $shouldContinue = $this->shouldContinue($content);

        if (!$shouldContinue) {
            return $content;
        }
        $requirements = '';
        $jsRequirements = '';

        // Combine files - updates $this->javascript and $this->css
        $this->processCombinedFiles();
        $jsRequirements = $this->getJSRequirements($jsRequirements);
        $requirements = $this->getCSSRequirements($requirements);

        $requirements = $this->createHeadTags($requirements);
        $content = $this->insertContent($content, $requirements, $jsRequirements);

        return $content;
    }

    /**
     * @param $content
     * @return bool
     */
    protected function shouldContinue($content): bool
    {
        $tagsAvailable = preg_match('#</head\b#', $content);
        $hasFiles = count($this->css) ||
            count($this->javascript) ||
            count($this->customCSS) ||
            count($this->customScript) ||
            count($this->customHeadTags);

        return $tagsAvailable && $hasFiles;
    }

    /**
     * @param string $jsRequirements
     * @return string
     * @throws ValidationException
     */
    protected function getJSRequirements(string $jsRequirements): string
    {
        // Script tags for js links
        foreach ($this->getJavascript() as $file => $attributes) {
            $path = $this->pathForFile($file);
            $jsRequirements = $this->getJsBuilder()->buildTags($file, $attributes, $jsRequirements, $path);
        }
        $jsRequirements = $this->getJsBuilder()->getCustomTags($jsRequirements);

        return $jsRequirements;
    }

    /**
     * @param string $requirements
     * @return string
     * @throws GuzzleException
     * @throws ValidationException
     */
    protected function getCSSRequirements(string $requirements): string
    {
        // CSS file links
        foreach ($this->getCSS() as $file => $attributes) {
            $path = $this->pathForFile($file);
            $requirements = $this->getCssBuilder()->buildTags($file, $attributes, $requirements, $path);
        }
        $requirements = $this->getCssBuilder()->getCustomTags($requirements);

        return $requirements;
    }

    /**
     * @param string $requirements
     * @return string
     */
    protected function createHeadTags(string $requirements): string
    {
        $requirements = $this->getCssBuilder()->getHeadTags($requirements);
        $requirements = $this->getJsBuilder()->getHeadTags($requirements);

        foreach ($this->getCustomHeadTags() as $customHeadTag) {
            $requirements .= "{$customHeadTag}\n";
        }

        return $requirements;
    }

    /**
     * @param $content
     * @param string $requirements
     * @param string $jsRequirements
     * @return string
     */
    protected function insertContent($content, string $requirements, string $jsRequirements): string
    {
        // Inject CSS into head
        $content = $this->insertTagsIntoHead($requirements, $content);

        // Inject scripts
        if ($this->getForceJSToBottom()) {
            $content = $this->insertScriptsAtBottom($jsRequirements, $content);
        } elseif ($this->getWriteJavascriptToBody()) {
            $content = $this->insertScriptsIntoBody($jsRequirements, $content);
        } else {
            $content = $this->insertTagsIntoHead($jsRequirements, $content);
        }

        return $content;
    }

    /**
     * @param $file
     * @param $options
     * @return bool
     */
    protected function isAsync($file, $options): bool
    {
        // make sure that async/defer is set if it is set once even if file is included multiple times
        return (
            (isset($options['async']) && isset($options['async']) === true)
            || (isset($this->javascript[$file]['async']) && $this->javascript[$file]['async'] === true)
        );
    }

    /**
     * @param $file
     * @param $options
     * @return bool
     */
    protected function isDefer($file, $options): bool
    {
        return (
            (isset($options['defer']) && isset($options['defer']) === true)
            || (isset($this->javascript[$file]['defer']) && $this->javascript[$file]['defer'] === true)
        );
    }
}
