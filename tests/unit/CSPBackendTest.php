<?php

namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Builders\CSSBuilder;
use Firesphere\CSPHeaders\Builders\JSBuilder;
use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

class CSPBackendTest extends SapphireTest
{
    public function testConstruct()
    {
        /** @var CSPBackend $backend */
        $backend = Injector::inst()->get(CSPBackend::class);

        $this->assertInstanceOf(CSSBuilder::class, $backend->getCssBuilder());
        $this->assertInstanceOf(JSBuilder::class, $backend->getJsBuilder());
    }

    public function testInsertJSTag()
    {
        $js = 'alert("hello world");';

        /** @var CSPBackend $backend */
        $backend = Injector::inst()->get(CSPBackend::class);

        $backend->customScript($js);

        $tags = $backend->getCustomScripts();

        $this->assertContains('alert("hello world");', $tags[0]);
        $this->assertContains($js, ControllerCSPExtension::getInlineJS());
    }
}
