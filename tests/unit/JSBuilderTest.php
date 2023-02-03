<?php


namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Builders\JSBuilder;
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

class JSBuilderTest extends SapphireTest
{
    /**
     * Build and setup extended controller with request
     */
    private function BuildController()
    {
        CSPBackend::config()->set('jsSRI', true);
        CSPBackend::setUsesNonce(false);
        $backend = Injector::inst()->get(CSPBackend::class);
        Requirements::set_backend($backend);

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
        $builder = new JSBuilder($owner);

        $this->assertInstanceOf(CSPBackend::class, $builder->getOwner());
        $builder->setOwner($owner);
        $this->assertInstanceOf(CSPBackend::class, $builder->getOwner());
        $this->assertInstanceOf(SRIBuilder::class, $builder->getSriBuilder());
    }

    public function testBuildTagsUseNonce()
    {
        CSPBackend::config()->set('useNonce', true);

        $controller = $this->buildController();
        $owner = Requirements::backend();
        $builder = $owner->getJsBuilder();

        $tag = $builder->buildTags('file', [], [], '');
        $this->assertStringContainsString('nonce=', $tag[0]);

        CSPBackend::config()->set('useNonce', false);
        $controller->onBeforeInit();

        $tag = $builder->buildTags('file', [], [], '');
        $this->assertStringNotContainsString('nonce=', $tag[0]);
    }

    public function testGetHeadTagsUseNonce()
    {
        CSPBackend::config()->set('useNonce', true);

        $controller = $this->buildController();
        $owner = Requirements::backend();
        $builder = $owner->getJsBuilder();
        Requirements::insertHeadTags('<script type="application/javascript">test</script>');
        $req = [];
        $tag = $builder->getHeadTags($req);
        $this->assertStringContainsString('nonce=', $req[0]);

        CSPBackend::config()->set('useNonce', false);
        $controller->onBeforeInit();

        $req = [];
        $tag = $builder->getHeadTags($req);
        $this->assertStringNotContainsString('nonce=', $req[0]);
    }

    public function testGetCustomTagsUseNonce()
    {
        CSPBackend::config()->set('useNonce', true);

        $controller = $this->buildController();
        $owner = Requirements::backend();
        $builder = $owner->getJsBuilder();
        Requirements::customScript('test');

        $tag = $builder->getCustomTags([]);
        $this->assertStringContainsString('nonce=', $tag[0]);

        CSPBackend::config()->set('useNonce', false);
        $controller->onBeforeInit();

        $tag = $builder->getCustomTags([]);
        $this->assertStringNotContainsString('nonce=', $tag[0]);
    }

    public function testDisableBuildTags()
    {
        $controller = $this->buildController();
        $owner = Requirements::backend();
        $builder = $owner->getJsBuilder();
        // Should add integrity
        $this->assertTrue(CSPBackend::isJsSRI());
        $requirements = $builder->buildTags('composer.json', [], [], '/');
        $this->assertStringContainsString('integrity=', $requirements[0]);

        // Shouldn't add integrity if not enabled
        CSPBackend::config()->merge('jsSRI', false);
        $this->assertFalse(CSPBackend::isJsSRI());
        $controller->onBeforeInit();
        $requirements = $builder->buildTags('composer.json', [], [], '/');
        $this->assertStringNotContainsString('integrity=', $requirements[0]);

        // Should add integrity if not enabled but forced by build headers in request
        $request = Controller::curr()->getRequest();
        $request->offsetSet('build-headers', 'true');
        $this->assertFalse(CSPBackend::isJsSRI());
        $controller->onBeforeInit();
        $requirements = $builder->buildTags('composer.json', [], [], '/');
        $this->assertStringContainsString('integrity=', $requirements[0]);
        CSPBackend::config()->merge('jsSRI', true);
        $request->offsetUnset('build-headers');
        Cookie::force_expiry('buildHeaders');
    }
}
