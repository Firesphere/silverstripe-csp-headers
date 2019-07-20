<?php


namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Builders\CSSBuilder;
use Firesphere\CSPHeaders\Builders\SRIBuilder;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Dev\SapphireTest;

class CSSBuilderTest extends SapphireTest
{
    public function testConstruct()
    {
        $owner = new CSPBackend();
        $builder = new CSSBuilder($owner);

        $this->assertInstanceOf(CSPBackend::class, $builder->getOwner());
        $builder->setOwner($owner);
        $this->assertInstanceOf(CSPBackend::class, $builder->getOwner());
        $this->assertInstanceOf(SRIBuilder::class, $builder->getSriBuilder());
    }
}
