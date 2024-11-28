<?php

namespace SilverStripe\Reports\Tests\ReportTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Reports\Report;

class FakeReport2 extends Report implements TestOnly
{
    public function title()
    {
        return 'Report title 2';
    }

    public function columns()
    {
        return array(
            "Title" => array(
                "title" => "Page Title 2"
            )
        );
    }

    public function sourceRecords($params, $sort, $limit)
    {
        return new ArrayList();
    }

    public function sort()
    {
        return 98;
    }
}
