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
        $response->addHeader('content-security-policy', 'default-src "self" firesphere.dev https://firesphere.dev; script-src "self" unsafe-eval;');

        $yml = CSPConvertor::toYml($response, true);

        $array = Yaml::parse($yml);

        print_r($array);

        $this->assertStringContainsStringIgnoringCase('default-src:', $yml);
        $this->assertStringContainsStringIgnoringCase("self: true", $yml);
        $this->assertStringContainsStringIgnoringCase("firesphere.dev", $yml);
        $this->assertStringContainsStringIgnoringCase('script-src:', $yml);
        $this->assertStringContainsStringIgnoringCase("self: true", $yml);
        $this->assertStringContainsStringIgnoringCase('unsafe-inline: true', $yml);
    }
}
