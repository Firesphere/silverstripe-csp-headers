<?php

namespace Firesphere\CSPHeaders\Tasks;

use Firesphere\CSPHeaders\Models\SRI;
use SilverStripe\Dev\BuildTask;

class SRIRefreshTask extends BuildTask
{
    protected $title = 'Refresh SRI';
    protected $description = 'Deletes cache of Sub-resource integrities for js/css resources,
        these will regenerate on the next request.';

    /**
     * Deletes the Sub-resource integrity values on build of the database
     * so they're regenerated next time that file is required.
     */
    public function run($request)
    {
        echo "Removing SRI values...\n";
        foreach (SRI::get() as $item) {
            $item->delete();
        }
        echo "done\n";
    }
}
