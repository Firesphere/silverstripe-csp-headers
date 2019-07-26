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
    }
}
