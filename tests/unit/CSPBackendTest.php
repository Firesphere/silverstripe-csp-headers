<?php

namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Builders\CSSBuilder;
use Firesphere\CSPHeaders\Builders\JSBuilder;
use Firesphere\CSPHeaders\Extensions\ControllerCSPExtension;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

class CSPBackendTest extends SapphireTest
{
    public function testConstruct()
    {
        /** @var CSPBackend $backend */
        $backend = Injector::inst()->get(CSPBackend::class);

        $this->assertInstanceOf(CSSBuilder::class, $backend->getCssBuilder());
        $this->assertInstanceOf(JSBuilder::class, $backend->getJsBuilder());
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

        $this->assertEquals(['alert("hello world");', "alert('hello');"], ControllerCSPExtension::getInlineJS());
        $this->assertEquals(['body { color: red; }', 'body {background-color: red;}'], ControllerCSPExtension::getInlineCSS());
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
}
