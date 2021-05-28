<?php


namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Builders\CSSBuilder;
use Firesphere\CSPHeaders\Builders\SRIBuilder;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Session;
use SilverStripe\Control\HTTPRequest;
use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use SilverStripe\Core\Injector\Injector;
use Page;
use PageController;
use SilverStripe\View\Requirements;

class CSSBuilderTest extends SapphireTest
{
    /**
     * Build and setup extended controller with request
     */
    private function BuildController()
    {
        CSPBackend::config()->set('jsSRI', false);
        CSPBackend::setUsesNonce(false);
        $backend = Injector::inst()->get(CSPBackend::class);
        Requirements::set_backend($backend);

        $page = new Page();
        $request = new HTTPRequest('GET', '/', ['build-headers' => 'true']);
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

    public function testGetHeadTagsUseNonce()
    {
        CSPBackend::config()->set('useNonce', true);

        $controller = $this->buildController();
        $owner = Requirements::backend();
        $builder = $owner->getCssBuilder();
        Requirements::insertHeadTags('<style>test</style>');

        $req = '';
        $tag = $builder->getHeadTags($req);
        $this->assertContains('nonce=', $req);

        CSPBackend::config()->set('useNonce', false);
        $controller->onBeforeInit();

        $req = '';
        $tag = $builder->getHeadTags($req);
        $this->assertNotContains('nonce=', $req);
    }

    public function testGetCustomTagsUseNonce()
    {
        CSPBackend::config()->set('useNonce', true);

        $controller = $this->buildController();
        $owner = Requirements::backend();
        $builder = $owner->getCSSBuilder();
        Requirements::customCSS('test');

        $tag = $builder->getCustomTags('');
        $this->assertContains('nonce=', $tag);

        CSPBackend::config()->set('useNonce', false);
        $controller->onBeforeInit();

        $tag = $builder->getCustomTags('');
        $this->assertNotContains('nonce=', $tag);
    }
}
