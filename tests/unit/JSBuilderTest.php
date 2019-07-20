<?php


namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Builders\JSBuilder;
use Firesphere\CSPHeaders\Builders\SRIBuilder;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Dev\SapphireTest;

class JSBuilderTest extends SapphireTest
{
    public function testConstruct()
    {
        $owner = new CSPBackend();
        $builder = new JSBuilder($owner);

        $this->assertInstanceOf(CSPBackend::class, $builder->getOwner());
        $builder->setOwner($owner);
        $this->assertInstanceOf(CSPBackend::class, $builder->getOwner());
        $this->assertInstanceOf(SRIBuilder::class, $builder->getSriBuilder());
    }
}
