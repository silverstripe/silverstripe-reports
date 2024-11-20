# Reports

[![CI](https://github.com/silverstripe/silverstripe-reports/actions/workflows/ci.yml/badge.svg)](https://github.com/silverstripe/silverstripe-reports/actions/workflows/ci.yml)
[![Silverstripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)

## Installation

```sh
composer require silverstripe/reports
```

## Introduction

This module contains the API's for building Reports that are displayed in the
Silverstripe backend.

There are also a few CMS reports that comes out of the box:
- A "Users, Groups and Permissions" report allowing administrators to get a quick overview of who has access to the CMS.
- A "Site-wide content report" report allowing CMS users to get a quick overview of content across the site.

## Troubleshooting

The reports section will not show up in the CMS if:

 * There are no reports to show
 * The logged in user does not have permission to view any reports

For large datasets, the reports section may take a long time to load, since each report is getting a count of the items it contains to display next to the title.

To mitigate this issue, there is a cap on the number of items that will be counted per report. This is set at 10,000 items by default, but can be configured using the `limit_count_in_overview` configuration variable. Setting this to `null` will result in showing the actual count regardless of how many items there are.

```yml
SilverStripe\Reports\Report:
  limit_count_in_overview: 500
```
Note that some reports may have overridden the `getCount` method, and for those reports this may not apply.

## Customising the "Site-wide content report"

In order to customise the columns included in a report you can build a custom extension and apply it to the
SitewideContentReport as necessary.

The built in `SitewideContentTaxonomy` extension, for instance, adds a custom columns extracted from the `silverstripe/taxonomy` modules, and can be used as a base for developing further extensions:

For instance, in order to add a new Page field to the report you could add an extension similar to the below:

```php
    use SilverStripe\Core\Extension;
    use SilverStripe\Reports\SiteWideContentReport\SiteWideContentReport;

    class MyReportExtension extends Extension {
        protected function updateColumns($itemType, &$columns) {
            if($itemType !== 'Pages') {
                return;
            }
            $columns["Price"] = [
                "title" => _t(SitewideContentReport::class . ".Price", "Price"),
                "formatting" => function ($value, $item) use ($mainSiteLabel) {
                    return number_format($value, 2, '.', ',');
                },
            ];
        }
    }
```

The `$columns` array can have any number of items added, where the key of the array represents the
field name to be included.

Each item can be either a literal string (which will be used as the title), or an array that may contain
the following key => value pairs:

 * `title`: The title to use for the column header
 * `format`: A method callback which will take the raw value and original object, and return a formatted
    string.
 * `datasource`: If the value for this column isn't a direct field on the original object, a custom callback
   can be set here. Unlike `format` this callback will only take a single parameter, the original object.
 * `printonly`: Set to true if this column is only visible on the print or export-to-csv views.
 * `casting`: Specify a field type (e.g. `Text` or `Int`) in order to assist with field casting. This is not
    necessary if `formatting` is used.

## Performance considerations

### Large data sets

If your project has a large number of pages or files, you may experience server timeouts while trying to export
this report to CSV. To avoid this issue, you can either increase your server timeout limit, or you can install
and configure the [silverstripe/gridfieldqueuedexport module](https://github.com/silverstripe/silverstripe-gridfieldqueuedexport)
which allows for CSV generation to offloaded to a queued job in the background.

An example of configuring this module in your project:

```php
use SilverStripe\Core\Extension;
use SilverStripe\Forms\GridField\GridFieldComponent;
use SilverStripe\GridfieldQueuedExport\Forms\GridFieldQueuedExportButton;

class SitewideContentReportQueuedExportExtension extends Extension
{
    protected function updateExportButton(GridFieldComponent &$exportButton)
    {
        $exportButton = new GridFieldQueuedExportButton('buttons-after-left');
    }
}
```

Apply the example Extension above with YAML configuration in your project:

```yaml
---
Name: queuedsitewidecontentreport
---
SilverStripe\Reports\SitewideContentReport\SitewideContentReport:
  extensions:
    - SitewideContentReportQueuedExportExtension
```

## Links ##

 * [License](./LICENSE)
