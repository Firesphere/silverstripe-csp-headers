<?php


namespace Firesphere\CSPHeaders\Extensions;

use Exception;
use Firesphere\CSPHeaders\Models\CSPDomain;
use Firesphere\CSPHeaders\View\CSPBackend;
use LeKoala\DebugBar\DebugBar;
use PageController;
use ParagonIE\ConstantTime\Base64;
use ParagonIE\CSPBuilder\CSPBuilder;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataList;
use function hash;

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
    protected $headTags = [];
    /**
     * @var string randomised sha512 nonce for enabling scripts if you don't want to use validating of the full script
     */
    protected $nonce;
    /**
     * Base CSP configuration
     * @var array
     */
    protected static $csp_config;
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
     * @throws Exception
     */
    public function onAfterInit()
    {
        /** @var Controller $owner */
        $owner = $this->owner;
        if (Director::isLive() || static::checkCookie($owner->getRequest())) {
            $config = CSPBackend::config()->get('csp_config');
            $legacy = $config['legacy'] ?? true;
            /** @var CSPBuilder $policy */
            $policy = CSPBuilder::fromArray($config);
            if (!$this->nonce && CSPBackend::config()->get('useNonce')) {
                $this->nonce = Base64::encode(hash('sha512', uniqid('nonce', true) . time()));
            }

            $this->addCSP($policy);
            $this->addInlineJSPolicy($policy, $config);
            $this->addInlineCSSPolicy($policy, $config);
            // When in dev, add the debugbar nonce
            if (Director::isDev() && class_exists(DebugBar::class)) {
                $policy->nonce('script-src', 'debugbar');
            }
            $policy->setReportUri($config['report-uri']);

            $headers = $policy->getHeaderArray($legacy);
            foreach ($headers as $name => $header) {
                $owner->getResponse()->addHeader($name, $header);
            }
        }
    }

    /**
     * @param HTTPRequest $request
     * @return bool
     */
    public static function checkCookie($request): bool
    {
        if ($request->getVar('build-headers')) {
            Cookie::set('buildHeaders', $request->getVar('build-headers'));
        }

        return (Cookie::get('buildHeaders') === 'true');
    }

    /**
     * @param CSPBuilder $policy
     */
    protected function addCSP($policy): void
    {
        /** @var DataList|CSPDomain[] $cspDomains */
        $cspDomains = CSPDomain::get()
            ->filterAny([
                'Pages.ID' => [null, $this->owner->ID]
            ]);

        foreach ($cspDomains as $domain) {
            $policy->addSource($domain->Source, $domain->Domain);
        }
    }

    /**
     * @param CSPBuilder $policy
     * @param array $config
     * @throws Exception
     */
    protected function addInlineJSPolicy($policy, $config): void
    {
        if ($config['script-src']['unsafe-inline']) {
            return;
        }

        if (CSPBackend::config()->get('useNonce')) {
            $policy->nonce('script-src', $this->nonce);
        }

        $inline = static::$inlineJS;
        foreach ($inline as $item) {
            $policy->hash('script-src', "//<![CDATA[\n{$item}\n//]]>");
        }
    }

    /**
     * @param CSPBuilder $policy
     * @param array $config
     * @throws Exception
     */
    protected function addInlineCSSPolicy($policy, $config): void
    {
        if ($config['style-src']['unsafe-inline']) {
            return;
        }

        if (CSPBackend::config()->get('useNonce')) {
            $policy->nonce('style-src', $this->nonce);
        }

        $inline = static::$inlineCSS;
        foreach ($inline as $css) {
            $policy->hash('style-src', "\n{$css}\n");
        }
    }

    /**
     * @return string
     */
    public function getNonce(): string
    {
        return $this->nonce;
    }
}
