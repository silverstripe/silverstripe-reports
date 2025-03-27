<?php

namespace SilverStripe\Reports\Tests\ExternalLinks;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Reports\ExternalLinks\Model\BrokenExternalPageTrackStatus;
use SilverStripe\Reports\ExternalLinks\Reports\BrokenExternalLinksReport;
use SilverStripe\Reports\ExternalLinks\Tasks\CheckExternalLinksTask;
use SilverStripe\Reports\ExternalLinks\Tasks\LinkChecker;
use SilverStripe\Reports\Tests\ExternalLinks\Stubs\ExternalLinksTestPage;
use SilverStripe\Reports\Tests\ExternalLinks\Stubs\PretendLinkChecker;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\i18n\i18n;
use SilverStripe\Reports\Report;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Reports\ExternalLinks\Model\BrokenExternalPageTrack;
use Symfony\Component\Console\Output\BufferedOutput;

class ExternalLinksTest extends FunctionalTest
{
    protected static $fixture_file = 'ExternalLinksTest.yml';

    protected static $extra_dataobjects = array(
        ExternalLinksTestPage::class
    );

    protected function setUp(): void
    {
        parent::setUp();

        // Stub link checker
        $checker = new PretendLinkChecker();
        Injector::inst()->registerService($checker, LinkChecker::class);
    }

    public function testLinks()
    {
        // Run link checker
        $task = CheckExternalLinksTask::create();
        $task->runLinksCheck(PolyOutput::create(PolyOutput::FORMAT_ANSI, PolyOutput::VERBOSITY_QUIET));

        // Get all links checked
        $status = BrokenExternalPageTrackStatus::get_latest();
        $this->assertEquals('Completed', $status->Status);
        $this->assertEquals(5, $status->TotalPages);
        $this->assertEquals(5, $status->CompletedPages);

        // Check all pages have had the correct HTML adjusted
        for ($i = 1; $i <= 5; $i++) {
            $page = $this->objFromFixture(ExternalLinksTestPage::class, 'page' . $i);
            $this->assertNotEmpty($page->Content);
            $this->assertEquals(
                $page->ExpectedContent,
                $page->Content,
                "Assert that the content of page{$i} has been updated"
            );
        }

        // Check that the correct report of broken links is generated
        $links = $status
            ->BrokenLinks()
            ->sort('Link');

        $this->assertEquals(4, $links->count());
        $this->assertEquals(
            array(
                'http://www.broken.com',
                'http://www.broken.com/url/thing',
                'http://www.broken.com/url/thing',
                'http://www.nodomain.com'
            ),
            array_values($links->map('ID', 'Link')->toArray() ?? [])
        );

        // Check response codes are correct
        $expected = array(
            'http://www.broken.com' => 403,
            'http://www.broken.com/url/thing' => 404,
            'http://www.nodomain.com' => 0
        );
        $actual = $links->map('Link', 'HTTPCode')->toArray();
        $this->assertEquals($expected, $actual);

        // Check response descriptions are correct
        i18n::set_locale('en_NZ');
        $expected = array(
            'http://www.broken.com' => '403 (Forbidden)',
            'http://www.broken.com/url/thing' => '404 (Not Found)',
            'http://www.nodomain.com' => '0 (Server Not Available)'
        );
        $actual = $links->map('Link', 'HTTPCodeDescription')->toArray();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that broken links appears in the reports list
     */
    public function testReportExists()
    {
        $reports = Report::get_reports();
        $reportNames = array();
        foreach ($reports as $report) {
            $reportNames[] = get_class($report);
        }
        $this->assertContains(
            BrokenExternalLinksReport::class,
            $reportNames,
            'BrokenExternalLinksReport is in reports list'
        );
    }

    public function testArchivedPagesAreHiddenFromReport()
    {
        // Run link checker
        $task = CheckExternalLinksTask::create();
        $task->runLinksCheck(PolyOutput::create(PolyOutput::FORMAT_ANSI, PolyOutput::VERBOSITY_QUIET));

        // Ensure report lists all broken links
        $this->assertEquals(4, BrokenExternalLinksReport::create()->sourceRecords()->count());

        // Archive a page
        $page = $this->objFromFixture(ExternalLinksTestPage::class, 'page1');
        $page->doArchive();

        // Ensure report does not list the link associated with an archived page
        $this->assertEquals(3, BrokenExternalLinksReport::create()->sourceRecords()->count());
    }

    public function testMissingPageGracefullyHandled(): void
    {
        // Create an output buffer to capture the output
        $bufferedOutput = new BufferedOutput();
        $polyOutput = new PolyOutput(PolyOutput::FORMAT_ANSI, PolyOutput::VERBOSITY_NORMAL, false, $bufferedOutput);
        // Run link checker to generate BrokenExternalPageTrackStatus
        // This needs to be created with ::create() so that its private static $dependencies are injected
        $task = CheckExternalLinksTask::create();
        $task->runLinksCheck($polyOutput);
        // Assert the task did not generated an 'Unable to find page' message
        $output = $bufferedOutput->fetch();
        $this->assertStringNotContainsString('Unable to find page', $output);
        // Simulate an old status that didn't complete, though was later reused
        // also simluate a page it was tracking was deleted
        $status = BrokenExternalPageTrackStatus::get_latest();
        $status->Status = 'Running';
        $status->write();
        $pageID = BrokenExternalPageTrack::get()->max('ID') + 1;
        $pageTrack = new BrokenExternalPageTrack();
        $pageTrack->PageID = $pageID;
        $pageTrack->StatusID = $status->ID;
        $pageTrack->write();
        // Re-run link checker to generate BrokenExternalPageTrackStatus
        $task->runLinksCheck($polyOutput);
        // Assert the task generated a message
        $output = $bufferedOutput->fetch();
        $this->assertStringContainsString("Unable to find page with ID $pageID. Continuing.", $output);
    }

    public static function provideGetJobStatus(): array
    {
        return [
            'ADMIN - valid permission' => ['ADMIN', 200],
            'CMS_ACCESS_CMSMain - valid permission' => ['CMS_ACCESS_CMSMain', 200],
            'VIEW_SITE - not enough permission' => ['VIEW_SITE', 403],
        ];
    }

    #[DataProvider('provideGetJobStatus')]
    public function testGetJobStatus(
        string $permission,
        int $expectedResponseCode
    ): void {
        $this->logInWithPermission($permission);

        $response = $this->get('admin/externallinks/start', null, ['Accept' => 'application/json']);
        $this->assertEquals($expectedResponseCode, $response->getStatusCode());

        $response = $this->get('admin/externallinks/getJobStatus', null, ['Accept' => 'application/json']);
        $this->assertEquals($expectedResponseCode, $response->getStatusCode());
    }
}
