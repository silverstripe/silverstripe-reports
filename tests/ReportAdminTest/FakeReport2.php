<?php
namespace SilverStripe\Reports\Tests\ReportAdminTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Reports\Report;
use SilverStripe\Reports\ReportAdmin;
use SilverStripe\View\ArrayData;

class FakeReport2 extends Report implements TestOnly
{
    public function title()
    {
        return 'Fake report two';
    }

    public function getBreadcrumbs()
    {
        return [ArrayData::create([
            'Title' => 'Fake report title',
            'Link' => FakeReport::singleton()->getLink()
        ])];
    }
}
