<?php

namespace SilverStripe\Reports\Tests;

use ReflectionClass;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Reports\Report;
use SilverStripe\Reports\ReportAdmin;
use SilverStripe\Reports\Tests\ReportAdminTest\CannotViewReport;
use SilverStripe\Reports\Tests\ReportAdminTest\FakeReport;
use SilverStripe\Reports\Tests\ReportAdminTest\FakeReport2;
use PHPUnit\Framework\Attributes\DataProvider;

class ReportAdminTest extends FunctionalTest
{
    public function testBreadcrumbsAreGenerated()
    {
        $noExtraCrumbs = FakeReport::create();

        $controller = $this->mockController($noExtraCrumbs);
        $breadcrumbs = $controller->BreadCrumbs();

        $this->assertCount(2, $breadcrumbs);
        $map = $breadcrumbs[0]->toMap();
        $this->assertSame('Reports', $map['Title']);
        $this->assertSame('admin/reports', $map['Link']);

        $map = $breadcrumbs[1]->toMap();
        $this->assertSame('Fake report', $map['Title']);

        $extraCrumbs = FakeReport2::create();
        $controller = $this->mockController($extraCrumbs);
        $breadcrumbs = $controller->Breadcrumbs();

        $this->assertCount(3, $breadcrumbs);

        $map = $breadcrumbs[0]->toMap();
        $this->assertSame('Reports', $map['Title']);
        $this->assertSame('admin/reports', $map['Link']);

        $map = $breadcrumbs[1]->toMap();
        $this->assertSame('Fake report title', $map['Title']);
        $this->assertSame('admin/reports/show/SilverStripe-Reports-Tests-ReportAdminTest-FakeReport', $map['Link']);

        $map = $breadcrumbs[2]->toMap();
        $this->assertSame('Fake report two', $map['Title']);
    }

    public static function provideShowReport(): array
    {
        return [
            'cannot view' => [
                'reportClass' => CannotViewReport::class,
                'expected' => 403,
            ],
            'can view' => [
                'reportClass' => FakeReport::class,
                'expected' => 200,
            ],
        ];
    }

    #[DataProvider('provideShowReport')]
    public function testShowReport(string $reportClass, int $expected): void
    {
        $this->logInWithPermission('ADMIN');
        $report = new $reportClass();
        $controller = $this->mockController($report);
        $breadcrumbs = $controller->BreadCrumbs();
        $response = $this->get($breadcrumbs[1]->Link);

        $this->assertSame($expected, $response->getStatusCode());
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
