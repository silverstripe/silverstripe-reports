<?php

namespace SilverStripe\Reports\Tests\SiteWideContentReport\Reports;

use Page;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\ORM\DataList;
use SilverStripe\Reports\SiteWideContentReport\Extensions\SitewideContentTaxonomy;
use SilverStripe\Reports\SitewideContentReport\Reports\SitewideContentReport;

class SitewideContentReportTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = 'SitewideContentReportTest.yml';

    protected function setUp(): void
    {
        // Stop default page creation from occuring - just use fixtures
        Config::modify()->set(SiteTree::class, 'create_default_pages', false);

        parent::setUp();

        foreach (range(1, 5) as $i) {
            /** @var SiteTree $page */
            $page = $this->objFromFixture(Page::class, "page{$i}");

            if ($i <= 3) {
                $page->publishRecursive();
            }
        }
    }

    public function testSourceRecords()
    {
        $report = SitewideContentReport::create();
        $records = $report->sourceRecords([]);

        $this->assertCount(2, $records, 'Returns an array with 2 items, one for pages and one for files');
        $this->assertArrayHasKey('Pages', $records);
        $this->assertArrayHasKey('Files', $records);

        /** @var DataList $pages */
        $pages = $records['Pages'];

        /** @var DataList $files */
        $files = $records['Files'];

        $this->assertCount(5, $pages, 'Total number of pages');
        $this->assertCount(1, $files, 'Total number of files');
    }

    public function testReportFields()
    {
        $report = SitewideContentReport::create();

        // Test pages view
        $gridField = $report->getReportField('Pages');

        /* @var $columns GridFieldDataColumns */
        $columns = $gridField->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $displayed = $columns->getDisplayFields($gridField);

        $this->assertArrayHasKey('Title', $displayed);
        $this->assertArrayHasKey('Created', $displayed);
        $this->assertArrayHasKey('LastEdited', $displayed);
        $this->assertArrayHasKey('i18n_singular_name', $displayed);
        $this->assertArrayHasKey('StageState', $displayed);

        // Use correct link
        $this->assertArrayHasKey('RelativeLink', $displayed);
        $this->assertArrayNotHasKey('AbsoluteLink', $displayed);

        // Export-only fields are not in display list
        $this->assertArrayNotHasKey('Terms', $displayed);
        $this->assertArrayNotHasKey('OwnerNames', $displayed);
        $this->assertArrayNotHasKey('ReviewDate', $displayed);
        $this->assertArrayNotHasKey('MetaDescription', $displayed);

        // Tests print / export field
        /* @var $export GridFieldExportButton */
        $export = $gridField->getConfig()->getComponentByType(GridFieldExportButton::class);
        $exported = $export->getExportColumns();

        // Make sure all shared columns are in this report
        $this->assertArrayHasKey('Title', $exported);
        $this->assertArrayHasKey('Created', $exported);
        $this->assertArrayHasKey('LastEdited', $exported);
        $this->assertArrayHasKey('i18n_singular_name', $exported);
        $this->assertArrayHasKey('StageState', $exported);

        // Export-only fields
        $this->assertArrayHasKey('MetaDescription', $exported);

        // Use correct link
        $this->assertArrayHasKey('AbsoluteLink', $exported);
        $this->assertArrayNotHasKey('RelativeLink', $exported);

        if (SitewideContentTaxonomy::enabled()) {
            $this->assertArrayHasKey('Terms', $exported);
        } else {
            $this->assertArrayNotHasKey('Terms', $exported);
        }

        $this->assertArrayNotHasKey('OwnerNames', $exported);
        $this->assertArrayNotHasKey('ReviewDate', $exported);
    }
}
