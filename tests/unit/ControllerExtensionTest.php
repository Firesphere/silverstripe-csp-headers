<?php


namespace Firesphere\CSPHeaders\Tests;

use BadMethodCallException;
use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use Firesphere\CSPHeaders\View\CSPBackend;
use Page;
use PageController;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\NullHTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Security;

class ControllerExtensionTest extends SapphireTest
{
    public function setUp()
    {
        parent::setUp();
        CSPBackend::config()->update('useNonce', false);
    }
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

    public function testNonceOnExcludedControllers()
    {
        //when CSPBackend.useNonce is true, it should only apply to controllers
        //with the extension applied. By default, this is page controller
        CSPBackend::setUsesNonce(true);
        $page = new Page();
        $controller = new PageController($page);
        $extension = new ControllerCSPExtension();
        $extension->setOwner($controller);

        //useNonce is set but only applies on the PageController.
        //let's check Security controller for logins: it should be absent
        $secController = new Security();
        $this->setExpectedException('BadMethodCallException');
        $this->assertNull($secController->getNonce());

        //also check CMS-level controllers
        $cmsController = new LeftAndMain();
        $this->setExpectedException('BadMethodCallException');
        $this->assertNull($secController->getNonce());

        //now apply the extension, getNonce should not be null
        $extension2 = new ControllerCSPExtension();
        $extension2->setOwner($secController);
        $this->assertNotNull($secController->getNonce());

        $extension3 = new ControllerCSPExtension();
        $extension3->setOwner($cmsController);
        $this->assertNotNull($cmsController->getNonce());

    }
}
