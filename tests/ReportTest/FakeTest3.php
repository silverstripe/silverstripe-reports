<?php

namespace SilverStripe\Reports\Tests\ReportTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Reports\Report;

class FakeTest3 extends Report implements TestOnly
{
    public function sourceRecords($params, $sort, $limit)
    {
        return new ArrayList(range(1, 15000));
    }
}
