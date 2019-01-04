<?php

namespace Firesphere\CSPHeaders\View;

use SilverStripe\Core\Flushable;
use SilverStripe\View\Requirements;

class CSPRequirements extends Requirements implements Flushable
{
    private static $backend;

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
     * @param string $css
     * @param null|string $identifier
     * @param array $options
     */
    public static function insertCSSTags($css, $identifier = null, $options = [])
    {
        static::backend()->insertCSSTags($css, $identifier, $options);
    }
}
