<?php
namespace SilverStripe\Reports\Tests;

use ReflectionClass;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use SilverStripe\Reports\Report;
use SilverStripe\Reports\ReportAdmin;
use SilverStripe\Reports\Tests\ReportAdminTest\FakeReport;
use SilverStripe\Reports\Tests\ReportAdminTest\FakeReport2;

class ReportAdminTest extends SapphireTest
{
    use ArraySubsetAsserts;

    public function testBreadcrumbsAreGenerated()
    {
        $noExtraCrumbs = FakeReport::create();

        $controller = $this->mockController($noExtraCrumbs);
        $breadcrumbs = $controller->BreadCrumbs();

        $this->assertCount(2, $breadcrumbs);

        $this->assertArraySubset([
            'Title' => 'Reports',
            'Link' => 'admin/reports/',
        ], $breadcrumbs[0]->toMap(), true, 'Link to top level reports is within breadcrumbs');

        $this->assertArraySubset([
            'Title' => 'Fake report'
        ], $breadcrumbs[1]->toMap(), true, 'Current report is within breadcrumbs');

        $extraCrumbs = FakeReport2::create();
        $controller = $this->mockController($extraCrumbs);
        $breadcrumbs = $controller->Breadcrumbs();

        $this->assertCount(3, $breadcrumbs);

        $this->assertArraySubset([
            'Title' => 'Reports',
            'Link' => 'admin/reports/',
        ], $breadcrumbs[0]->toMap(), true, 'Link to top level reports is within breadcrumbs (again)');

        $this->assertArraySubset([
            'Title' => 'Fake report title',
            'Link' => 'admin/reports/show/SilverStripe-Reports-Tests-ReportAdminTest-FakeReport',
        ], $breadcrumbs[1]->toMap(), true, 'Custom breadcrumb appears');

        $this->assertArraySubset([
            'Title' => 'Fake report two'
        ], $breadcrumbs[2]->toMap(), true, 'Current report is still within breadcrumbs');
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
