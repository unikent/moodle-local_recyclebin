@local_recyclebin @javascript
Feature: Delete confirmation
    As a teacher
    I want to be prompted before I permanently delete something
    So that I do not make a mistake again

Background:
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
    And I delete "Test page" activity
    And I follow "Recycle bin"

Scenario: Confirm single delete
    When I click on "Delete" "link"
    Then I should see "Are you sure you want to delete the selected item(s) in the recycle bin?"
    And I press "No"
    And I should see "Test page"
    When I click on "Delete" "link"
    And I press "Yes"
    And I wait to be redirected
    Then I should see "There are no items in the recycle bin."

Scenario: Confirm empty bin
    When I click on "Empty recycle bin" "link"
    Then I should see "Are you sure you want to delete the selected item(s) in the recycle bin?"
    And I press "No"
    And I should see "Test page"
    When I click on "Empty recycle bin" "link"
    And I press "Yes"
    And I wait to be redirected
    Then I should see "There are no items in the recycle bin."
