<?php

namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Requirements;

class CSPBackendTest extends SapphireTest
{
    public function testInsertJSTag()
    {
        $js = 'alert("hello world");';
        
        /** @var CSPBackend $backend */
        $backend = Injector::inst()->get(CSPBackend::class);
        
        $backend->insertJSTags($js);
        
        $tags = $backend->getCustomHeadTags();

        $this->assertContains('<script type="application/javascript">alert("hello world");</script>', $tags[0]);
        $this->assertContains($js, ControllerCSPExtension::getInlineJS());
    }
}
