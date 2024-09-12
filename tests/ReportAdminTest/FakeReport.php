<?php

namespace SilverStripe\Reports\Tests\ReportAdminTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Reports\Report;
use SilverStripe\Security\Member;

class FakeReport extends Report implements TestOnly
{
    public function title()
    {
        return 'Fake report';
    }

    public function sourceRecords($params = [], $sort = null, $limit = null)
    {
        $list = new ArrayList();
        $list->setDataClass(Member::class);
        return $list;
    }
}
