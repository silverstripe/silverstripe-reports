<?php

namespace SilverStripe\Reports;

/**
 * A report wrapper that makes it easier to define slightly different behaviour for side-reports.
 *
 * This report wrapper will use sideReportColumns() for the report columns, instead of columns().
 */
class SideReportWrapper extends ReportWrapper
{
    public function columns()
    {
        if ($this->baseReport->hasMethod('sideReportColumns')) {
            return $this->baseReport->sideReportColumns();
        } else {
            return parent::columns();
        }
    }
}
