<?php

namespace SilverStripe\Reports\Tests\ReportAdminTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Reports\Report;

class CannotViewReport extends Report implements TestOnly
{
    public function title()
    {
        return 'Cannot View report';
    }

    public function canView($member = null)
    {
        return false;
    }
}
