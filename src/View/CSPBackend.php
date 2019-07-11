<?php

namespace Firesphere\CSPHeaders\View;

use Exception;
use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use Firesphere\CSPHeaders\Models\SRI;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Security;
use SilverStripe\View\HTML;
use SilverStripe\View\Requirements;
use SilverStripe\View\Requirements_Backend;

class CSPBackend extends Requirements_Backend
{
    use Configurable;

    protected static $csp_config;

    /**
     * Specific method for JS insertion
     *
     * @param $js
     * @param $identifier
     * @param $options
     */
    public function insertJSTags($js, $identifier = null, $options = [])
    {
        $options = array_merge(['type' => 'application/javascript'], $options);
        $scriptTag = HTML::createTag(
            'script',
            $options,
            $js
        );

        ControllerCSPExtension::addJS($js);

        Requirements::insertHeadTags($scriptTag, $identifier);
    }

    public function insertCSSTags($css, $identifier, $options)
    {
        $options = array_merge(['type' => 'text/css'], $options);
        $scriptTag = HTML::createTag(
            'style',
            $options,
            $css
        );

        ControllerCSPExtension::addCSS($css);

        Requirements::insertHeadTags($scriptTag, $identifier);
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
    public function javascript($file, $options = array())
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


        $this->javascript[$file] = array(
            'async'    => $async,
            'defer'    => $defer,
            'type'     => $type,
            'fallback' => $fallback
        );

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
    public function includeInHTML($content)
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
        $hasFiles = $this->css || $this->javascript || $this->customCSS || $this->customScript || $this->customHeadTags;
        if (!$tagsAvailable || !$hasFiles) {
            return $content;
        }
        $requirements = '';
        $jsRequirements = '';

        // Combine files - updates $this->javascript and $this->css
        $this->processCombinedFiles();

        // Script tags for js links
        foreach ($this->getJavascript() as $file => $attributes) {
            $jsRequirements = $this->buildAttributes($attributes, $file, $jsRequirements);
        }

        // Add all inline JavaScript *after* including external files they might rely on
        foreach ($this->getCustomScripts() as $script) {
            $jsRequirements .= HTML::createTag(
                'script',
                ['type' => 'application/javascript'],
                "//<![CDATA[\n{$script}\n//]]>"
            );
            $jsRequirements .= "\n";
        }

        // CSS file links
        foreach ($this->getCSS() as $file => $params) {
            $htmlAttributes = [
                'rel'  => 'stylesheet',
                'type' => 'text/css',
                'href' => $this->pathForFile($file),
            ];
            if (!empty($params['media'])) {
                $htmlAttributes['media'] = $params['media'];
            }

            $requirements .= HTML::createTag('link', $htmlAttributes);
            $requirements .= "\n";
        }

        // Literal custom CSS content
        foreach ($this->getCustomCSS() as $css) {
            $requirements .= HTML::createTag('style', ['type' => 'text/css'], "\n{$css}\n");
            $requirements .= "\n";
        }

        foreach ($this->getCustomHeadTags() as $customHeadTag) {
            $requirements .= "{$customHeadTag}\n";
        }

        // Inject CSS  into body
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
     * @param $attributes
     * @param $file
     * @param $jsRequirements
     * @return string
     * @throws GuzzleException
     * @throws ValidationException
     */
    protected function buildAttributes($attributes, $file, $jsRequirements)
    {
        // Build html attributes
        $htmlAttributes = [
            'type' => $attributes['type'] ?? 'application/javascript',
            'src'  => $this->pathForFile($file),
        ];
        if (!empty($attributes['async'])) {
            $htmlAttributes['async'] = 'async';
        }
        if (!empty($attributes['defer'])) {
            $htmlAttributes['defer'] = 'defer';
        }

        $htmlAttributes = $this->buildSRI($file, $htmlAttributes, $attributes);

        $jsRequirements .= HTML::createTag('script', $htmlAttributes);
        $jsRequirements .= "\n";

        return $jsRequirements;
    }

    /**
     * @param $file
     * @param array $htmlAttributes
     * @param $attributes
     * @return array
     * @throws GuzzleException
     * @throws ValidationException
     * @throws Exception
     */
    protected function buildSRI($file, array $htmlAttributes, $attributes)
    {
        $sri = SRI::get()->filter(['File' => $file])->first();
        // Create on first time it's run, or if it's been deleted because the file has changed, known to the admin
        if (
            !$sri ||
            (
                Controller::curr()->getRequest()->getVar('updatesri') &&
                Security::getCurrentUser() &&
                Security::getCurrentUser()->inGroup('administrators')
            )
        ) {
            // Since this is the CSP Backend, an SRI for external files is automatically created
            /** @var Client $client */
            $client = new Client();
            $location = $file;

            if (!Director::is_site_url($file)) {
                $result = $client->request('GET', $location);
                $body = $result->getBody()->getContents();
            } else {
                $body = file_get_contents(Director::baseFolder() . '/' . $location);
            }

            if ($body === '') {
                throw new \RuntimeException('ERROR no file contents given');
            }
            if (!$sri) {
                $sri = SRI::create();
            }
            $sri->update([
                'File' => $file,
                'SRI'  => base64_encode(hash('sha384', $body, true))

            ]);

            $sri->write();
        }

        // Don't write integrity in dev, it's breaking build scripts
        if (($sri && Director::isLive()) || ControllerCSPExtension::checkCookie(Controller::curr()->getRequest())) {
            $htmlAttributes['integrity'] = 'sha384-' . $sri->SRI;
            if (!Director::is_site_url($file)) {
                $htmlAttributes['crossorigin'] = 'anonymous';
            }

            if (isset($attributes['fallback']) && $attributes['fallback'] !== false) {
                $htmlAttributes['data-sri-fallback'] = $attributes['fallback'];
            }
        }

        return $htmlAttributes;
    }
}
