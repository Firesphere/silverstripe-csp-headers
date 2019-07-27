<?php


namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Models\SRI;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\DefaultAdminService;

class SRITest extends SapphireTest
{
    private static $expected = [
        'DELETE_SRI' =>
            [
                'name' => 'Delete SRI',
                'category' => 'SRI permissions',
                'help' => 'Permission required to delete existing SRI\'s.',
            ],
    ];

    public function testCan()
    {
        $this->assertTrue((new SRI())->canView(null)); // You can view in any state
        $this->assertFalse((new SRI())->canEdit(null));
        $this->assertFalse((new SRI())->canDelete(null));
        $this->assertFalse((new SRI())->canCreate(null));
        $admin = DefaultAdminService::create()->findOrCreateAdmin('test', 'test');
        $this->logInAs($admin);
        $this->assertTrue((new SRI())->canView($admin));
        $this->assertFalse((new SRI())->canEdit($admin));
        $this->assertTrue((new SRI())->canDelete($admin));
        $this->assertFalse((new SRI())->canCreate($admin));
    }

    public function testPermissions()
    {
        $this->assertEquals(static::$expected, (new SRI())->providePermissions());
    }
}
