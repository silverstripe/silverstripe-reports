<?php

namespace SilverStripe\Reports\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Reports\Tests\ReportTest\FakeObject;
use SilverStripe\Reports\Tests\ReportTest\FakeReport;
use SilverStripe\Reports\Tests\ReportTest\FakeReport2;
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
        $this->assertContains(FakeReport::class, $reportNames, 'ReportTest_FakeReport is in reports list');

        // Exclude one report
        Config::modify()->merge(Report::class, 'excluded_reports', [FakeReport::class]);

        $reports = Report::get_reports();
        $reportNames = array();
        foreach ($reports as $report) {
            $reportNames[] = get_class($report);
        }
        $this->assertNotContains(FakeReport::class, $reportNames, 'ReportTest_FakeReport is NOT in reports list');

        // Exclude two reports
        Config::modify()->merge(Report::class, 'excluded_reports', [
            FakeReport::class,
            FakeReport2::class
        ]);

        $reports = Report::get_reports();
        $reportNames = [];
        foreach ($reports as $report) {
            $reportNames[] = get_class($report);
        }
        $this->assertNotContains(FakeReport::class, $reportNames, 'ReportTest_FakeReport is NOT in reports list');
        $this->assertNotContains(FakeReport2::class, $reportNames, 'ReportTest_FakeReport2 is NOT in reports list');
    }

    public function testAbstractClassesAreExcluded()
    {
        $reports = Report::get_reports();
        $reportNames = array();
        foreach ($reports as $report) {
            $reportNames[] = get_class($report);
        }
        $this->assertNotContains(
            'ReportTest_FakeReport_Abstract',
            $reportNames,
            'ReportTest_FakeReport_Abstract is NOT in reports list as it is abstract'
        );
    }

    public function testPermissions()
    {
        $report = new ReportTest\FakeReport2();

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
        $report = new ReportTest\FakeReport();
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

    public function testCountForOverview()
    {
        $report = new ReportTest\FakeReport3();

        // Count is limited to 10000 by default
        $this->assertEquals('10000+', $report->getCountForOverview());

        // Count is limited as per configuration
        Config::modify()->set(ReportTest\FakeReport3::class, 'limit_count_in_overview', 15);
        $this->assertEquals('15+', $report->getCountForOverview());

        // A null limit displays the full count
        Config::modify()->set(ReportTest\FakeReport3::class, 'limit_count_in_overview', null);
        $this->assertEquals('15000', $report->getCountForOverview());
    }
}
