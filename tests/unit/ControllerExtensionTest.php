<?php


namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use Firesphere\CSPHeaders\View\CSPBackend;
use Page;
use PageController;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\NullHTTPRequest;
use SilverStripe\Dev\SapphireTest;

class ControllerExtensionTest extends SapphireTest
{
    public function testInit()
    {
        $this->assertFalse(ControllerCSPExtension::checkCookie(new NullHTTPRequest()));
        $page = new Page();
        $controller = new PageController($page);
        $extension = new ControllerCSPExtension();
        $extension->setOwner($controller);

        $this->assertFalse($extension->isAddPolicyHeaders());

        $extension->onBeforeInit();
        $this->assertNull($extension->getNonce());
        $extension->onAfterInit();
        $this->assertArrayNotHasKey('content-security-policy-report-only', $controller->getResponse()->getHeaders());

        $request = new HTTPRequest('GET', '/', ['build-headers' => 'true']);
        $controller->setRequest($request);
        $extension->setOwner($controller);

        $extension->onBeforeInit();

        $this->assertEquals('true', Cookie::get('buildHeaders'));
        $this->assertTrue(ControllerCSPExtension::checkCookie($request));
        $this->assertTrue($extension->isAddPolicyHeaders());
        $this->assertNull($extension->getNonce());
        CSPBackend::setUsesNonce(true);
        $extension->onBeforeInit();
        $this->assertNotNull($extension->getNonce());
        Cookie::force_expiry('buildHeaders');

        $extension->onAfterInit();

        $this->assertArrayHasKey('content-security-policy-report-only', $controller->getResponse()->getHeaders());
        $header = $controller->getResponse()->getHeader('content-security-policy-report-only');
        $this->assertContains('self', $header);
        $this->assertContains('style-src \'self\' \'unsafe-inline\'', $header);
        
    }
}
