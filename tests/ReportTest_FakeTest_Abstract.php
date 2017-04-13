<?php
namespace SilverStripe\Reports\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Reports\Report;

/**
 * @package reports
 * @subpackage tests
 */
abstract class ReportTest_FakeTest_Abstract extends Report implements TestOnly
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
