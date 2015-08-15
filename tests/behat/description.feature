@local_recyclebin
Feature: Description of recycle bin and expiry
    As a teacher
    I want to know what the recycle bin will do and how long contents last in the bin
    So that I can better understand the tool

Scenario: Description should show when the recycle bin will clean up files.
    Given the following "courses" exist:
        | fullname | shortname |
        | Course 1 | C1 |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Page" to section "1" and I fill the form with:
      | Name                | Test page |
      | Description         | Test   |
      | Page content        | Test   |
    When I delete "Test page" activity
    And I follow "Recycle bin"
    # Default expiry is 0 (never).
    Then I should not see "Contents will be permanently deleted"
    # Test changing expiry to something else.
    When the following config values are set as admin:
        | expiry | 10 | local_recyclebin |
    And I reload the page
    Then I should see "Contents will be permanently deleted after 10 days"