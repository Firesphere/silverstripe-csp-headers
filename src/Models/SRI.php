<?php

namespace Firesphere\CSPHeaders\Models;

use Firesphere\CSPHeaders\View\CSPBackend;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
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

    private static $db = [
        'File' => 'Varchar(255)',
        'SRI'  => 'Varchar(255)'
    ];

    private static $summary_fields = [
        'File'
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
            $filename = Director::baseFolder() . '/' . $this->File;
            if(file_exists($filename)) {
                $body = file_get_contents($filename);
            }

            //also check the public folder, the CMS dumps tinymce config here
            $publicFilename = Director::publicFolder(). '/' . $this->File;
            if(file_exists($publicFilename)) {
                $body = file_get_contents($publicFilename);
            }

        }

        //unlikely, but possible, that $body is empty
        if($body) {
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
}
