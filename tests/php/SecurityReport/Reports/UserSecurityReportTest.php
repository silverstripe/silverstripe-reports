<?php

namespace SilverStripe\Reports\Tests\SecurityReport\Reports;

use SilverStripe\Core\Config\Config;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Reports\SecurityReport\Extensions\MemberReportExtension;
use SilverStripe\Reports\Report;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Reports\SecurityReport\Reports\UserSecurityReport;

/**
 * User Security Report Tests.
 *
 * @author Michael Parkhill <mike@silverstripe.com>
 */
class UserSecurityReportTest extends SapphireTest
{
    protected static $fixture_file = 'UserSecurityReportTest.yml';

    protected $records;
    protected $report;

    protected static $required_extensions = [
        Member::class => [
            MemberReportExtension::class,
        ],
    ];

    /**
     * Utility method for all tests to use.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $reports = Report::get_reports();
        $report = $reports[UserSecurityReport::class];
        $this->report = $report;
        $this->records = $report->sourceRecords()->toArray();
    }

    public function testSourceRecords()
    {
        $this->assertNotEmpty($this->records);
    }

    public function testGetMemberGroups()
    {
        //getMemberGroups(&$member) returns string
        $member = $this->objFromFixture(Member::class, 'member-has-0-groups');
        $groups = $member->GroupsDescription;
        $this->assertEquals('Not in a Security Group', $groups);

        $member = $this->objFromFixture(Member::class, 'member-has-1-groups');
        $groups = $member->GroupsDescription;
        $this->assertStringContainsString('Group Test 01', $groups);
    }

    public function testGetMemberPermissions()
    {
        $member = $this->objFromFixture(Member::class, 'member-has-0-permissions');
        $perms = $member->PermissionsDescription;
        $this->assertEquals('No Permissions', $perms);

        $member = $this->objFromFixture(Member::class, 'member-has-1-permissions');
        $perms = $member->PermissionsDescription;
        $this->assertEquals('Full administrative rights', $perms);

        $member = $this->objFromFixture(Member::class, 'member-has-n-permissions');
        $perms = $member->PermissionsDescription;
        $this->assertEquals('Full administrative rights, Edit any page', $perms);
    }

    public function testLoginLoggingColumnShowsOnlyWhenApplicable()
    {
        $original = Config::inst()->get(Security::class, 'login_recording');

        Config::modify()->set(Security::class, 'login_recording', true);
        $this->assertContains('LastLoggedIn', array_keys($this->report->columns() ?? []));

        Config::modify()->set(Security::class, 'login_recording', false);
        $this->assertNotContains('LastLoggedIn', array_keys($this->report->columns() ?? []));

        Config::modify()->set(Security::class, 'login_recording', $original);
    }
}
