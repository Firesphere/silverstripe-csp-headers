<?php

namespace Firesphere\CSPHeaders\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

/**
 * Class \Firesphere\CSPHeaders\Models\SRI
 *
 * @property string $File
 * @property string $SRI
 */
class SRI extends DataObject implements PermissionProvider
{
    private static $table_name = 'SRI';

    private static $db = [
        'File' => 'Varchar(255)',
        'SRI'  => 'Varchar(255)'
    ];

    private static $summary_fields = [
        'File'
    ];

    public function canCreate($member = null, $context = array())
    {
        return false;
    }

    public function canEdit($member = null)
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return Permission::checkMember($member, 'DELETE_SRI');
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
            'DELETE_SRI' => [
                'name'     => _t(self::class . '.PERMISSION_DELETE_DESCRIPTION', 'Delete SRI'),
                'category' => _t('Permissions.TOPICS_CATEGORY', 'SRI permissions'),
                'help'     => _t(
                    self::class . '.PERMISSION_DELETE_HELP',
                    'Permission required to delete existing SRI\'s.'
                )
            ],
        ];
    }
}
