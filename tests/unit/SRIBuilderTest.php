<?php


namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Builders\SRIBuilder;
use Firesphere\CSPHeaders\Models\SRI;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
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

        $expected = sprintf('%s-%s', CSPBackend::SHA384, base64_encode(hash(CSPBackend::SHA384, $contents, true)));
        $this->assertEquals(['integrity' => $expected, 'crossorigin' => ''], $builder->buildSRI('composer.json', []));
    }

    public function testSkipDomains()
    {
        $builder = new SRIBuilder();
        $builder->buildSRI('composer.json', []);
        // skip files starting with composer
        $builder->config()->set('skip_domains', ['composer']);
        Cookie::set('buildHeaders', 'true');
        // Should not have added integrity to the array
        $this->assertEquals([], $builder->buildSRI('composer.json', []));
    }
}
