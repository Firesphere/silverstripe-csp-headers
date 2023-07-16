<?php

namespace Firesphere\CSPHeaders\Models;

use Firesphere\CSPHeaders\View\CSPBackend;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Core\Path;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
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

    private static $singular_name = 'Subresource Integrity';
    private static $plural_name = 'Subresource Integrities';

    /**
     * If enabled (and using framework 4.7+) then a dev/build will delete all
     * generated SRI and they will be regenerated when next required.
     * @var bool
     * @config
     */
    private static $clear_sri_on_build = false;

    private static $db = [
        'File' => DBVarchar::class,
        'SRI'  => DBVarchar::class
    ];

    private static $summary_fields = [
        'File',
        'LastEdited'
    ];

    private static $indexes = [
        'File' => true
    ];

    /**
     * @param $file
     * @return SRI
     * @throws ValidationException
     */
    public static function findOrCreate($file): SRI
    {
        /** @var SRI|null $sri */
        $sri = self::get()->filter(['File' => $file])->first();
        // Create on first time it's run, or if it's been deleted because the file has changed, known to the admin
        if (!$sri || !$sri->isInDB()) {
            $sri = self::create(['File' => $file]);
            $sri->write();
        }

        return $sri;
    }

    /**
     * Created on request
     * @param null|Member $member
     * @param array $context
     * @return bool
     */
    public function canCreate($member = null, $context = array())
    {
        return false;
    }

    /**
     * If it needs to be edited, it should actually be recreated
     * @param null|Member $member
     * @return bool
     */
    public function canEdit($member = null)
    {
        return false;
    }

    /**
     * @param null|Member $member
     * @return bool|int
     */
    public function canDelete($member = null)
    {
        if (!$member) {
            return false;
        }

        return Permission::checkMember($member, 'DELETE_SRI');
    }

    /**
     * Generate the SRI for the file given
     * @throws GuzzleException
     */
    public function onBeforeWrite()
    {
        $body = null;
        // Since this is called from CSP Backend, an SRI for external files is automatically created
        if (!Director::is_site_url($this->File)) {
            /** @var Client $client */
            $client = Injector::inst()->get(Client::class);
            $result = $client->request('GET', $this->File);
            $body = $result->getBody()->getContents();
        } else {
            $folders = [
                Director::baseFolder(),
                Director::publicFolder(),
                Director::publicFolder() . '/resources',
                Director::publicFolder() . '/_resources'
            ];

            foreach ($folders as $folder) {
                $filename = Path::join($folder, $this->File);
                if (file_exists($filename)) {
                    $body = file_get_contents($filename);
                    break;
                }
            }
        }

        // Unlikely, but possible, that $body is empty
        if ($body) {
            $hash = hash(CSPBackend::SHA384, $body, true);
            $this->SRI = base64_encode($hash);
        }

        parent::onBeforeWrite();
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

    /**
     * If configured, this deletes the Sub-resource integrity values on build of the database
     * so they're regenerated next time that file is used.
     * Note that this hook only exists in silverstripe-framework 4.7+
     */
    public function onAfterBuild()
    {
        if ($this->config()->get('clear_sri_on_build')) {
            SRI::get()->removeAll();
        }
    }
}
