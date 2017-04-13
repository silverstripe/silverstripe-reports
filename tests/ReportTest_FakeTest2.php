<?php
namespace SilverStripe\Reports\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Reports\Report;

/**
 * @package reports
 * @subpackage tests
 */
class ReportTest_FakeTest2 extends Report implements TestOnly
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
