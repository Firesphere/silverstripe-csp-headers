<?php

namespace Firesphere\CSPHeaders\Builders;

use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Control\Controller;
use SilverStripe\View\HTML;

class BaseBuilder
{

    /**
     * @param array $requirements
     * @param array $scripts
     * @param string $type
     */
    protected function getBaseHeadTags(array &$requirements, array $scripts, string $type = ''): void
    {
        foreach ($scripts as $script) {
            $content = array_keys($script)[0];
            $options = $script[$content] ?? [];
            self::getNonce($options);

            $content = "\n{$content}\n";
            // Wrap in CDATA if it's a script tag.
            if ($type === 'script') {
                $content = "//<![CDATA[{$content}//]]>";
            }

            $requirements[] = HTML::createTag(
                $type,
                $options,
                $content
            );
        }
    }

    /**
     * @param array $options
     * @return void
     */
    public static function getNonce(array &$options): void
    {
        if (CSPBackend::isUsesNonce() && Controller::has_curr()) {
            /** @var Controller|ControllerCSPExtension $ctrl */
            $ctrl = Controller::curr();
            if ($ctrl && $ctrl->hasMethod('getNonce')) {
                $options['nonce'] = $ctrl->getNonce();
            }
        }
    }

    /**
     * @param array $requirements
     * @param array $scripts
     * @param string $type
     * @return void
     */
    protected function getBaseCustomTags(array &$requirements = [], array $scripts = [], string $type = ''): void
    {
        // Literal custom CSS content
        foreach ($scripts as $script) {
            $srcType = $type === 'style' ? 'text/css' : 'application/javascript';
            $options = ['type' => $srcType];
            // Use nonces for inlines if requested
            self::getNonce($options);

            $requirements[] = HTML::createTag(
                $type,
                $options,
                "\n{$script}\n"
            );
        }
    }
}
