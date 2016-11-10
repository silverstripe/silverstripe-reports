<?php

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Reports\Report;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Admin\CMSPreviewable ;
use SilverStripe\Forms\GridField\GridFieldDataColumns;


/**
 * @package reports
 * @subpackage tests
 */
class ReportTest extends SapphireTest
{
    protected $usesDatabase = true;

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
        $reportNames = array();
        foreach ($reports as $report) {
            $reportNames[] = get_class($report);
        }
        $this->assertContains('ReportTest_FakeTest', $reportNames, 'ReportTest_FakeTest is in reports list');

        //exclude one report
        Report::add_excluded_reports('ReportTest_FakeTest');

        $reports = Report::get_reports();
        $reportNames = array();
        foreach ($reports as $report) {
            $reportNames[] = get_class($report);
        }
        $this->assertNotContains('ReportTest_FakeTest', $reportNames, 'ReportTest_FakeTest is NOT in reports list');

        //exclude two reports
        Report::add_excluded_reports(array('ReportTest_FakeTest', 'ReportTest_FakeTest2'));

        $reports = Report::get_reports();
        $reportNames = array();
        foreach ($reports as $report) {
            $reportNames[] = get_class($report);
        }
        $this->assertNotContains('ReportTest_FakeTest', $reportNames, 'ReportTest_FakeTest is NOT in reports list');
        $this->assertNotContains('ReportTest_FakeTest2', $reportNames, 'ReportTest_FakeTest2 is NOT in reports list');
    }

    public function testAbstractClassesAreExcluded()
    {
        $reports = Report::get_reports();
        $reportNames = array();
        foreach ($reports as $report) {
            $reportNames[] = get_class($report);
        }
        $this->assertNotContains('ReportTest_FakeTest_Abstract',
            $reportNames,
            'ReportTest_FakeTest_Abstract is NOT in reports list as it is abstract');
    }

    public function testPermissions()
    {
        $report = new ReportTest_FakeTest2();

        // Visitor cannot view
        Session::clear("loggedInAs");
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

	public function testColumnLink() {
		$report = new ReportTest_FakeTest();
		/** @var GridField $gridField */
		$gridField = $report->getReportField();
		/** @var GridFieldDataColumns $columns */
		$columns = $gridField->getConfig()->getComponentByType(GridFieldDataColumns::class);

		$page = new ReportTest_FakeObject();
		$page->Title = 'My Object';
		$page->ID = 959547;

		$titleContent = $columns->getColumnContent($gridField, $page, 'Title');
		$this->assertEquals('<a class="grid-field__link-block" href="dummy-edit-link/959547" title="My Object">My Object</a>', $titleContent);
	}
}

class ReportTest_FakeObject extends DataObject implements CMSPreviewable, TestOnly {

	private static $db = array(
		'Title' => 'Varchar'
	);

	/**
	 * @return String Absolute URL to the end-user view for this record.
	 * Example: http://mysite.com/my-record
	 */
	public function Link()
	{
		return Controller::join_links('dummy-link', $this->ID);
	}

	public function CMSEditLink()
	{
		return Controller::join_links('dummy-edit-link', $this->ID);
	}

    public function PreviewLink($action = null) {
        return false;
	}

	public function getMimeType() {
        return 'text/html';
    }
}

/**
 * @package reports
 * @subpackage tests
 */
class ReportTest_FakeTest extends Report implements TestOnly
{
    public function title()
    {
        return 'Report title';
    }
    public function columns()
    {
        return array(
            "Title" => array(
                "title" => "Page Title",
                "link" => true,
            )
        );
    }
    public function sourceRecords($params, $sort, $limit)
    {
        return new ArrayList();
    }

    public function sort()
    {
        return 100;
    }
}

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
