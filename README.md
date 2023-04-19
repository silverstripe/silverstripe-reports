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

## Links ##

 * [License](./LICENSE)
