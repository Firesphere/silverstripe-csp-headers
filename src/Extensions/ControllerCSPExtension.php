<?php


namespace Firesphere\CSPHeaders\Extensions;

use Firesphere\CSPHeaders\Models\CSPDomain;
use Firesphere\CSPHeaders\View\CSPBackend;
use Phpcsp\Security\ContentSecurityPolicyHeaderBuilder;
use Phpcsp\Security\InvalidValueException;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class \Firesphere\CSPHeaders\Extensions\ControllerCSPExtension
 *
 * This extension is applied to the PageController, to avoid duplicates.
 * Any duplicates may be caused by extended classes. It should however, not affect the outcome
 *
 * @property ControllerCSPExtension $owner
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
     * @var array
     */
    protected $allowedDirectivesMap = [
        'default' => ContentSecurityPolicyHeaderBuilder::DIRECTIVE_DEFAULT_SRC,
        'font'    => ContentSecurityPolicyHeaderBuilder::DIRECTIVE_FONT_SRC,
        'form'    => ContentSecurityPolicyHeaderBuilder::DIRECTIVE_FORM_ACTION,
        'frame'   => ContentSecurityPolicyHeaderBuilder::DIRECTIVE_FRAME_SRC,
        'img'     => ContentSecurityPolicyHeaderBuilder::DIRECTIVE_IMG_SRC,
        'media'   => ContentSecurityPolicyHeaderBuilder::DIRECTIVE_MEDIA_SRC,
        'script'  => ContentSecurityPolicyHeaderBuilder::DIRECTIVE_SCRIPT_SRC,
        'style'   => ContentSecurityPolicyHeaderBuilder::DIRECTIVE_STYLE_SRC,
    ];

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
     * @throws \Phpcsp\Security\InvalidDirectiveException
     * @throws \Phpcsp\Security\InvalidValueException
     */
    public function onAfterInit()
    {
        if (Director::isLive() || $this->checkCookie($this->owner->getRequest())) {
            $policy = $this->setDefaultPolicies();
            $this->setConfigPolicies($policy);
            $this->setSiteConfigPolicies($policy);
            $this->setReportPolicy();

            $headers = $policy->getHeaders(CSPBackend::config()->get('legacy_headers'));
            foreach ($headers as $header) {
                $this->owner->getResponse()->addHeader($header['name'], $header['value']);
            }
        }
    }

    /**
     * @param HTTPRequest $request
     * @return bool
     * default-src 'self' http://192.168.33.5/ piwik.casa-laguna.net; font-src 'self' http://192.168.33.5/ netdna.bootstrapcdn.com fonts.gstatic.com; form-action 'self' http://192.168.33.5/; frame-src 'self' http://192.168.33.5/ *.vimeocdn.com player.vimeo.com www.youtube.com www.youtube-nocookie.com; img-src 'self' http://192.168.33.5/ secure.gravatar.com a.slack-edge.com avatars.slack-edge.com emoji.slack-edge.com *.imgur.com imgur.com *.wp.com piwik.casa-laguna.net data: i.ytimg.com packagist.org www.silverstripe.org www.silverstripe.com; media-src 'self' http://192.168.33.5/ *.vimeocdn.com player.vimeo.com www.youtube.com www.youtube-nocookie.com; script-src 'self' http://192.168.33.5/ code.jquery.com piwik.casa-laguna.net 'sha256-eMFqux9gzJQ++3HtmRPlbbsshtbkqI6ieVhYTWbaV1k='; style-src 'self' http://192.168.33.5/ 'unsafe-inline'; base-uri 'self' http://192.168.33.5/ https:; reflected-xss block; report-uri https://casalaguna.report-uri.com/r/d/csp/wizard;
     */
    protected function checkCookie($request)
    {
        if ($request->getVar('build-headers')) {
            Cookie::set('buildHeaders', $request->getVar('build-headers'));
        }

        return (Cookie::get('buildHeaders') === 'true');
    }

    /**
     * Setup the default allowed URI's
     * @return ContentSecurityPolicyHeaderBuilder
     */
    protected function setDefaultPolicies()
    {
        $policy = Injector::inst()->get(ContentSecurityPolicyHeaderBuilder::class);
        foreach ($this->allowedDirectivesMap as $key => $directive) {
            // Always allow self and the local domain
            $policy->addSourceExpression($directive, 'self');
            $policy->addSourceExpression($directive, Director::absoluteBaseURL());
        }

        $policy->addSourceExpression(ContentSecurityPolicyHeaderBuilder::DIRECTIVE_BASE_URI, 'self');
        $policy->addSourceExpression(
            ContentSecurityPolicyHeaderBuilder::DIRECTIVE_BASE_URI,
            Director::absoluteBaseURL()
        );

        return $policy;
    }

    /**
     * Any setting that is not set, this will be assumed as true, except for the reporting-only mode
     *
     *
     * @param ContentSecurityPolicyHeaderBuilder $policy
     * @throws \Phpcsp\Security\InvalidDirectiveException
     * @throws \Phpcsp\Security\InvalidValueException
     */
    protected function setConfigPolicies($policy)
    {
        $config = CSPBackend::config()->get('csp_config');

        if ($config['https'] === true) {
            $policy->addSourceExpression(ContentSecurityPolicyHeaderBuilder::DIRECTIVE_BASE_URI, 'https:');
        }

        if ($config['block_xss'] === true) {
            $policy->setReflectedXssPolicy(ContentSecurityPolicyHeaderBuilder::REFLECTED_XSS_BLOCK);
        }

        if ($config['upgrade_insecure_requests'] === true) {
            $policy->setUpgradeInsecureRequests(true);
        }

        foreach ($this->allowedDirectivesMap as $key => $directive) {
            foreach ($config[$key]['domains'] as $domain) {
                $policy->addSourceExpression($directive, $domain);
            }
        }
        if ($config['report_uri'] !== '') {
            $policy->setReportUri($config['report_uri']);
        }

        if ($config['report_only'] === true) {
            $policy->enforcePolicy(false);
            if ($config['report_only_uri'] === '') {
                throw new InvalidValueException('No report URI given for report-only directive', 1);
            }
            $policy->setReportUri($config['report_only_uri']);
        }

        if ($config['wizard']) {
            $policy->setReportUri($config['wizard_uri']);
        }

        if ($config['style']['allow_inline'] === true) {
            $policy->addSourceExpression(ContentSecurityPolicyHeaderBuilder::DIRECTIVE_STYLE_SRC, 'unsafe-inline');
        }

        foreach (static::$inlineJS as $js) {
            $policy->addHash(
                ContentSecurityPolicyHeaderBuilder::HASH_SHA_256,
                hash(ContentSecurityPolicyHeaderBuilder::HASH_SHA_256, $js, true)
            );
        }
    }

    /**
     * Add SiteConfig set domain policies
     *
     * @param ContentSecurityPolicyHeaderBuilder $policy
     */
    protected function setSiteConfigPolicies($policy)
    {
        /** @var DataList|CSPDomain[] $domains */
        $domains = SiteConfig::current_site_config()->CSPDomains();
        $map = $this->allowedDirectivesMap;

        foreach ($domains as $domain) {
            $policy->addSourceExpression($map[$domain->Source], $domain->Domain);
        }
    }

    protected function setReportPolicy()
    {
        $config = CSPBackend::config()->get('report_to');
        if ($config['report']) {
            $this->owner->getResponse()->addHeader(
                'Report-To',
                json_encode([
                    "group"     => "default",
                    "max_age"   => 31536000,
                    "endpoints" => [
                        [
                            "url" => $config['report_to_uri']
                        ],
                        "include_subdomains" => true
                    ]
                ])
            );
            if ($config['NEL']) {
                $this->owner->getResponse()->addHeader(
                    'NEL',
                    json_encode([
                        "report_to"          => "default",
                        "max_age"            => 31536000,
                        "include_subdomains" => true
                    ])
                );
            }
        }
    }
}
