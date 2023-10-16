<?php

namespace Firesphere\CSPHeaders\Models;

use Page;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBEnum;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

/**
 * Class \Firesphere\CSPHeaders\Models\CSPDomain
 *
 * @property string $Domain
 * @property string $Source
 * @method ManyManyList|Page[] Pages()
 */
class CSPDomain extends DataObject implements PermissionProvider
{
    private static $singular_name = 'Content Security Policy Domain';
    private static $plural_name = 'Content Security Policy Domains';

    private static $table_name = 'CSPDomain';

    private static $db = [
        'Domain' => DBVarchar::class,
        'Source' => DBEnum::class . '("default,script,style,img,media,font,form,frame,ancestor,worker,connect")'
    ];

    private static $belongs_many_many = [
        'Pages' => Page::class
    ];

    private static $summary_fields = [
        'Domain',
        'Source'
    ];

    private static $indexes = [
        'Domain' => true
    ];

    /**
     * @todo make translatable
     * @var array
     */
    private static $sourceMap = [
        'default' => 'All',
        'script'  => 'Javascripts',
        'style'   => 'Styling',
        'img'     => 'Images',
        'media'   => 'Embedded media (e.g. YouTube)',
        'font'    => 'Fonts',
        'form'    => 'Forms',
        'frame'   => 'Iframes',
        'worker'  => 'Worker',
        'connect' => 'Connect'
    ];

    private static $searchable_fields = [
        'Domain',
    ];

    /**
     * @return array|string[]
     */
    protected static function getSourceMap(): array
    {
        $map = self::$sourceMap;
        foreach ($map as $key => &$value) {
            [$translateKey] = explode(' ', strtoupper($value));
            $translateString = sprintf('%s.%s', __CLASS__, $translateKey);
            $value = _t($translateString, $value);
        }

        return $map;
    }

    /**
     * @param array|string[] $sourceMap
     */
    public static function setSourceMap(array $sourceMap): void
    {
        self::$sourceMap = $sourceMap;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->Domain;
    }

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['SiteConfigID']);

        $fields->dataFieldByName('Source')->setSource(self::getSourceMap());

        return $fields;
    }

    /**
     * @param null|Member $member
     * @param array $context
     * @return bool|int
     */
    public function canCreate($member = null, $context = array())
    {
        $canCreate = parent::canCreate($member, $context);

        if ($canCreate) {
            return Permission::check('CREATE_CSPDomain', 'any', $member);
        }

        return $canCreate;
    }

    /**
     * @param null|Member $member
     * @return bool|int
     */
    public function canEdit($member = null)
    {
        $canEdit = parent::canEdit($member);

        if ($canEdit) {
            return Permission::check('EDIT_CSPDomain', 'any', $member);
        }

        return $canEdit;
    }

    /**
     * @param null|Member $member
     * @return bool|int
     */
    public function canView($member = null)
    {
        $canView = parent::canView($member);

        if ($canView) {
            return Permission::check('VIEW_CSPDomain', 'any', $member);
        }

        return $canView;
    }

    /**
     * @param null|Member $member
     * @return bool|int
     */
    public function canDelete($member = null)
    {
        $canDelete = parent::canDelete($member);

        if ($canDelete) {
            return Permission::check('DELETE_CSPDOMAIN', 'any', $member);
        }

        return $canDelete;
    }

    /**
     * Return a map of permission codes to add to the dropdown shown in the Security section of the CMS.
     * array(
     *   'VIEW_SITE' => 'View the site',
     * );
     */
    public function providePermissions()
    {
        return [
            'CREATE_CSPDOMAIN' => [
                'name'     => _t(self::class . '.PERMISSION_CREATE_DESCRIPTION', 'Create CSP Domains'),
                'category' => _t('Permissions.CSPDOMAINS_CATEGORY', 'CSP Permissions'),
                'help'     => _t(
                    self::class . '.PERMISSION_CREATE_HELP',
                    'Permission required to create new CSP Domains.'
                )
            ],
            'EDIT_CSPDOMAIN'   => [
                'name'     => _t(self::class . '.PERMISSION_EDIT_DESCRIPTION', 'Edit CSP Domains'),
                'category' => _t('Permissions.CSPDOMAINS_CATEGORY', 'CSP Permissions'),
                'help'     => _t(
                    self::class . '.PERMISSION_EDIT_HELP',
                    'Permission required to edit existing CSP Domains.'
                )
            ],
            'DELETE_CSPDOMAIN' => [
                'name'     => _t(self::class . '.PERMISSION_DELETE_DESCRIPTION', 'Delete CSP Domains'),
                'category' => _t('Permissions.CSPDOMAINS_CATEGORY', 'CSP Permissions'),
                'help'     => _t(
                    self::class . '.PERMISSION_DELETE_HELP',
                    'Permission required to delete existing CSP Domains.'
                )
            ],
            'VIEW_CSPDOMAIN'   => [
                'name'     => _t(self::class . '.PERMISSION_VIEW_DESCRIPTION', 'View CSP Domains'),
                'category' => _t('Permissions.CSPDOMAINS_CATEGORY', 'CSP Permissions'),
                'help'     => _t(
                    self::class . '.PERMISSION_VIEW_HELP',
                    'Permission required to view existing CSP Domains.'
                )
            ],
        ];
    }
}
