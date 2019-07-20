<?php


namespace Firesphere\CSPHeaders\Traits;

use Firesphere\CSPHeaders\Builders\CSSBuilder;
use Firesphere\CSPHeaders\Builders\JSBuilder;
use Firesphere\CSPHeaders\View\CSPBackend;

/**
 * Trait CSPBackendTrait contains all variables, static and dynamic, and their respective getters and setters
 * This is to keep the CSPBackend class itself more readable
 * @package Firesphere\CSPHeaders\Traits
 */
trait CSPBackendTrait
{
    /**
     * @var bool
     */
    protected static $jsSRI;
    /**
     * CSS defaults to false.
     * It's causing a lot of trouble with CDN's usually
     * @var bool
     */
    protected static $cssSRI;
    /**
     * JS to be inserted in to the head
     * @var array
     */
    protected static $headJS = [];
    /**
     * CSS to be inserted in to the head
     * @var array
     */
    protected static $headCSS = [];
    /**
     * @var bool
     */
    protected static $usesNonce = false;
    /**
     * @var CSSBuilder
     */
    protected $cssBuilder;
    /**
     * @var JSBuilder
     */
    protected $jsBuilder;

    /**
     * @return bool
     */
    public static function isJsSRI(): bool
    {
        return CSPBackend::config()->get('jsSRI') || self::$jsSRI;
    }

    /**
     * @param bool $jsSRI
     */
    public static function setJsSRI(bool $jsSRI): void
    {
        self::$jsSRI = $jsSRI;
    }

    /**
     * @return bool
     */
    public static function isCssSRI(): bool
    {
        return CSPBackend::config()->get('cssSRI') || self::$cssSRI;
    }

    /**
     * @param bool $cssSRI
     */
    public static function setCssSRI(bool $cssSRI): void
    {
        self::$cssSRI = $cssSRI;
    }

    /**
     * @return array
     */
    public static function getHeadCSS(): array
    {
        return self::$headCSS;
    }

    /**
     * @param array $headCSS
     */
    public static function setHeadCSS(array $headCSS): void
    {
        self::$headCSS = $headCSS;
    }

    /**
     * @return array
     */
    public static function getHeadJS(): array
    {
        return self::$headJS;
    }

    /**
     * @param array $headJS
     */
    public static function setHeadJS(array $headJS): void
    {
        self::$headJS = $headJS;
    }

    /**
     * @return bool
     */
    public static function isUsesNonce(): bool
    {
        return CSPBackend::config()->get('useNonce') || self::$usesNonce;
    }

    /**
     * @param bool static::isUseNonce()
     */
    public static function setUsesNonce(bool $usesNonce): void
    {
        self::$usesNonce = $usesNonce;
    }


    /**
     * @return JSBuilder
     */
    public function getJsBuilder(): JSBuilder
    {
        return $this->jsBuilder;
    }

    /**
     * @param JSBuilder $jsBuilder
     */
    public function setJsBuilder(JSBuilder $jsBuilder): void
    {
        $this->jsBuilder = $jsBuilder;
    }

    /**
     * @return CSSBuilder
     */
    public function getCssBuilder(): CSSBuilder
    {
        return $this->cssBuilder;
    }

    /**
     * @param CSSBuilder $cssBuilder
     */
    public function setCssBuilder(CSSBuilder $cssBuilder): void
    {
        $this->cssBuilder = $cssBuilder;
    }
}
