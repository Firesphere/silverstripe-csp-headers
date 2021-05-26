<?php

namespace Firesphere\CSPHeaders\Tasks;

use Firesphere\CSPHeaders\Models\SRI;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\DebugView;

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
        $renderer = Director::is_cli() ? null : DebugView::create();
        if ($renderer) {
            echo $renderer->renderHeader();
            echo $renderer->renderInfo('Refresh SRI Task', 'Removing SRI values...');
        } else {
            echo "Removing SRI values...\n";
        }
        foreach (SRI::get() as $item) {
            $item->delete();
        }
        if ($renderer) {
            echo $renderer->renderMessage('Done', null, false);
            echo $renderer->renderFooter();
        } else {
            echo "Done\n";
        }
    }
}
