<?php


namespace Firesphere\CSPHeaders\Extensions;

use Firesphere\CSPHeaders\Models\CSPDomain;
use Firesphere\CSPHeaders\View\CSPBackend;
use LeKoala\DebugBar\DebugBar;
use PageController;
use ParagonIE\CSPBuilder\CSPBuilder;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataList;

/**
 * Class \Firesphere\CSPHeaders\Extensions\ControllerCSPExtension
 *
 * This extension is applied to the PageController, to avoid duplicates.
 * Any duplicates may be caused by extended classes. It should however, not affect the outcome
 *
 * @property PageController|ControllerCSPExtension $owner
 */
class ControllerCSPExtension extends Extension
{
    /**
     * @var array
     */
    protected static $inlineJS = [];

    /**
     * @var array
     */
    protected static $inlineCSS = [];

    /**
     * @param string $js
     */
    public static function addJS($js)
    {
        static::$inlineJS[] = $js;
    }

    /**
     * @param string $css
     */
    public static function addCSS($css)
    {
        static::$inlineCSS[] = $css;
    }

    /**
     * @return array
     */
    public static function getInlineJS()
    {
        return static::$inlineJS;
    }

    /**
     * @return array
     */
    public static function getInlineCSS()
    {
        return static::$inlineCSS;
    }

    /**
     * Add the needed headers from the database and config
     * @throws \Exception
     */
    public function onAfterInit()
    {
        if (Director::isLive() || static::checkCookie($this->owner->getRequest())) {
            $config = CSPBackend::config()->get('csp_config');
            $legacy = $config['legacy'] ?? true;
            /** @var CSPBuilder $policy */
            $policy = CSPBuilder::fromArray($config);

            $this->addCSP($policy);
            $this->addInline($policy);
            $policy->setReportUri($config['report-uri']);
            if (class_exists(DebugBar::class)) {
                $policy->nonce('script-src', 'phpdebugbar');
            }

            $policy->saveSnippet('tmp.conf', CSPBuilder::FORMAT_APACHE);

            $headers = $policy->getHeaderArray($legacy);
            foreach ($headers as $name => $header) {
                $this->owner->getResponse()->addHeader($name, $header);
            }
        }
    }

    /**
     * @param HTTPRequest $request
     * @return bool
     */
    public static function checkCookie($request)
    {
        if ($request->getVar('build-headers')) {
            Cookie::set('buildHeaders', $request->getVar('build-headers'));
        }

        return (Cookie::get('buildHeaders') === 'true');
    }

    /**
     * @param CSPBuilder $policy
     */
    public function addCSP($policy)
    {
        /** @var DataList|CSPDomain[] $cspDomains */
        $cspDomains = CSPDomain::get();

        foreach ($cspDomains as $domain) {
            $policy->addSource($domain->Source, $domain->Domain);
        }
    }

    /**
     * @param CSPBuilder $policy
     */
    public function addInline($policy)
    {
        $inline = static::$inlineJS;

        foreach ($inline as $item) {
            $policy->hash('script-src', $item);
        }
    }
}
