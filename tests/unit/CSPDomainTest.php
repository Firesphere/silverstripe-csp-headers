<?php


namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Models\CSPDomain;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\DefaultAdminService;

class CSPDomainTest extends SapphireTest
{
    protected static $permissions =
        [
            'CREATE_CSPDOMAIN' =>
                [
                    'name'     => 'Create CSP Domains',
                    'category' => 'CSP Permissions',
                    'help'     => 'Permission required to create new CSP Domains.',
                ],
            'EDIT_CSPDOMAIN'   =>
                [
                    'name'     => 'Edit CSP Domains',
                    'category' => 'CSP Permissions',
                    'help'     => 'Permission required to edit existing CSP Domains.',
                ],
            'DELETE_CSPDOMAIN' =>
                [
                    'name'     => 'Delete CSP Domains',
                    'category' => 'CSP Permissions',
                    'help'     => 'Permission required to delete existing CSP Domains.',
                ],
            'VIEW_CSPDOMAIN'   =>
                [
                    'name'     => 'View CSP Domains',
                    'category' => 'CSP Permissions',
                    'help'     => 'Permission required to view existing CSP Domains.',
                ],
        ];

    public function testTitle()
    {
        $cspDomain = CSPDomain::create(['Domain' => 'example.com']);
        $this->assertEquals('example.com', $cspDomain->getTitle());
        $this->assertNotEquals('something.com', $cspDomain->getTitle());
    }

    public function testGetCMSFields()
    {
        $fields = (new CSPDomain())->getCMSFields();
        $this->assertNotNull($fields->dataFieldByName('Source'));
        $this->assertNull($fields->dataFieldByName('SiteConfigID'));
    }

    public function testCan()
    {
        $this->assertFalse((new CSPDomain())->canView(null));
        $this->assertFalse((new CSPDomain())->canEdit(null));
        $this->assertFalse((new CSPDomain())->canDelete(null));
        $this->assertFalse((new CSPDomain())->canCreate(null));
        $admin = DefaultAdminService::create()->findOrCreateAdmin('test', 'test');
        $this->logInAs($admin);
        $this->assertTrue((new CSPDomain())->canView($admin));
        $this->assertTrue((new CSPDomain())->canEdit($admin));
        $this->assertTrue((new CSPDomain())->canDelete($admin));
        $this->assertTrue((new CSPDomain())->canCreate($admin));
    }

    public function testPermissionList()
    {
        $domains = new CSPDomain();
        $this->assertEquals(self::$permissions, $domains->providePermissions());
    }
}
