<?php


namespace Firesphere\CSPHeaders\Tests;


use Firesphere\CSPHeaders\Extensions\PageExtension;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\Tab;

class PageExtensionTest extends SapphireTest
{

    public function testUpdateSettingsFields()
    {
        $page = new \Page();
        $extension = new PageExtension();
        $extension->setOwner($page);

        $fields = $page->getSettingsFields();
        $extension->updateSettingsFields($fields);

        $this->assertInstanceOf(GridField::class, $fields->dataFieldByName('CSPDomains'));
    }
}