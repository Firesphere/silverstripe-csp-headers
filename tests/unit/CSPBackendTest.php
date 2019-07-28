<?php

namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Builders\CSSBuilder;
use Firesphere\CSPHeaders\Builders\JSBuilder;
use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\SapphireTest;

class CSPBackendTest extends SapphireTest
{
    public function testSet()
    {
        $origJS = CSPBackend::getHeadJS();
        $origCSS = CSPBackend::getHeadCSS();
        CSPBackend::setHeadJS([]);
        $this->assertEmpty(CSPBackend::getHeadJS());
        CSPBackend::setHeadCSS([]);
        $this->assertEmpty(CSPBackend::getHeadCSS());
        CSPBackend::setHeadJS($origJS);
        CSPBackend::setHeadCSS($origCSS);
        $this->assertEquals($origJS, CSPBackend::getHeadJS());
        $this->assertEquals($origCSS, CSPBackend::getHeadCSS());
    }

    public function testConstruct()
    {
        /** @var CSPBackend $backend */
        $backend = Injector::inst()->get(CSPBackend::class);

        $this->assertInstanceOf(CSSBuilder::class, $backend->getCssBuilder());
        $this->assertInstanceOf(JSBuilder::class, $backend->getJsBuilder());
    }

    public function testIncludeInHTML()
    {
        /** @var CSPBackend $backend */
        $backend = Injector::inst()->get(CSPBackend::class, false);

        $content = '<html><body></body></html>';

        $result = $backend->includeInHTML($content);

        $this->assertEquals($content, $result);

        $content2 = '<html><head></head><body></body></html>';

        $this->assertEquals($content2, $backend->includeInHTML($content2));

        $backend->customCSS('body {background-color: red;}');

        $expected = '<html><head><style type="text/css">
body {background-color: red;}
</style>
</head><body></body></html>';

        $this->assertEquals($expected, $backend->includeInHTML($content2));

        $backend->insertHeadTags('<meta name="test" />');

        $expected = '<html><head><style type="text/css">
body {background-color: red;}
</style>
<meta name="test" />
</head><body></body></html>';

        $this->assertEquals($expected, $backend->includeInHTML($content2));
    }

    public function testInsertJSTag()
    {
        $js = 'alert("hello world");';

        /** @var CSPBackend $backend */
        $backend = Injector::inst()->get(CSPBackend::class);

        $backend->customScript($js);

        $tags = $backend->getCustomScripts();

        $this->assertContains('alert("hello world");', $tags[0]);
        $this->assertContains($js, ControllerCSPExtension::getInlineJS());
    }

    public function testInsertCSSTag()
    {
        $css = 'body { color: red; }';

        /** @var CSPBackend $backend */
        $backend = Injector::inst()->get(CSPBackend::class);

        $backend->customCSS($css);

        $tags = $backend->getCustomCSS();

        $this->assertContains('body { color: red; }', $tags[0]);
        $this->assertContains($css, ControllerCSPExtension::getInlineCSS());
    }

    public function testInsertHeadTags()
    {
        /** @var CSPBackend $backend */
        $backend = Injector::inst()->get(CSPBackend::class);

        $script = "<script type='text/javascript'>alert('hello');</script>";
        $css = '<style>body {background-color: red;}</style>';
        $other = '<meta name="test">test</meta>';

        $backend->insertHeadTags($script);
        $backend->insertHeadTags($css);
        $backend->insertHeadTags($other);

        $scriptHash = hash('sha256', $script);
        $cssHash = hash('sha256', $css);
        $otherHash = hash('sha256', $other);

        $this->assertArrayHasKey($scriptHash, CSPBackend::getHeadJS());
        $this->assertArrayHasKey($cssHash, CSPBackend::getHeadCSS());
        $headJS = CSPBackend::getHeadJS();
        $this->assertEquals(['type' => 'text/javascript'], $headJS[$scriptHash]['alert(\'hello\');']);
        $this->assertEquals([$otherHash => $other], $backend->getCustomHeadTags());

        $expected = [
            'body {background-color: red;}',
            'body { color: red; }',
            'body {background-color: red;}',
        ];
        $this->assertEquals(['alert("hello world");', "alert('hello');"], ControllerCSPExtension::getInlineJS());
        $this->assertEquals(
            $expected,
            ControllerCSPExtension::getInlineCSS()
        );
    }

    public function testGetTagType()
    {
        /** @var CSPBackend $backend */
        $backend = Injector::inst()->get(CSPBackend::class);

        $script = "<script type='text/javascript'>alert('hello');</script>";
        $css = '<style>body {background-color: red;}';
        $other = '<meta name="test">test</meta>';

        $this->assertEquals('javascript', $backend->getTagType($script));
        $this->assertEquals('css', $backend->getTagType($css));
        $this->assertNull($backend->getTagType($other));
    }

    public function testNonce()
    {
        $this->assertFalse(CSPBackend::isUsesNonce());
        CSPBackend::setUsesNonce(true);
        $this->assertTrue(CSPBackend::isUsesNonce());
        CSPBackend::setUsesNonce(false);
    }

    public function testJavascript()
    {
        $backend = new CSPBackend();
        $backend->javascript('test/my/script.js');
        $expected = [
            'test/my/script.js' => [
                'async'    => false,
                'defer'    => false,
                'type'     => 'text/javascript',
                'fallback' => false
            ]
        ];
        $this->assertEquals($expected, $backend->getJavascript());
        $backend->javascript('test/my/script.js', ['async' => true, 'defer' => true]);
        $expected = [
            'test/my/script.js' => [
                'async'    => true,
                'defer'    => true,
                'type'     => 'text/javascript',
                'fallback' => false
            ]
        ];
        $this->assertEquals($expected, $backend->getJavascript());
        $backend->javascript(
            'test/my/script.js',
            ['async' => true, 'defer' => true, 'fallback' => '1234567890987654321']
        );
        $expected = [
            'test/my/script.js' => [
                'async'    => true,
                'defer'    => true,
                'type'     => 'text/javascript',
                'fallback' => '1234567890987654321'
            ]
        ];
        $this->assertEquals($expected, $backend->getJavascript());
        $backend->javascript('test/my/script.js', ['provides' => ['test/some/script.js']]);
        $expected = [
            'test/my/script.js' => [
                'async'    => true, // Should not change from the loading above
                'defer'    => true, // Should not change from the loading above
                'type'     => 'text/javascript',
                'fallback' => '1234567890987654321', // Should not change from the loading above
            ]
        ];
        $this->assertEquals($expected, $backend->getJavascript());
    }

    public function testSRISettings()
    {
        $isSRI = CSPBackend::config()->get('jsSRI');
        $this->assertEquals($isSRI, CSPBackend::isJsSRI());

        CSPBackend::setJsSRI(true);
        $this->assertEquals(true, CSPBackend::isJsSRI());
        CSPBackend::setJsSRI(false);
        $this->assertEquals($isSRI, CSPBackend::isJsSRI());

        $isSRI = CSPBackend::config()->get('cssSRI');
        $this->assertEquals($isSRI, CSPBackend::isCssSRI());

        CSPBackend::setCssSri(true);
        $this->assertEquals(true, CSPBackend::isCssSRI());
        CSPBackend::setCssSri(false);
        $this->assertEquals($isSRI, CSPBackend::isCssSRI());
    }
}
