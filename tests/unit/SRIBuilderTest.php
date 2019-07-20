<?php


namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Builders\SRIBuilder;
use GuzzleHttp\Client;
use SilverStripe\Dev\SapphireTest;

class SRIBuilderTest extends SapphireTest
{
    public function testConstruct()
    {
        $builder = new SRIBuilder();

        $this->assertInstanceOf(Client::class, $builder->getClient());
    }
}
