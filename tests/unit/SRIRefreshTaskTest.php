<?php


namespace Firesphere\CSPHeaders\Tests;

use Firesphere\CSPHeaders\Models\SRI;
use Firesphere\CSPHeaders\Tasks\SRIRefreshTask;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;

class SRIRefreshTaskTest extends SapphireTest
{
    public function testClearSRI()
    {
        SRI::get()->removeAll();
        $this->assertEquals(0, SRI::get()->count());
        $sri = new SRI();
        $sri->File = 'readme.md';
        $sri->write();
        $this->assertEquals(1, SRI::get()->count());

        // Test that the task clears out all SRI from the database
        $task = new SRIRefreshTask();
        $request = new HTTPRequest('GET', '/');
        $task->run($request);
        $this->assertEquals(0, SRI::get()->count());
    }
}
