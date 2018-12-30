<?php

namespace Firesphere\CSPHeaders\Extensions;

use Firesphere\CSPHeaders\Models\CSPDomain;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;

/**
 * Class \Firesphere\CSPHeaders\Extensions\SiteConfigExtension
 *
 * @property SiteConfig|SiteConfigExtension $owner
 * @method DataList|CSPDomain[] CSPDomains()
 */
class SiteConfigExtension extends DataExtension
{
    private static $has_many = [
        'CSPDomains' => CSPDomain::class
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $cspConfig = GridFieldConfig_RecordEditor::create();

        $fields->addFieldToTab(
            'Root.CSP',
            GridField::create(
                'CSPDomains',
                _t(self::class . '.CSPDOMAINS', 'CSP Domains'),
                CSPDomain::get(),
                $cspConfig
            )
        );
    }
}
