<?php


namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Builders\SRIBuilder;
use Firesphere\CSPHeaders\Models\SRI;
use Firesphere\CSPHeaders\View\CSPBackend;
use GuzzleHttp\Client;
use SilverStripe\Control\Director;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\SapphireTest;

class SRIBuilderTest extends SapphireTest
{
    public function testBuildSRI()
    {
        $builder = new SRIBuilder();

        $builder->buildSRI('composer.json', []);

        /** @var SRI $sri */
        $sri = SRI::get()->filter(['File' => 'composer.json'])->first();
        $contents = file_get_contents(Director::baseFolder() . '/composer.json');
        $base = base64_encode(hash(CSPBackend::SHA384, $contents, true));
        $this->assertInstanceOf(SRI::class, $sri);
        $this->assertEquals($base, $sri->SRI);
        $sriCheck = SRI::findOrCreate('composer.json');
        $this->assertEquals($sriCheck, $sri);
    }
}
