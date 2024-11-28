<?php

namespace SilverStripe\Reports\Tests\ReportTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Reports\Report;

abstract class FakeReportAbstract extends Report implements TestOnly
{
    public function title()
    {
        return 'Report title Abstract';
    }

    public function columns()
    {
        return array(
            "Title" => array(
                "title" => "Page Title Abstract"
            )
        );
    }

    public function sourceRecords($params, $sort, $limit)
    {
        return new ArrayList();
    }

    public function sort()
    {
        return 5;
    }
}
