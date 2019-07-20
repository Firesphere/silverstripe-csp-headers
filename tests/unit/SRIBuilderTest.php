<?php


namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Builders\SRIBuilder;
use Firesphere\CSPHeaders\Models\SRI;
use GuzzleHttp\Client;
use SilverStripe\Dev\SapphireTest;

class SRIBuilderTest extends SapphireTest
{
    public function testBuildSRI()
    {
        $builder = new SRIBuilder();

        $builder->buildSRI('composer.json', []);

        $sri = SRI::get()->filter(['File' => 'composer.json']);
        $this->assertInstanceOf(SRI::class, $sri);
    }
}
