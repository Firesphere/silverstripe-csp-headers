<?php

namespace Firesphere\CSPHeaders\View;

use Firesphere\CSPHeaders\Builders\CSSBuilder;
use Firesphere\CSPHeaders\Builders\JSBuilder;
use Firesphere\CSPHeaders\Middlewares\CSPMiddleware;
use Firesphere\CSPHeaders\Traits\CSPBackendTrait;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
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
        CSPMiddleware::addJS($js);

        parent::customScript($js, $uniquenessID);
    }

    /**
     * @param $css
     * @param null|string $uniquenessID
     */
    public function customCSS($css, $uniquenessID = null): void
    {
        CSPMiddleware::addCSS($css);

        parent::customCSS($css, $uniquenessID);
    }

    /**
     * Add the following custom HTML code to the `<head>` section of the page
     * @param string $html Custom HTML code
     * @param string|null $uniquenessID A unique ID that ensures a piece of code is only added once
     */
    public function insertHeadTags($html, $uniquenessID = null): void
    {
        $uniquenessID = $uniquenessID ?: hash(static::SHA256, $html);
        $type = $this->getTagType($html);
        if ($type === 'javascript') {
            $options = $this->getOptions($html);
            static::$headJS[$uniquenessID] = [strip_tags($html) => $options];
            CSPMiddleware::addJS(strip_tags($html));
        } elseif ($type === 'css') {
            $options = $this->getOptions($html); // SimpleXML does its job here, we see the outcome
            static::$headCSS[$uniquenessID] = [strip_tags($html) => $options];
            CSPMiddleware::addCSS(strip_tags($html));
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
     * @param $html
     * @return array
     */
    protected function getOptions($html): array
    {
        $doc = simplexml_load_string($html); // SimpleXML does it's job here, we see the outcome
        $option = [];
        foreach ($doc->attributes() as $key => $attribute) {
            $option[$key] = (string)$attribute; // Add each option as a string
        }

        return $option;
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
        $fallback = $options['fallback'] ?? $this->javascript[$file]['fallback'] ?? false;

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
     * @param $file
     * @param $options
     * @return bool
     */
    protected function isAsync($file, $options): bool
    {
        // make sure that async/defer is set if it is set once even if file is included multiple times
        return (
            (isset($options['async']) && $options['async'] === true)
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
            (isset($options['defer']) && $options['defer'] === true)
            || (isset($this->javascript[$file]['defer']) && $this->javascript[$file]['defer'] === true)
        );
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
        if (!$this->shouldContinue($content)) {
            return $content;
        }

        $requirements = [];
        $jsRequirements = [];

        // Combine files - updates $this->javascript and $this->css
        $this->processCombinedFiles();
        $jsRequirements = $this->getJSRequirements($jsRequirements);
        $requirements = $this->getCSSRequirements($requirements);

        $requirements = $this->getHeadTags($requirements);

        return $this->insertContent($content, $requirements, $jsRequirements);
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
     * @param array $jsRequirements
     * @return string
     * @throws ValidationException
     */
    protected function getJSRequirements(array $jsRequirements): string
    {
        // Script tags for js links
        foreach ($this->getJavascript() as $file => $attributes) {
            $path = $this->pathForFile($file);
            $jsRequirements = $this->getJsBuilder()->buildTags($file, $attributes, $jsRequirements, $path);
        }

        return implode(PHP_EOL, $this->getJsBuilder()->getCustomTags($jsRequirements));
    }

    /**
     * @param array $requirements
     * @return array
     * @throws ValidationException
     */
    protected function getCSSRequirements(array $requirements): array
    {
        // CSS file links
        foreach ($this->getCSS() as $file => $attributes) {
            $path = $this->pathForFile($file);
            $requirements = $this->getCssBuilder()->buildTags($file, $attributes, $requirements, $path);
        }

        return $this->getCssBuilder()->getCustomTags($requirements);
    }

    /**
     * @param array $requirements
     * @return string
     */
    protected function getHeadTags(array $requirements = []): string
    {
        $this->getCssBuilder()->getHeadTags($requirements);
        $this->getJsBuilder()->getHeadTags($requirements);

        foreach ($this->getCustomHeadTags() as $customHeadTag) {
            $requirements[] = $customHeadTag;
        }

        return implode(PHP_EOL, $requirements);
    }

    /**
     * @param $content
     * @param string $requirements
     * @param string $jsRequirements
     * @return string
     */
    protected function insertContent($content, string $requirements, string $jsRequirements): string
    {
        $requirements .= PHP_EOL;
        $jsRequirements .= PHP_EOL;
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
}
