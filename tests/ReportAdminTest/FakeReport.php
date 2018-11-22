<?php
namespace SilverStripe\Reports\Tests\ReportAdminTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Reports\Report;

class FakeReport extends Report implements TestOnly
{
    public function title()
    {
        return 'Fake report';
    }
}
