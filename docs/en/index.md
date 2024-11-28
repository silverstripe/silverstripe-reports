# Included reports

This module provides a number of reports that can be used to provide insights into the content and structure of a Silverstripe site. This document provides a guide to some of these reports, and how to customise them.

## Table of contents

- [External broken links report](#external-broken-links)
- [Site-wide content report](#site-wide-content)

## External broken links report(#external-broken-links)

### Features

* Add external links to broken links reports
* Add a task to track external broken links

### Report

A new report is added called 'External Broken links report'. When viewing this report, a user may press
the "Create new report" button which will trigger an ajax request to initiate a report run.

In this initial ajax request this module will do one of two things, depending on which modules are included:

* If the queuedjobs module is installed, a new queued job will be initiated. The queuedjobs module will then
  manage the progress of the task.
* If the queuedjobs module is absent, then the controller will fallback to running a buildtask in the background.
  This is less robust, as a failure or error during this process will abort the run.

In either case, the background task will loop over every page in the system, inspecting all external urls and
checking the status code returned by requesting each one. If a URL returns a response code that is considered
"broken" (defined as < 200 or > 302) then the `ss-broken` css class will be assigned to that url, and
a line item will be added to the report. If a previously broken link has been corrected or fixed, then
this class is removed.

In the actual report generated the user can click on any broken link item to either view the link in their browser,
or edit the containing page in the CMS.

While a report is running the current status of this report will be displayed on the report details page, along
with the status. The user may leave this page and return to it later to view the ongoing status of this report.

Any subsequent report may not be generated until a prior report has completed.

### Dev task

Run `sake tasks:CheckExternalLinksTask` to check your site for external
broken links.

### Queued job

If you have the queuedjobs module installed you can set the task to be run every so often.

### Whitelisting codes

If you want to ignore or whitelist certain HTTP codes this can be setup via `ignore_codes` in the config.yml
file in `mysite/_config`:

```yml
SilverStripe\Reports\ExternalLinks\Tasks\CheckExternalLinksTask:
  ignore_codes:
    - 401
    - 403
    - 501
```

### Follow 301 redirects

You may want to follow a redirected URL a example of this would be redirecting from http to https
can give you a false poitive as the http code of 301 will be returned which will be classed
as a working link.

To allow redirects to be followed setup the following config in your config.yml

```yaml
# Follow 301 redirects
SilverStripe\Reports\ExternalLinks\Tasks\CurlLinkChecker:
  follow_location: 1
```

### Bypass cache

By default the task will attempt to cache any results the cache can be bypassed with the
following config in config.yml.

```yaml
# Bypass SS_Cache
SilverStripe\Reports\ExternalLinks\Tasks\CurlLinkChecker::
  bypass_cache: 1
```

### Headers

You may want to set headers to be sent with the CURL request (eg: user-agent) to avoid website rejecting the request thinking it is a bot.
You can set them with the following config in config.yml.

```yaml
# Headers
SilverStripe\Reports\ExternalLinks\Tasks\CurlLinkChecker:
  headers:
    - 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:53.0) Gecko/20100101 Firefox/53.0'
    - 'accept-encoding: gzip, deflate, br'
    - 'referer: https://www.domain.com/'
    - 'sec-fetch-mode: navigate'
    ...
```

## Site-wide content report(#site-wide-content)

### Customising the report

In order to customise the columns included in a report you can build a custom extension and apply it to the
SitewideContentReport as necessary.

The built in `SitewideContentTaxonomy` extension, for instance, adds a custom columns extracted from the `silverstripe/taxonomy` modules, and can be used as a base for developing further extensions:

For instance, in order to add a new Page field to the report you could add an extension similar to the below:

```php
    use SilverStripe\Core\Extension;
    use SilverStripe\Reports\SiteWideContentReport\Reports\SiteWideContentReport;

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

### Performance considerations

#### Large data sets

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
SilverStripe\Reports\SitewideContentReport\Reports\SitewideContentReport:
  extensions:
    - SitewideContentReportQueuedExportExtension
```

