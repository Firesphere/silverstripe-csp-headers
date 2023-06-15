<?php

namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Helpers\CSPConvertor;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\SapphireTest;

class CSPConvertorTest extends SapphireTest
{

    /**
     * @return void
     */
    public function testToYml()
    {
        $response = new HTTPResponse([], 200);
        $response->addHeader('content-security-policy', 'default-src "self"');

        $yml = CSPConvertor::toYml($response, true);

        $this->assertContains('default-src:', $yml);
        $this->assertContains("self: true", $yml);
    }
}
