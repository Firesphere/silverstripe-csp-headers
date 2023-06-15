<?php

namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Helpers\CSPConvertor;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\SapphireTest;
use Symfony\Component\Yaml\Yaml;

class CSPConvertorTest extends SapphireTest
{

    /**
     * @return void
     */
    public function testToYml()
    {
        $response = new HTTPResponse([], 200);
        $response->addHeader('content-security-policy',
            'default-src "self" firesphere.dev https://firesphere.dev; script-src "self" unsafe-eval;
            report-uri: https://example.com/report/uri; upgrade-insecure-requests'
        );

        $yml = CSPConvertor::toYml($response, true);

        $array = Yaml::parse($yml);

        $this->assertStringContainsStringIgnoringCase('default-src:', $yml);
        $this->assertStringContainsStringIgnoringCase("self: true", $yml);
        $this->assertStringContainsStringIgnoringCase("firesphere.dev", $yml);
        $this->assertStringContainsStringIgnoringCase('script-src:', $yml);
        $this->assertStringContainsStringIgnoringCase("self: true", $yml);
        $this->assertStringContainsStringIgnoringCase('unsafe-eval: true', $yml);
        $this->assertStringContainsStringIgnoringCase('report-uri:', $yml);
        $this->assertStringContainsStringIgnoringCase('upgrade-insecure-requests', $yml);

        $this->assertEquals(1, $array['default-src']['self']);
        $this->assertEquals(1, $array['script-src']['self']);
        $this->assertEquals(1, $array['script-src']['unsafe-eval']);
        $this->assertCount(0, $array['script-src']['allow']);
        $this->assertCount(1, $array['default-src']['allow']);
        $this->assertEquals('true', $array['upgrade-insecure-requests']);
    }
}
