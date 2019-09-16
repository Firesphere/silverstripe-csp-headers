<?php


namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Models\SRI;
use Firesphere\CSPHeaders\View\CSPBackend;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\DefaultAdminService;

class SRITest extends SapphireTest
{
    private static $expected = [
        'DELETE_SRI' =>
            [
                'name'     => 'Delete SRI',
                'category' => 'SRI permissions',
                'help'     => 'Permission required to delete existing SRI\'s.',
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
        $this->assertEquals(self::$expected, (new SRI())->providePermissions());
    }

    public function testOnBeforeWrite()
    {
        /** @var SRI $sri */
        $sri = SRI::create();
        $sri->File = 'http://127.0.0.1/codecov.yml';
        $sri->onBeforeWrite();
        $hash = hash(CSPBackend::SHA384, file_get_contents('codecov.yml'), true);
        $this->assertEquals('http://127.0.0.1/codecov.yml', $sri->File);
        $this->assertEquals(base64_encode($hash), $sri->SRI);
    }

    public function testFindOrCreate()
    {
        /** @var SRI $sri */
        $sri = SRI::findOrCreate('/readme.md');
        $hash = hash(CSPBackend::SHA384, file_get_contents('readme.md'), true);

        $this->assertEquals(base64_encode($hash), $sri->SRI);
        $this->assertGreaterThan(0, $sri->ID);
    }
}
