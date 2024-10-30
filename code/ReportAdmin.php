<?php

namespace SilverStripe\Reports;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Filters\PartialMatchFilter;
use SilverStripe\ORM\Search\BasicSearchContext;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

/**
 * Reports section of the CMS.
 *
 * All reports that should show in the ReportAdmin section
 * of the CMS need to subclass {@link SilverStripe\Reports\Report}, and implement
 * the appropriate methods and variables that are required.
 */
class ReportAdmin extends LeftAndMain implements PermissionProvider
{
    private static $url_segment = 'reports';

    private static $menu_title = 'Reports';

    private static $menu_icon_class = 'font-icon-chart-line';

    private static $template_path = null; // defaults to (project)/templates/email

    /**
     * @deprecated 5.4.0 Will be renamed to model_class
     */
    private static $tree_class = Report::class;

    private static $url_handlers = array(
        'show/$ReportClass/$Action' => 'handleAction'
    );

    /**
     * Variable that describes which report we are currently viewing based on
     * the URL (gets set in init method).
     *
     * @var string
     */
    protected $reportClass;

    /**
     * @var Report
     */
    protected $reportObject;

    private static $required_permission_codes = 'CMS_ACCESS_ReportAdmin';

    public function init()
    {
        parent::init();

        // Set custom options for TinyMCE specific to ReportAdmin
        HTMLEditorConfig::get('cms')->setOption('content_css', project() . '/css/editor.css');

        Requirements::javascript('silverstripe/reports: javascript/ReportAdmin.js');
    }

    /**
     * Does the parent permission checks, but also
     * makes sure that instantiatable subclasses of
     * {@link SilverStripe\Reports\Report} exist. By default, the CMS doesn't
     * include any Reports, so there's no point in showing
     *
     * @param Member $member
     * @return boolean
     */
    public function canView($member = null)
    {
        if (!$member && $member !== false) {
            $member = Security::getCurrentUser();
        }

        if (!parent::canView($member)) {
            return false;
        }

        foreach ($this->Reports() as $report) {
            if ($report->canView($member)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return a SS_List of SS_Report subclasses
     * that are available for use.
     *
     * @return SS_List
     */
    public function Reports()
    {
        $output = ArrayList::create();
        foreach (Report::get_reports() as $report) {
            if ($report->canView()) {
                $output->push($report);
            }
        }

        return $output
            ->sort('Title', 'ASC')
            ->setDataClass(Report::class);
    }

    public function handleAction($request, $action)
    {
        $this->reportClass = $this->unsanitiseClassName($request->param('ReportClass'));

        // Check report
        if ($this->reportClass) {
            $allReports = Report::get_reports();
            if (empty($allReports[$this->reportClass])) {
                return $this->httpError(404);
            }
            $this->reportObject = $allReports[$this->reportClass];
            if (!$this->reportObject->canView()) {
                return Security::permissionFailure($this);
            }
        }

        // Delegate to sub-form
        return parent::handleAction($request, $action);
    }

    /**
     * Unsanitise a model class' name from a URL param
     *
     * @param string $class
     * @return string
     */
    protected function unsanitiseClassName($class)
    {
        return str_replace('-', '\\', $class ?? '');
    }

    /**
     * Determine if we have reports and need
     * to display the "Reports" main menu item
     * in the CMS.
     *
     * The test for an existance of a report
     * is done by checking for a subclass of
     * "SS_Report" that exists.
     *
     * @return boolean
     */
    public static function has_reports()
    {
        return sizeof(Report::get_reports() ?? []) > 0;
    }

    /**
     * Returns the Breadcrumbs for the ReportAdmin
     */
    public function Breadcrumbs($unlinked = false)
    {
        $items = parent::Breadcrumbs($unlinked);

        // The root element should explicitly point to the root node.
        // Uses session state for current record otherwise.
        $items[0]->Link = singleton('SilverStripe\\Reports\\ReportAdmin')->Link();

        if ($report = $this->reportObject) {
            $breadcrumbs = $report->getBreadcrumbs();
            if (!empty($breadcrumbs)) {
                foreach ($breadcrumbs as $crumb) {
                    $items->push($crumb);
                }
            }

            //build breadcrumb trail to the current report
            $items->push(ArrayData::create([
                'Title' => $report->title(),
                'Link' => Controller::join_links(
                    $this->Link(),
                    '?' . http_build_query(['q' => $this->request->requestVar('q')])
                )
            ]));
        }

        return $items;
    }

    /**
     * Returns the link to the report admin section, or the specific report that is currently displayed
     *
     * @param string $action
     * @return string
     */
    public function Link($action = null)
    {
        if ($this->reportObject) {
            return $this->reportObject->getLink($action);
        }

        // Basic link to this cms section
        return parent::Link($action);
    }

    public function providePermissions()
    {
        return array(
            "CMS_ACCESS_ReportAdmin" => array(
                'name' => _t('SilverStripe\\CMS\\Controllers\\CMSMain.ACCESS', "Access to '{title}' section", array(
                    'title' => static::menu_title()
                )),
                'category' => _t('SilverStripe\\Security\\Permission.CMS_ACCESS_CATEGORY', 'CMS Access')
            )
        );
    }

    public function getEditForm($id = null, $fields = null)
    {
        $report = $this->reportObject;
        if ($report) {
            $fields = $report->getCMSFields();
        } else {
            // List all reports
            $fields = FieldList::create();
            $gridFieldConfig = GridFieldConfig::create()->addComponents(
                // This is a container component that is required by filter header component
                GridFieldButtonRow::create('before'),
                $filterHeader = GridFieldFilterHeader::create(),
                GridFieldSortableHeader::create(),
                $columns = GridFieldDataColumns::create(),
                GridFieldPageCount::create(),
                GridFieldPaginator::create()
            );

            $titleLabel = _t('SilverStripe\\Reports\\ReportAdmin.ReportTitle', 'Title');
            $descriptionLabel = _t('SilverStripe\\Reports\\ReportAdmin.ReportDescription', 'Description');

            // Configure the filter header filter search form
            $generalField = BasicSearchContext::config()->get('general_search_field_name');
            $searchFieldList = FieldList::create([
                HiddenField::create($generalField),
                TextField::create('Title', $titleLabel),
                TextField::create('Description', $descriptionLabel),
            ]);
            $searchContext = BasicSearchContext::create(Report::class);
            $searchContext->setFields($searchFieldList);

            // Setup filter configuration - partial match with case-insensitive modifier
            $filters = [
                'Title',
                'Description',
            ];
            foreach ($filters as $fieldName) {
                $fieldFilter = PartialMatchFilter::create($fieldName);
                $searchContext->addFilter($fieldFilter);
            }
            $filterHeader->setSearchContext($searchContext);

            $gridField = GridField::create('Reports', false, $this->Reports(), $gridFieldConfig);
            $columns->setDisplayFields(array(
                'title' => $titleLabel,
                'description' => $descriptionLabel,
            ));

            $columns->setFieldFormatting([
                'title' => '<a href=\"$Link\" class=\"grid-field__link-block\">$value ($CountForOverview)</a>'
            ]);
            $gridField->addExtraClass('all-reports-gridfield');
            $fields->push($gridField);
        }

        $actions = FieldList::create();
        $form = Form::create($this, "EditForm", $fields, $actions);
        $form->addExtraClass(
            'panel panel--padded panel--scrollable cms-edit-form cms-panel-padded' . $this->BaseCSSClasses()
        );
        $form->loadDataFrom($this->request->getVars());

        $this->extend('updateEditForm', $form);

        return $form;
    }
}
