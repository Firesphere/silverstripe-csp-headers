<?php


namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Builders\CSSBuilder;
use Firesphere\CSPHeaders\Builders\SRIBuilder;
use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use Firesphere\CSPHeaders\View\CSPBackend;
use Page;
use PageController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Session;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Requirements;

class CSSBuilderTest extends SapphireTest
{
    /**
     * Build and setup extended controller with request
     */
    private function BuildController()
    {
        CSPBackend::config()->set('cssSRI', true);
        CSPBackend::setUsesNonce(false);
        $backend = Injector::inst()->get(CSPBackend::class);
        Requirements::set_backend($backend);
        if (!class_exists('\Page')) {
            Controller::add_extension(Controller::class, ControllerCSPExtension::class);
            return new Controller();
        }

        $page = new Page();
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session('test'));

        PageController::add_extension(Controller::class, ControllerCSPExtension::class);
        $controller = new PageController($page);
        $controller->setRequest($request);
        $controller->pushCurrent();
        $controller->onBeforeInit();
        return $controller;
    }

    public function testConstruct()
    {
        $owner = new CSPBackend();
        $builder = new CSSBuilder($owner);

        $this->assertInstanceOf(CSPBackend::class, $builder->getOwner());
        $builder->setOwner($owner);
        $this->assertInstanceOf(CSPBackend::class, $builder->getOwner());
        $this->assertInstanceOf(SRIBuilder::class, $builder->getSriBuilder());
    }

    public function testBuildTags()
    {
        $controller = $this->buildController();
        $owner = Requirements::backend();
        /** @var CSSBuilder $builder */
        $builder = $owner->getCSSBuilder();

        $tag = $builder->buildTags('file', [], [], '');
        $this->assertStringContainsString('link', $tag[0]);

        CSPBackend::config()->set('useNonce', false);
        $controller->onBeforeInit();

        $tag = $builder->buildTags('file', [], [], '');
        $this->assertStringNotContainsString('nonce=', $tag[0]);
    }

    public function testGetHeadTagsUseNonce()
    {
        CSPBackend::config()->set('useNonce', true);
        CSPBackend::setUsesNonce(true);

        $controller = $this->buildController();
        $owner = Requirements::backend();
        $builder = $owner->getCssBuilder();
        Requirements::insertHeadTags('<style>test</style>');

        $req = [];
        $tag = $builder->getHeadTags($req);
        $this->assertStringContainsString('nonce=', $req[0]);

        CSPBackend::config()->set('useNonce', false);
        CSPBackend::setUsesNonce(false);
        $controller->onBeforeInit();

        $req = [];
        $tag = $builder->getHeadTags($req);
        $this->assertStringNotContainsString('nonce=', $req[0]);
    }

    public function testGetCustomTagsUseNonce()
    {
        CSPBackend::config()->set('useNonce', true);
        CSPBackend::setUsesNonce(true);

        $controller = $this->buildController();
        $owner = Requirements::backend();
        $builder = $owner->getCSSBuilder();
        Requirements::customCSS('test');

        $tag = $builder->getCustomTags([]);
        $this->assertStringContainsString('nonce=', $tag[0]);

        CSPBackend::config()->set('useNonce', false);
        CSPBackend::setUsesNonce(false);
        $controller->onBeforeInit();

        $tag = $builder->getCustomTags([]);
        $this->assertStringNotContainsString('nonce=', $tag[0]);
    }

    public function testDisableBuildTags()
    {
        $controller = $this->buildController();
        $owner = Requirements::backend();
        $builder = $owner->getCSSBuilder();
        // Should add integrity
        CSPBackend::config()->set('cssSRI', true);
        CSPBackend::setCssSRI(true);
        $this->assertTrue(CSPBackend::isCssSRI());
        $requirements = $builder->buildTags('composer.json', [], [], '/');
        $this->assertStringContainsString('integrity=', $requirements[0]);
        // Shouldn't add integrity if not enabled
        CSPBackend::config()->set('cssSRI', false);
        CSPBackend::setCssSRI(false);
        $this->assertFalse(CSPBackend::isCssSRI());
        $controller->onBeforeInit();
        $requirements = $builder->buildTags('composer.json', [], [], '/');
        $this->assertStringNotContainsString('integrity=', $requirements[0]);

        // Should add integrity if not enabled but forced by build headers in request
        $request = Controller::curr()->getRequest();
        $request->offsetSet('build-headers', 'true');
        $controller->onBeforeInit();
        $this->assertFalse(CSPBackend::isCssSRI());
        $requirements = $builder->buildTags('composer.json', [], [], '/');
        $this->assertStringContainsString('integrity=', $requirements[0]);
        $request->offsetUnset('build-headers');
        Cookie::force_expiry('buildHeaders');
    }
}
