<?php


namespace Firesphere\CSPHeaders\Extensions;

use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\DevBuildController;
use SilverStripe\ORM\DB;

/**
 * Class \Firesphere\CSPHeaders\Extensions\CSPBuildExtension
 *
 * Experimental feature that's not yet complete
 *
 * @property DevBuildController|CSPBuildExtension $owner
 */
class CSPBuildExtension extends Extension
{
    public function afterCallActionHandler()
    {
        $config = CSPBackend::config()->get('clear_on_build');
        if ($config) {
            DB::query('TRUNCATE `SRI`');
        }
    }
}
