<?php


namespace Firesphere\CSPHeaders\Extensions;

use Exception;
use Firesphere\CSPHeaders\Models\CSPDomain;
use Firesphere\CSPHeaders\Models\SRI;
use Firesphere\CSPHeaders\View\CSPBackend;
use LeKoala\DebugBar\DebugBar;
use PageController;
use ParagonIE\ConstantTime\Base64;
use ParagonIE\CSPBuilder\CSPBuilder;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
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
     * Should we generate the policy headers or not
     * @var bool
     */
    protected $addPolicyHeaders;
    /**
     * @var string randomised sha512 nonce for enabling scripts if you don't want to use validating of the full script
     */
    protected $nonce;
    /**
     * @var array
     */
    protected $headTags = [];

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
     * @return null|string
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * Add the needed headers from the database and config
     * @throws Exception
     */
    public function onBeforeInit()
    {
        /** @var ContentController $owner */
        $owner = $this->owner;
        $this->addPolicyHeaders = Director::isLive() || static::checkCookie($owner->getRequest());
        /** @var Controller $owner */
        $owner = $this->owner;
        if ($this->addPolicyHeaders) {
            $this->SRIs = ArrayList::create(SRI::get()->toArray());
            if (!$this->getNonce() && CSPBackend::isUsesNonce()) {
                $this->nonce = Base64::encode(hash('sha512', uniqid('nonce', true) . time()));
            }
            $ymlConfig = CSPBackend::config()->get('csp_config');
            $config = Injector::inst()->convertServiceProperty($ymlConfig);
            $legacy = $config['legacy'] ?? true;

            $policy = CSPBuilder::fromArray($config);

            $this->addCSP($policy, $owner);
            $this->addInlineJSPolicy($policy, $config);
            $this->addInlineCSSPolicy($policy, $config);
            // When in dev, add the debugbar nonce, requires a change to the lib
            if (Director::isDev() && class_exists(DebugBar::class)) {
                $policy->nonce('script-src', 'debugbar');
            }

            $headers = $policy->getHeaderArray($legacy);
            $this->addResponseHeaders($headers, $owner);
        }
    }

    /**
     * @param CSPBuilder $policy
     * @param SiteTree|Controller $owner
     */
    protected function addCSP($policy, $owner): void
    {
        /** @var DataList|CSPDomain[] $cspDomains */
        $cspDomains = CSPDomain::get()->filterAny(['Pages.ID' => [null, $owner->ID]]);

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
            $policy->nonce('script-src', $this->getNonce());
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
            $policy->nonce('style-src', $this->getNonce());
        }

        $inline = static::$inlineCSS;
        foreach ($inline as $css) {
            $policy->hash('style-src', "\n{$css}\n");
        }
    }

    /**
     * @return bool
     */
    public function isAddPolicyHeaders(): bool
    {
        return $this->addPolicyHeaders ?? false;
    }

    /**
     * @param array $headers
     * @param Controller $owner
     */
    protected function addResponseHeaders(array $headers, Controller $owner): void
    {
        $response = $owner->getResponse();
        foreach ($headers as $name => $header) {
            if (!$response->getHeader($header)) {
                $response->addHeader($name, $header);
            }
        }
    }
}
