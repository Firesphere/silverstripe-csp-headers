<?php

namespace Firesphere\CSPHeaders\Extensions;

use Firesphere\CSPHeaders\Models\SRI;
use SilverStripe\ORM\DataExtension;

class SRIRefreshExtension extends DataExtension
{
    /**
     * Deletes the Sub-resource integrity values on build of the database
     * so they're regenerated next time that file is used.
     */
    public function onAfterBuild()
    {
        foreach (SRI::get() as $item) {
            $item->delete();
        }
    }
}
