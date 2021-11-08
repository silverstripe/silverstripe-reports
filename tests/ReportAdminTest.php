<?php
namespace SilverStripe\Reports\Tests;

use ReflectionClass;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Reports\Report;
use SilverStripe\Reports\ReportAdmin;
use SilverStripe\Reports\Tests\ReportAdminTest\FakeReport;
use SilverStripe\Reports\Tests\ReportAdminTest\FakeReport2;

class ReportAdminTest extends SapphireTest
{
    public function testBreadcrumbsAreGenerated()
    {
        $noExtraCrumbs = FakeReport::create();

        $controller = $this->mockController($noExtraCrumbs);
        $breadcrumbs = $controller->BreadCrumbs();

        $this->assertCount(2, $breadcrumbs);
        $map = $breadcrumbs[0]->toMap();
        $this->assertSame('Reports', $map['Title']);
        $this->assertSame('admin/reports/', $map['Link']);

        $map = $breadcrumbs[1]->toMap();
        $this->assertSame('Fake report', $map['Title']);

        $extraCrumbs = FakeReport2::create();
        $controller = $this->mockController($extraCrumbs);
        $breadcrumbs = $controller->Breadcrumbs();

        $this->assertCount(3, $breadcrumbs);

        $map = $breadcrumbs[0]->toMap();
        $this->assertSame('Reports', $map['Title']);
        $this->assertSame('admin/reports/', $map['Link']);

        $map = $breadcrumbs[1]->toMap();
        $this->assertSame('Fake report title', $map['Title']);
        $this->assertSame('admin/reports/show/SilverStripe-Reports-Tests-ReportAdminTest-FakeReport', $map['Link']);

        $map = $breadcrumbs[2]->toMap();
        $this->assertSame('Fake report two', $map['Title']);
    }

    /**
     * @param Report $report
     * @return ReportAdmin
     * @throws \ReflectionException
     */
    protected function mockController(Report $report)
    {
        $reflector = new ReflectionClass($controller = ReportAdmin::create());

        $reportClass = $reflector->getProperty('reportClass');
        $reportClass->setAccessible(true);
        $reportClass->setValue($controller, get_class($report));

        $reportObject = $reflector->getProperty('reportObject');
        $reportObject->setAccessible(true);
        $reportObject->setValue($controller, $report);

        $controller->setRequest(Controller::curr()->getRequest());

        return $controller;
    }
}
