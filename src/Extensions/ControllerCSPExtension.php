<?php


namespace Firesphere\CSPHeaders\Extensions;

use Exception;
use Firesphere\CSPHeaders\Models\CSPDomain;
use Firesphere\CSPHeaders\View\CSPBackend;
use ParagonIE\ConstantTime\Base64;
use ParagonIE\CSPBuilder\CSPBuilder;
use phpDocumentor\Reflection\Types\Boolean;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;
use function hash;

/**
 * Class \Firesphere\CSPHeaders\Extensions\ControllerCSPExtension
 *
 * This extension is applied to the PageController, to avoid duplicates.
 * Any duplicates may be caused by extended classes. It should however, not affect the outcome
 *
 * @property Controller|ControllerCSPExtension $owner
 */
class ControllerCSPExtension extends Extension
{
    public static $isTesting = false;
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
     * Should permission policies be added
     * @var bool
     */
    protected $addPermissionHeaders;
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
     * Add the needed headers from the database and config
     * @throws Exception
     */
    public function onBeforeInit()
    {
        if (self::$isTesting || !DB::is_active() || !ClassInfo::hasTable('Member') || Director::is_cli()) {
            return;
        }
        $config = CSPBackend::config();
        /** @var Controller $owner */
        $owner = $this->owner;
        $cspConfig = $config->get('csp_config');
        $permissionConfig = $config->get('permissions_config');
        $this->addPolicyHeaders = ($cspConfig['enabled'] ?? false) || static::checkCookie($owner->getRequest());
        $this->addPermissionHeaders = $permissionConfig['enabled'] ?? false;
        $this->addCSPHeaders($cspConfig, $owner);
        /** @var Controller $owner */
        // Policy-headers
        if ($this->addPolicyHeaders) {
            $this->addCSPHeaders($cspConfig, $owner);
        }
        // Permission-policy
        if ($this->addPermissionHeaders) {
            $this->addPermissionsHeaders($permissionConfig, $owner);
        }
        // Referrer-Policy
        if ($referrerPolicy = $config->get('referrer')) {
            $this->addResponseHeaders(['Referrer-Policy' => $referrerPolicy], $owner);
        }
        // X-Frame-Options
        if ($frameOptions = $config->get('frame-options')) {
            $this->addResponseHeaders(['X-Frame-Options' => $frameOptions], $owner);
        }
        // X-Content-Type-Options
        if ($ContentTypeOptions = $config->get('content-type-options')) {
            $this->addResponseHeaders(['X-Content-Type-Options' => $ContentTypeOptions], $owner);
        }
        // Strict-Transport-Security
        $hsts = $config->get('HSTS');
        if ($hsts && $hsts['enabled']) {
            $header = $hsts['max-age'] ? sprintf('max-age=%s; ', $hsts['max-age']) : '0';
            $header .= $hsts['include_subdomains'] ? 'includeSubDomains' : '';
            $this->addResponseHeaders(['Strict-Transport-Security' => trim($header)], $owner);
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
     * @param Controller $owner
     */
    protected function addCSP($policy, $owner): void
    {
        /** @var DataList|CSPDomain[] $cspDomains */
        $cspDomains = CSPDomain::get();
        if (class_exists('\Page')) {
            $cspDomains = $cspDomains->filterAny(['Pages.ID' => [null, $owner->ID]]);
        }
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

    /**
     * @return bool
     */
    public function isAddPolicyHeaders(): bool
    {
        return $this->addPolicyHeaders ?? false;
    }

    /**
     * @param mixed $ymlConfig
     * @param Controller $owner
     * @return void
     * @throws Exception
     */
    private function addCSPHeaders(mixed $ymlConfig, Controller $owner): void
    {
        $config = Injector::inst()->convertServiceProperty($ymlConfig);
        $legacy = $config['legacy'] ?? true;
        $unsafeCSSInline = $config['style-src']['unsafe-inline'];
        $unsafeJsInline = $config['script-src']['unsafe-inline'];
        if (class_exists('\Page') && $owner && $owner->dataRecord) {
            $config['style-src']['unsafe-inline'] = $unsafeCSSInline || $owner->dataRecord->AllowCSSInline;
            $config['script-src']['unsafe-inline'] = $unsafeJsInline || $owner->dataRecord->AllowJSInline;
        }
        $policy = CSPBuilder::fromArray($config);

        $this->addCSP($policy, $owner);
        $this->addInlineJSPolicy($policy, $config);
        $this->addInlineCSSPolicy($policy, $config);
        // When in dev, add the debugbar nonce, requires a change to the lib
        if (Director::isDev() && class_exists('LeKoala\DebugBar\DebugBar')) {
            $bar = \LeKoala\DebugBar\DebugBar::getDebugBar();

            if ($bar) {
                $bar->getJavascriptRenderer()->setCspNonce('debugbar');
                $policy->nonce('script-src', 'debugbar');
            }
        }

        $headers = $policy->getHeaderArray($legacy);
        $this->addResponseHeaders($headers, $owner);
    }

    /**
     * Add the Permissions-Policy header
     * @param array $ymlConfig
     * @param Controller $controller
     * @return void
     */
    private function addPermissionsHeaders(mixed $ymlConfig, Controller $controller)
    {
        $config = Injector::inst()->convertServiceProperty($ymlConfig);
        $policies = [];
        foreach ($config as $key => $value) {
            switch ($key) {
                case 'accelerator':
                case 'accelerator_policy':
                    $policy ='accelerator';
                    break;
                case 'ambient-light-sensor':
                case 'ambient_light_sensor':
                case 'ambientLightSensor':
                    $policy ='ambient-light-sensor';
                    break;
                case 'autoplay':
                case 'autoPlay':
                    $policy = 'autoplay';
                    break;
                case 'battery':
                    $policy = 'battery';
                    break;
                case 'camera':
                    $policy = 'camera';
                    break;
                case 'display-capture':
                case 'display_capture':
                case 'displayCapture':
                    $policy = 'display-capture';
                    break;
                case 'encrypted-media':
                case 'encrypted_media':
                case 'encryptedMedia':
                    $policy = 'encrypted-media';
                    break;
                case 'fullscreen':
                case 'fullScreen':
                    $policy = 'fullscreen';
                    break;
                case 'geolocation':
                case 'geoLocation':
                    $policy = 'geolocation';
                    break;
                case 'interest-cohort':
                case 'interest_cohort':
                case 'interestCohort':
                    $policy = 'interest-cohort';
                    break;
                case 'microphone':
                    $policy = 'microphone';
                    break;
                default:
                    $policy = false;
            }
            if ($policy) {
                $policies[] = $this->ymlToPolicy($policy, $value);
            }
        }
        $headerAsArray = ['Permissions-Policy' => implode(', ', $policies)];

        $this->addResponseHeaders($headerAsArray, $controller);
    }

    private function ymlToPolicy($key, $yml)
    {
        $value = [];
        if (!empty($yml['self'])) {
            $value[] = "'self'";
        }
        if (!empty($yml['allow']) && !in_array('none', $yml['allow'])) {
            $value[] = implode(', ', $yml['allow']);
        }
        // If it's none, then anything else we did is useless
        if (in_array('none', $yml['allow'])) {
            $value = ["'none'"];
        }

        return sprintf('%s=(%s)', $key, implode(' ', $value));
    }
}
