Feature: Site wide content report
  As a website user
  I want to use the site wide content report

  Background:

    Given a "page" "My page" has the "Content" "<p>My content</p>"
    And a "file" "test1.pdf"
    And the "group" "EDITOR" has permissions "Access to 'Pages' section" and "Access to 'Reports' section" and "Access to 'Files' section" and "FILE_EDIT_ALL"

  Scenario: Operate site wide content report
    When I am logged in as a member of "EDITOR" group
    And I go to "/admin/reports"
    And I follow "Site-wide content report"
    
    # Show all Pages
    Then I should see "My page"
    And I should see "my-page"

    # Show all files
    And I should see "test1.pdf"
    And I should see "Adobe Acrobat PDF file"

    # Click on a page to open it
    When I go to "/admin/reports"
    And I follow "Site-wide content report"
    When I follow "My page"
    Then I should see a ".tox-tinymce" element

    # Click on a file to open it
    When I go to "/admin/reports"
    And I follow "Site-wide content report"
    When I follow "test1" with javascript
    Then I should see a "#Form_fileEditForm" element
