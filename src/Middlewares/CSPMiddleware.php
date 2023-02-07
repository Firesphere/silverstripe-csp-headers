<?php

namespace Firesphere\CSPHeaders\Middlewares;

use Exception;
use Firesphere\CSPHeaders\Models\CSPDomain;
use Firesphere\CSPHeaders\View\CSPBackend;
use ParagonIE\ConstantTime\Base64;
use ParagonIE\CSPBuilder\CSPBuilder;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;

class CSPMiddleware implements HTTPMiddleware
{

    protected $nonce;
    /**
     * @var bool
     */
    protected $addPolicyHeaders;

    /**
     * @var array
     */
    protected static $inlineJS = [];
    /**
     * @var array
     */
    protected static $inlineCSS = [];
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
     * @return bool
     */
    public function isAddPolicyHeaders(): bool
    {
        return $this->addPolicyHeaders ?? false;
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


    public function process(HTTPRequest $request, callable $delegate)
    {
        if (!DB::is_active() || !ClassInfo::hasTable('Member') || Director::is_cli()) {
            return;
        }
        /** @var HTTPResponse $response */
        $response = $delegate($request);

        $ymlConfig = CSPBackend::config()->get('csp_config');
        $this->addPolicyHeaders = ($ymlConfig['enabled'] ?? false) || static::checkCookie($request);

        if ($this->addPolicyHeaders) {
            $cache = Injector::inst()->get(CacheInterface::class . '.cspheaders');
            if (!$cache->has('headers')) {
                $config = Injector::inst()->convertServiceProperty($ymlConfig);
                $legacy = $config['legacy'] ?? true;

                $policy = CSPBuilder::fromArray($config);
                $this->addCSP($policy);
                $this->addInlineJSPolicy($policy, $config);
                $this->addInlineCSSPolicy($policy, $config);

                // When in dev, add the debugbar nonce, requires a change to the lib
                if (Director::isDev() && class_exists('LeKoala\DebugBar\DebugBar')) {
                    $policy->nonce('script-src', 'debugbar');
                    $bar = \LeKoala\DebugBar\DebugBar::getDebugBar();

                    if ($bar) {
                        $bar->getJavascriptRenderer()->setCspNonce('debugbar');
                    }
                }
                $headers = $policy->getHeaderArray($legacy);

                $cache->set('headers', $headers);
            }

            $this->addResponseHeaders($cache->get('headers'), $response);
        }


        return $response;
    }

    /**
     * @return null|string
     */
    public function getNonce()
    {
        if (!$this->nonce) {
            $this->nonce = Base64::encode(hash('sha512', uniqid('nonce', false)));
        }

        return $this->nonce;
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

        $inline = CSPBackend::getHeadJS();
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
     * @param CSPBuilder $policy
     * @param SiteTree|Controller $owner
     */
    protected function addCSP($policy, $owner = null): void
    {
        $filter = $owner ? ['Pages.ID' => [null, $owner->ID]] : [];
        /** @var DataList|CSPDomain[] $cspDomains */
        $cspDomains = CSPDomain::get()->filterAny($filter);

        foreach ($cspDomains as $domain) {
            $policy->addSource($domain->Source, $domain->Domain);
        }
    }


    /**
     * @param array $headers
     * @param HTTPResponse $response
     */
    protected function addResponseHeaders(array $headers, $response): void
    {
        foreach ($headers as $name => $header) {
            if (!$response->getHeader($header)) {
                $response->addHeader($name, $header);
            }
        }
    }
}