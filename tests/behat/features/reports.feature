Feature: CMS reports
  As a website user
  I want to create and link taxonomies

  Background:
    Given the "group" "EDITOR group" has permissions "CMS_ACCESS_LeftAndMain" and "TAXONOMYTERM_CREATE" and "TAXONOMYTERM_EDIT" and "TAXONOMYTERM_DELETE"

    # TODO: delete
    Given I take a screenshot after every step
    Given I dump the rendered HTML after every step


@sboyd
  Scenario: Operate reports
    Given I am logged in with "EDITOR" permissions
    When I go to "/admin/reports"
