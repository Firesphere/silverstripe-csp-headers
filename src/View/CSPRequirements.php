<?php

namespace Firesphere\CSPHeaders\View;

use SilverStripe\Core\Flushable;
use SilverStripe\View\Requirements;
use SilverStripe\View\Requirements_Backend;

class CSPRequirements extends Requirements implements Flushable
{
    private static $backend;


    /**
     * @return CSPBackend
     */
    public static function backend()
    {
        if (!self::$backend) {
            self::$backend = CSPBackend::create();
        }

        return self::$backend;
    }

    /**
     * @param string $js
     * @param null|string $identifier
     * @param array $options
     */
    public static function insertJSTags($js, $identifier = null, $options = [])
    {
        static::backend()->insertJSTags($js, $identifier, $options);
    }

    /**
     * @param string $css
     * @param null|string $identifier
     * @param array $options
     */
    public static function insertCSSTags($css, $identifier = null, $options = [])
    {
        static::backend()->insertCSSTags($css, $identifier, $options);
    }
}
