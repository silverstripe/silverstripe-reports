<?php

namespace SilverStripe\Reports\SiteWideContentReport\Reports;

use Page;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldComponent;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Reports\Report;
use SilverStripe\Reports\SiteWideContentReport\Form\GridFieldBasicContentReport;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\Requirements;

/**
 * Content side-report listing all pages and files from all sub sites.
 */
class SitewideContentReport extends Report
{
    /**
     * @return string
     */
    public function title()
    {
        return _t(__CLASS__ . '.Title', 'Site-wide content report');
    }

    /**
     * @return string
     */
    public function description()
    {
        return _t(__CLASS__ . '.Description', 'All pages and files across the system');
    }

    /**
     * Returns an array with 2 elements, one with a list of Page on the site and another with files.
     *
     * @param array $params
     * @return array
     */
    public function sourceRecords($params = [])
    {
        $records = [
            'Pages' => Versioned::get_by_stage(SiteTree::class, 'Stage'),
            'Files' => File::get(),
        ];
        $this->extend('updateSourceRecords', $records, $params);
        return $records;
    }

    public function getCount($params = [], $limit = null)
    {
        $records = $this->sourceRecords();
        return $records['Pages']->count() + $records['Files']->count();
    }

    /**
     * Returns columns for the grid fields on this report.
     *
     * @param string $itemType (i.e 'Pages' or 'Files')
     *
     * @return array
     */
    public function columns($itemType = 'Pages')
    {
        $columns = [
            'Title' => [
                'title' => _t(__CLASS__ . '.Name', 'Name'),
                'link' => true,
            ],
            'Created' => [
                'title' => _t(__CLASS__ . '.Created', 'Date created'),
                'formatting' => function ($value, $item) {
                    return $item->dbObject('Created')->Nice();
                },
            ],
            'LastEdited' => [
                'title' => _t(__CLASS__ . '.LastEdited', 'Date last edited'),
                'formatting' => function ($value, $item) {
                    return $item->dbObject('LastEdited')->Nice();
                },
            ],
        ];

        if ($itemType === 'Pages') {
            // Page specific fields
            $columns['i18n_singular_name'] = _t(__CLASS__ . '.PageType', 'Page type');
            $columns['StageState'] = [
                'title' => _t(__CLASS__ . '.Stage', 'Stage'),
                'formatting' => function ($value, $item) {
                    // Stage only
                    if (!$item->isPublished()) {
                        return _t(__CLASS__ . '.Draft', 'Draft');
                    }

                    // Pending changes
                    if ($item->isModifiedOnDraft()) {
                        return _t(__CLASS__ . '.PublishedWithChanges', 'Published (with changes)');
                    }

                    // If on live and unmodified
                    return _t(__CLASS__ . '.Published', 'Published');
                },
            ];
            $columns['RelativeLink'] = _t(__CLASS__ . '.Link', 'Link');
            $columns['MetaDescription'] = [
                'title' => _t(__CLASS__ . '.MetaDescription', 'Description'),
                'printonly' => true,
            ];
        } else {
            // File specific fields
            $columns['FileType'] = [
                'title' => _t(__CLASS__ . '.FileType', 'File type'),
                'datasource' => function ($record) {
                    // Handle folders separately
                    if ($record instanceof Folder) {
                        return $record->i18n_singular_name();
                    }

                    return $record->getFileType();
                }
            ];
            $columns['Size'] = _t(__CLASS__ . '.Size', 'Size');
            $columns['Filename'] = _t(__CLASS__ . '.Directory', 'Directory');
        }

        $this->extend('updateColumns', $itemType, $columns);

        return $columns;
    }

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        Requirements::css('silverstripe/reports: client/dist/styles/sitewidecontentreport.css');
        $fields = parent::getCMSFields();

        $fields->push(HeaderField::create('FilesTitle', _t(__CLASS__ . '.Files', 'Files'), 3));
        $fields->push($this->getReportField('Files'));

