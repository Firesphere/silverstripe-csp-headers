<?php

namespace Firesphere\CSPHeaders\View;

use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\View\HTML;
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

        parent::insertHeadTags($scriptTag, $identifier);
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

        parent::insertHeadTags($scriptTag, $identifier);
    }
}
