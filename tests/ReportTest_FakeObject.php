<?php
namespace SilverStripe\Reports\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\CMSPreviewable;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Control\Controller;

class ReportTest_FakeObject extends DataObject implements CMSPreviewable, TestOnly
{

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