        return $fields;
    }

    /**
     * Creates a GridField for pages and another one for files with different columns.
     * Grid fields have an export and a print button.
     *
     * @param string $itemType (i.e 'Pages' or 'Files')
     *
     * @return GridField
     */
    public function getReportField($itemType = 'Pages')
    {
        $params = isset($_REQUEST['filters']) ? $_REQUEST['filters'] : array();
        $items = $this->sourceRecords($params, null, null);

        $gridField = new GridFieldBasicContentReport('Report-' . $itemType, false, $items[$itemType]);

        $gridFieldConfig = GridFieldConfig::create()->addComponents(
            new GridFieldToolbarHeader(),
            new GridFieldSortableHeader(),
            new GridFieldDataColumns(),
            new GridFieldPaginator(),
            new GridFieldButtonRow('after'),
            $printButton = new GridFieldPrintButton('buttons-after-left'),
            $exportButton = $this->getExportButton()
        );

        $gridField->setConfig($gridFieldConfig);

        /* @var $columns GridFieldDataColumns */
        $columns = $gridField->getConfig()->getComponentByType(GridFieldDataColumns::class);

        $exportFields = [];
        $displayFields = [];
        $fieldCasting = [];
        $fieldFormatting = [];
        $dataFields = [];

        // Parse the column information
        foreach ($this->columns($itemType) as $source => $info) {
            if (is_string($info)) {
                $info = ['title' => $info];
            }

            if (isset($info['formatting'])) {
                $fieldFormatting[$source] = $info['formatting'];
            }
            if (isset($info['casting'])) {
                $fieldCasting[$source] = $info['casting'];
            }

            if (isset($info['link']) && $info['link']) {
                $fieldFormatting[$source] = function ($value, &$item) {
                    if ($item instanceof Page) {
                        return sprintf(
                            "<a href='%s'>%s</a>",
                            Controller::join_links(singleton(CMSPageEditController::class)->Link('show'), $item->ID),
                            $value
                        );
                    }

                    return sprintf(
                        '<a href="%s" target="_blank" rel="noopener">%s</a>',
                        Controller::join_links(
                            singleton(AssetAdmin::class)->Link('EditForm'),
                            'field/File/item',
                            $item->ID,
                            'edit'
                        ),
                        $value
                    );
                };
            }

            // Set custom datasource
            if (isset($info['datasource'])) {
                $dataFields[$source] = $info['datasource'];
            }

            // Set field name for export
            $fieldTitle = isset($info['title']) ? $info['title'] : $source;

            // If not print-only, then add to display list
            if (empty($info['printonly'])) {
                $displayFields[$source] = $fieldTitle;
            }

            // Assume that all displayed fields are printed also
            $exportFields[$source] = $fieldTitle;
        }

        // Set custom evaluated columns
        $gridField->addDataFields($dataFields);

        // Set visible fields
        $columns->setDisplayFields($displayFields);
        $columns->setFieldCasting($fieldCasting);
        $columns->setFieldFormatting($fieldFormatting);

        // Get print columns, and merge with print-only columns
        $printExportColumns = $this->getPrintExportColumns($gridField, $itemType, $exportFields);

        $printButton->setPrintColumns($printExportColumns);
        $exportButton->setExportColumns($printExportColumns);

        return $gridField;
    }

    /**
     * Returns the columns for the export and print functionality.
     *
     * @param GridField $gridField
     * @param string $itemType (i.e 'Pages' or 'Files')
     * @param array $exportColumns
     *
     * @return array
     */
    public function getPrintExportColumns($gridField, $itemType, $exportColumns)
    {
        // Swap RelativeLink for AbsoluteLink for export
        $exportColumns['AbsoluteLink'] = _t(__CLASS__ . '.Link', 'Link');
        unset($exportColumns['RelativeLink']);

        $this->extend('updatePrintExportColumns', $gridField, $itemType, $exportColumns);

        return $exportColumns;
    }

    /**
     * @return GridFieldComponent|GridFieldExportButton
     */
    protected function getExportButton()
    {
        $exportButton = new GridFieldExportButton('buttons-after-left');
        $this->extend('updateExportButton', $exportButton);
        return $exportButton;
    }
}
