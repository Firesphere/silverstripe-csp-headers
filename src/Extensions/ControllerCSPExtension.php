<?php


namespace Firesphere\CSPHeaders\Extensions;

use Firesphere\CSPHeaders\View\CSPBackend;
use Phpcsp\Security\ContentSecurityPolicyHeaderBuilder;
use Phpcsp\Security\InvalidValueException;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;

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
    protected static $inlineJS = [];

    protected static $inlineCSS = [];

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

    public static function addJS($js)
    {
        static::$inlineJS[] = $js;
    }

    public static function addCSS($css)
    {
        static::$inlineCSS[] = $css;
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
            $this->setReportPolicy();

            $headers = $policy->getHeaders(true);
            foreach ($headers as $header) {
                $this->owner->getResponse()->addHeader($header['name'], $header['value']);
            }
        }
    }

    /**
     * @param HTTPRequest $request
     * @return bool
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
        $policy->addSourceExpression(ContentSecurityPolicyHeaderBuilder::DIRECTIVE_DEFAULT_SRC, 'self');
        $policy->addSourceExpression(
            ContentSecurityPolicyHeaderBuilder::DIRECTIVE_DEFAULT_SRC,
            Director::absoluteBaseURL()
        );
        $policy->addSourceExpression(ContentSecurityPolicyHeaderBuilder::DIRECTIVE_BASE_URI, 'self');
        $policy->addSourceExpression(
            ContentSecurityPolicyHeaderBuilder::DIRECTIVE_BASE_URI,
            Director::absoluteBaseURL()
        );
        $policy->addSourceExpression(ContentSecurityPolicyHeaderBuilder::DIRECTIVE_FONT_SRC, 'self');
        $policy->addSourceExpression(ContentSecurityPolicyHeaderBuilder::DIRECTIVE_STYLE_SRC, 'self');
        $policy->addSourceExpression(ContentSecurityPolicyHeaderBuilder::DIRECTIVE_SCRIPT_SRC, 'self');
        $policy->addSourceExpression(ContentSecurityPolicyHeaderBuilder::DIRECTIVE_FORM_ACTION, 'self');
        $policy->addSourceExpression(ContentSecurityPolicyHeaderBuilder::DIRECTIVE_IMG_SRC, 'self');
        $policy->addSourceExpression(ContentSecurityPolicyHeaderBuilder::DIRECTIVE_MEDIA_SRC, 'self');

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

        if ($config['report_uri'] === $config['report_only_uri']) {
            throw new InvalidValueException('Report or ReportOnly URI can not be empty or equal');
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

    protected function setReportPolicy()
    {
        $config = CSPBackend::config()->get('report_to');
        if ($config['report']) {
            $this->owner->getResponse()->addHeader('Report-To',
                '{"group":"default","max_age":31536000,"endpoints":[{"url":"' . $config['report_to_uri'] . '"}],"include_subdomains":true}');
            if ($config['NEL']) {
                $this->owner->getResponse()->addHeader('NEL',
                    '{"report_to":"default","max_age":31536000,"include_subdomains":true}');
            }
        }
    }
}
