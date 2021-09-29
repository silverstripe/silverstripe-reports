<?php

namespace SilverStripe\Reports\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Reports\Tests\ReportTest\FakeObject;
use SilverStripe\Reports\Tests\ReportTest\FakeTest;
use SilverStripe\Reports\Tests\ReportTest\FakeTest2;
use SilverStripe\Reports\Report;

class ReportTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        FakeObject::class,
    ];

    public function testGetReports()
    {
        $reports = Report::get_reports();
        $this->assertNotNull($reports, "Reports returned");
        $previousSort = 0;
        foreach ($reports as $report) {
            $this->assertGreaterThanOrEqual($previousSort, $report->sort, "Reports are in correct sort order");
            $previousSort = $report->sort;
        }
    }

    public function testExcludeReport()
    {
        $reports = Report::get_reports();
        $reportNames = [];
        foreach ($reports as $report) {
            $reportNames[] = get_class($report);
        }
        $this->assertContains(FakeTest::class, $reportNames, 'ReportTest_FakeTest is in reports list');

        // Exclude one report
        Config::modify()->merge(Report::class, 'excluded_reports', [FakeTest::class]);

        $reports = Report::get_reports();
        $reportNames = array();
        foreach ($reports as $report) {
            $reportNames[] = get_class($report);
        }
        $this->assertNotContains(FakeTest::class, $reportNames, 'ReportTest_FakeTest is NOT in reports list');

        // Exclude two reports
        Config::modify()->merge(Report::class, 'excluded_reports', [
            FakeTest::class,
            FakeTest2::class
        ]);

        $reports = Report::get_reports();
        $reportNames = [];
        foreach ($reports as $report) {
            $reportNames[] = get_class($report);
        }
        $this->assertNotContains(FakeTest::class, $reportNames, 'ReportTest_FakeTest is NOT in reports list');
        $this->assertNotContains(FakeTest2::class, $reportNames, 'ReportTest_FakeTest2 is NOT in reports list');
    }

    public function testAbstractClassesAreExcluded()
    {
        $reports = Report::get_reports();
        $reportNames = array();
        foreach ($reports as $report) {
            $reportNames[] = get_class($report);
        }
        $this->assertNotContains(
            'ReportTest_FakeTest_Abstract',
            $reportNames,
            'ReportTest_FakeTest_Abstract is NOT in reports list as it is abstract'
        );
    }

    public function testPermissions()
    {
        $report = new ReportTest\FakeTest2();

        // Visitor cannot view
        $this->logOut();
        $this->assertFalse($report->canView());

        // Logged in user that cannot view reports
        $this->logInWithPermission('SITETREE_REORGANISE');
        $this->assertFalse($report->canView());

        // Logged in with report permissions
        $this->logInWithPermission('CMS_ACCESS_ReportAdmin');
        $this->assertTrue($report->canView());

        // Admin can view
        $this->logInWithPermission('ADMIN');
        $this->assertTrue($report->canView());
    }

    public function testColumnLink()
    {
        $report = new ReportTest\FakeTest();
        /** @var GridField $gridField */
        $gridField = $report->getReportField();
        /** @var GridFieldDataColumns $columns */
        $columns = $gridField->getConfig()->getComponentByType(GridFieldDataColumns::class);

        $page = new ReportTest\FakeObject();
        $page->Title = 'My Object';
        $page->ID = 959547;

        $titleContent = $columns->getColumnContent($gridField, $page, 'Title');
        $this->assertEquals(
            '<a class="grid-field__link-block" href="dummy-edit-link/959547" title="My Object">My Object</a>',
            $titleContent
        );
    }
}
