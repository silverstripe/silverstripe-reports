<?php

/**
 * Renderer for showing SideReports in CMSMain
 * 
 * @package reports
 */
class SideReportView extends ViewableData {

	protected $controller, $report;
	protected $parameters;
	
	public function __construct($controller, $report) {
		$this->controller = $controller;
		$this->report = $report;
		parent::__construct();
	}
	
	public function group() {
		return _t('SideReport.OtherGroupTitle', "Other");
	}
	
	public function sort() {
		return 0;
	}
	
	public function setParameters($parameters) {
		$this->parameters = $parameters;
	}
	
	public function forTemplate() {
		$records = $this->report->records($this->parameters);
		$columns = $this->report->columns();
		
		if($records && $records->Count()) {
			$result = "<ul class=\"$this->class\">\n";
			
			foreach($records as $record) {
				$result .= "<li>\n";
				foreach($columns as $source => $info) {
					if(is_string($info)) $info = array('title' => $info);
					$result .= $this->formatValue($record, $source, $info);
				}
				$result .= "\n</li>\n";
			}
			$result .= "</ul>\n";	
		} else {
			$result = "<p class=\"message notice\">" . 
				_t(
					'SideReport.REPEMPTY',
					'The {title} report is empty.',
					array('title' => $this->report->title())
				) 
				. "</p>";
		}
		return $result;
	}
	
	protected function formatValue($record, $source, $info) {
		// Field sources
		//if(is_string($source)) {
			$val = Convert::raw2xml($record->$source);
		//} else {
		//	$val = $record->val($source[0], $source[1]);
		//}
		
		// Casting, a la TableListField.  We're deep-calling a helper method on TableListField that
		// should probably be pushed elsewhere...
		if(!empty($info['casting'])) {
			$val = TableListField::getCastedValue($val, $info['casting']);
		}
		
		// Formatting, a la TableListField
		if(!empty($info['formatting'])) {
			$format = str_replace('$value', "__VAL__", $info['formatting']);
			$format = preg_replace('/\$([A-Za-z0-9-_]+)/','$record->$1', $format);
			$format = str_replace('__VAL__', '$val', $format);
			$val = eval('return "' . $format . '";');
		}

		$prefix = empty($info['newline']) ? "" : "<br>";

		
		$classClause = "";
		if(isset($info['title'])) {
			$cssClass = preg_replace('/[^A-Za-z0-9]+/', '', $info['title']);
			$classClause = "class=\"$cssClass\"";
		}
		
		if(isset($info['link']) && $info['link']) {
			$link = ($info['link'] === true && $record->hasMethod('CMSEditLink'))
				? $record->CMSEditLink()
				: $info['link'];
			return $prefix . "<a $classClause href=\"$link\">$val</a>";
		} else {
			return $prefix . "<span $classClause>$val</span>";
		}
	}
}

/**
 * A report wrapper that makes it easier to define slightly different behaviour for side-reports.
 * 
 * This report wrapper will use sideReportColumns() for the report columns, instead of columns().
 * 
 * @package reports
 */
class SideReportWrapper extends SS_ReportWrapper {
	public function columns() {
		if($this->baseReport->hasMethod('sideReportColumns')) {
			return $this->baseReport->sideReportColumns();
		} else {
			return parent::columns();
		}
	}
}