<?php


namespace Firesphere\CSPHeaders\Extensions;

use Firesphere\CSPHeaders\Models\CSPDomain;
use Page;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\Tab;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\ManyManyList;

/**
 * Class \Firesphere\CSPHeaders\Extensions\SiteTreeExtension
 *
 * @property Page|PageExtension $owner
 * @method ManyManyList|CSPDomain[] CSPDomains()
 */
class PageExtension extends DataExtension
{

    private static $db = [
        'AllowCSSInline' => DBBoolean::class,
        'AllowJSInline'  => DBBoolean::class,
    ];

    private static $many_many = [
        'CSPDomains' => CSPDomain::class
    ];

    private static $defaults = [
        'AllowCSSInline' => false,
        'AllowJSInline'  => false,
    ];

    public function updateSettingsFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root', Tab::create(
            'CSP',
            _t(__CLASS__ . '.CSP', 'Content Security Policies')
        ));

        $config = GridFieldConfig_RelationEditor::create();
        $gridfield = GridField::create(
            'CSPDomains',
            _t(__CLASS__ . '.CSP_DOMAINS', 'Content Security Policy Domains'),
            $this->owner->CSPDomains(),
            $config
        );
        $fields->addFieldsToTab('Root.CSP', [
            CheckboxField::create('AllowCSSInline', _t(__CLASS__ . '.ALLOWCSSINLINE', 'Allow CSS inline')),
            CheckboxField::create('AllowJSInline', _t(__CLASS__ . '.ALLOWJSINLINE', 'Allow JS inline')),
            $gridfield
        ]);
    }
}
