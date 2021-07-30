<?php

namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Extensions\CSPBuildExtension;
use Firesphere\CSPHeaders\Models\SRI;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Dev\SapphireTest;

class CSPBuildExtensionTest extends SapphireTest
{
    public function testAfterCallActionHandler()
    {
        CSPBackend::config()->set('clear_on_build', false);

        SRI::findOrCreate('/jstest.js');
        (new CSPBuildExtension())->afterCallActionHandler();

        $this->assertEquals(1, SRI::get()->count());
        CSPBackend::config()->set('clear_on_build', true);
        (new CSPBuildExtension())->afterCallActionHandler();
        $this->assertEquals(0, SRI::get()->count());
    }
}
