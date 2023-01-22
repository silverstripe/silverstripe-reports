<?php

namespace SilverStripe\Reports;

use SilverStripe\Core\Injector\Injector;

/**
 * SS_ReportWrapper is a base class for creating report wappers.
 *
 * Wrappers encapsulate an existing report to alter their behaviour - they are implementations of
 * the standard GoF decorator pattern.
 *
 * This base class ensure that, by default, wrappers behave in the same way as the report that is
 * being wrapped.  You should override any methods that need to behave differently in your subclass
 * of SS_ReportWrapper.
 *
 * It also makes calls to 2 empty methods that you can override {@link beforeQuery()} and
 * {@link afterQuery()}
 */
abstract class ReportWrapper extends Report
{
    protected $baseReport;

    public function __construct($baseReport)
    {
        $this->baseReport = is_string($baseReport) ? Injector::inst()->create($baseReport) : $baseReport;
        $this->dataClass = $this->baseReport->dataClass();
        parent::__construct();
    }

    public function ID()
    {
        return get_class($this->baseReport) . '_' . static::class;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Filtering

    public function parameterFields()
    {
        return $this->baseReport->parameterFields();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Columns

    public function columns()
    {
        return $this->baseReport->columns();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Querying

    /**
     * Override this method to perform some actions prior to querying.
     */
    public function beforeQuery($params)
    {
    }

    /**
     * Override this method to perform some actions after querying.
     */
    public function afterQuery()
    {
    }

    public function sourceQuery($params)
    {
        if ($this->baseReport->hasMethod('sourceRecords')) {
            // The default implementation will create a fake query from our sourceRecords() method
            return parent::sourceQuery($params);
        } elseif ($this->baseReport->hasMethod('sourceQuery')) {
            $this->beforeQuery($params);
            $query = $this->baseReport->sourceQuery($params);
            $this->afterQuery();
            return $query;
        } else {
            throw new \RuntimeException(
                "Please override sourceQuery()/sourceRecords() and columns() in your base report"
            );
        }
    }

    public function sourceRecords($params = array(), $sort = null, $limit = null)
    {
        $this->beforeQuery($params);
        $records = $this->baseReport->sourceRecords($params, $sort, $limit);
        $this->afterQuery();
        return $records;
    }


    ///////////////////////////////////////////////////////////////////////////////////////////
    // Pass-through

    public function title()
    {
        return $this->baseReport->title();
    }

    public function group()
    {
        return $this->baseReport->hasMethod('group') ? $this->baseReport->group() : 'Group';
    }

    public function sort()
    {
        return $this->baseReport->hasMethod('sort') ? $this->baseReport->sort() : 0;
    }

    public function description()
    {
        return $this->baseReport->description();
    }

    public function canView($member = null)
    {
        return $this->baseReport->canView($member);
    }
}
