<?php

namespace Firesphere\CSPHeaders\Admins;

use Firesphere\CSPHeaders\Models\CSPDomain;
use Firesphere\CSPHeaders\Models\SRI;
use SilverStripe\Admin\ModelAdmin;

/**
 * Class \Firesphere\CSPHeaders\Admins\SRIAdmin
 *
 */
class SRIAdmin extends ModelAdmin
{
    private static $managed_models = [
        SRI::class,
        CSPDomain::class,
    ];

    private static $url_segment = 'sri-admin';

    private static $menu_title = 'SRI & CSP';

    private static $menu_icon_class = 'font-icon-lock';
}
