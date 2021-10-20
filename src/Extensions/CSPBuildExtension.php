<?php

namespace Firesphere\CSPHeaders\Extensions;

use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\DevBuildController;
use SilverStripe\ORM\DB;

/**
 * Class \Firesphere\CSPHeaders\Extensions\CSPBuildExtension
 *
 * Runs on dev/build to remove all known SRIs
 *
 * @property DevBuildController|CSPBuildExtension $owner
 */
class CSPBuildExtension extends Extension
{
    /**
     * Truncate the SRI table on build
     */
    public function afterCallActionHandler()
    {
        $config = CSPBackend::config()->get('clear_on_build');
        if ($config) {
            DB::query('TRUNCATE `SRI`');
        }
    }
}
