<?php

namespace Firesphere\CSPHeaders\View;

use Firesphere\CSPHeaders\Builders\CSSBuilder;
use Firesphere\CSPHeaders\Builders\JSBuilder;
use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use GuzzleHttp\Exception\GuzzleException;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\ValidationException;
use SilverStripe\View\HTML;
use SilverStripe\View\Requirements_Backend;

class CSPBackend extends Requirements_Backend
{
    use Configurable;

    public const SHA256 = 'sha256';
    public const SHA384 = 'sha384';
    /**
     * @var bool
     */
    protected static $jsSRI;
    /**
     * CSS defaults to false.
     * It's causing a lot of trouble with CDN's usually
     * @var bool
     */
    protected static $cssSRI;
    /**
     * JS to be inserted in to the head
     * @var array
     */
    protected static $headJS = [];
    /**
     * CSS to be inserted in to the head
     * @var array
     */
    protected static $headCSS = [];
    /**
     * @var bool
     */
    protected static $usesNonce = false;
    /**
     * @var CSSBuilder
     */
    protected $cssBuilder;
    /**
     * @var JSBuilder
     */
    protected $jsBuilder;

    public function __construct()
    {
        $this->cssBuilder = new CSSBuilder($this);
        $this->jsBuilder = new JSBuilder($this);
    }

    /**
     * @return bool
     */
    public static function isJsSRI(): bool
    {
        return static::config()->get('jsSRI') || self::$jsSRI;
    }

    /**
     * @param bool $jsSRI
     */
    public static function setJsSRI(bool $jsSRI): void
    {
        self::$jsSRI = $jsSRI;
    }

    /**
     * @return bool
     */
    public static function isCssSRI(): bool
    {
        return static::config()->get('cssSRI') || self::$cssSRI;
    }

    /**
     * @param bool $cssSRI
     */
    public static function setCssSRI(bool $cssSRI): void
    {
        self::$cssSRI = $cssSRI;
    }

    /**
     * Specific method for JS insertion
     *
     * @param $js
     * @param null|string $uniquenessID
     */
    public function customScript($js, $uniquenessID = null)
    {
        ControllerCSPExtension::addJS($js);

        parent::customScript($js, $uniquenessID);
    }

    /**
     * @param $css
     * @param null|string $uniquenessID
     */
    public function customCSS($css, $uniquenessID = null)
    {
        ControllerCSPExtension::addCSS($css);

        parent::customCSS($css, $uniquenessID);
    }

    /**
     * Add the following custom HTML code to the `<head>` section of the page
     *
     * @param string $html Custom HTML code
     * @param string|null $uniquenessID A unique ID that ensures a piece of code is only added once
     */
    public function insertHeadTags($html, $uniquenessID = null)
    {
        $uniquenessID = $uniquenessID ?: uniqid('tag', false);
        $type = $this->getTagType($html);
        if ($type === 'javascript') {
            static::$headJS[$uniquenessID] = strip_tags($html);
            ControllerCSPExtension::addJS(strip_tags($html));
        } elseif ($type === 'css') {
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
    public function getTagType($html)
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
    public function javascript($file, $options = [])
    {
        $file = ModuleResourceLoader::singleton()->resolvePath($file);

        // Get type
        $type = $options['type'] ?? $this->javascript[$file]['type'] ?? null;

        // make sure that async/defer is set if it is set once even if file is included multiple times
        $async = (
            (isset($options['async']) && isset($options['async']) === true)
            || (isset($this->javascript[$file]['async']) && $this->javascript[$file]['async'] === true)
        );
        $defer = (
            (isset($options['defer']) && isset($options['defer']) === true)
            || (isset($this->javascript[$file]['defer']) && $this->javascript[$file]['defer'] === true)
        );

        $fallback = $options['fallback'] ?? false;

        $this->javascript[$file] = [
            'async'    => $async,
            'defer'    => $defer,
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
        $tagsAvailable = preg_match('#</head\b#', $content);
        $hasFiles = count($this->css) ||
            count($this->javascript) ||
            count($this->customCSS) ||
            count($this->customScript) ||
            count($this->customHeadTags);

        if (!$tagsAvailable || !$hasFiles) {
            return $content;
        }
        $requirements = '';
        $jsRequirements = '';

        // Combine files - updates $this->javascript and $this->css
        $this->processCombinedFiles();

        // Script tags for js links
        foreach ($this->getJavascript() as $file => $attributes) {
            $path = $this->pathForFile($file);
            $jsRequirements = $this->jsBuilder->buildJSTag($attributes, $file, $jsRequirements, $path);
        }

        // CSS file links
        foreach ($this->getCSS() as $file => $params) {
            $path = $this->pathForFile($file);
            $requirements = $this->cssBuilder->buildCSSTags($file, $params, $requirements, $path);
        }

        $requirements = $this->createHeadTags($requirements);

        // Inject CSS into body
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
     * @param string $requirements
     * @return string
     */
    protected function createHeadTags(string $requirements): string
    {
        foreach (static::$headCSS as $css) {
            $options = ['type' => 'text/css'];
            if (static::isUsesNonce()) {
                $options['nonce'] = Controller::curr()->getNonce();
            }
            $requirements .= HTML::createTag(
                'style',
                $options,
                "\n{$css}\n"
            );
            $requirements .= "\n";
        }
        foreach (static::$headJS as $script) {
            $options = ['type' => 'application/javascript'];
            if (static::isUsesNonce()) {
                $options['nonce'] = Controller::curr()->getNonce();
            }
            $requirements .= HTML::createTag(
                'script',
                $options,
                "//<![CDATA[\n{$script}\n//]]>"
            );
            $requirements .= "\n";
        }

        foreach ($this->getCustomHeadTags() as $customHeadTag) {
            $requirements .= "{$customHeadTag}\n";
        }

        return $requirements;
    }

    /**
     * @return bool
     */
    public static function isUsesNonce(): bool
    {
        return static::config()->get('useNonce') || self::$usesNonce;
    }

    /**
     * @param bool static::isUseNonce()
     */
    public static function setUsesNonce(bool $usesNonce): void
    {
        self::$usesNonce = $usesNonce;
    }
}
