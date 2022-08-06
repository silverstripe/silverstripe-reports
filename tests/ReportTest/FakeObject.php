<?php

namespace SilverStripe\Reports\Tests\ReportTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Admin\Previewable;
use SilverStripe\ORM\DataObject;

class FakeObject extends DataObject implements Previewable, TestOnly
{
    private static $table_name = 'ReportTest_FakeObject';

    private static $db = array(
        'Title' => 'Varchar'
    );

    /**
     * @return String Absolute URL to the end-user view for this record.
     * Example: http://mysite.com/my-record
     */
    public function Link()
    {
        return Controller::join_links('dummy-link', $this->ID);
    }

    public function CMSEditLink()
    {
        return Controller::join_links('dummy-edit-link', $this->ID);
    }

    public function PreviewLink($action = null)
    {
        return false;
    }

    public function getMimeType()
    {
        return 'text/html';
    }
}
