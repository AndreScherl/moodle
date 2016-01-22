@block @block_mbstpl
Feature: Duplicate a course
  In order to create a new couse from a template
  As a teacher
  I need to duplicate a template

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

    And I log in as "admin"
    # Make sure the default 'Template deployment category' is selected.
    And I navigate to "Course templating" node in "Site administration > Plugins > Blocks"
    And I press "Save changes"
    # Allow teachers to submit Course 1 as a template.
    And I am on homepage
    And I follow "Course 1"
    And I navigate to "Permissions" node in "Course administration > Users"
    And I override the system permissions of "Teacher" role with:
      | block/mbstpl:sendcoursetemplate | Allow    |
      | block/mbstpl:notanonymised      | Prohibit |
    And I log out

    # Teacher 1 submits Course 1 as a template.
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "forum" to section "1" and I fill the form with:
      | Forum name  | Test forum      |
      | Description | This is a forum |
    And I add a "forum" to section "1" and I fill the form with:
      | Forum name  | Second forum    |
      | Description | This is a forum |
    And I add a new discussion to "Second forum" forum with:
      | Subject | Second topic subject |
      | Message | Second topic message |
    And I navigate to "Send course template" node in "Course templating"
    And I click on "copyright" "checkbox"
    And I press "Send for reviewing"
    # Submit the second form with the activities list.
    And I press "Send for reviewing"
    And I trigger cron
    And I am on homepage
    And I log out

    # Admin publishes the Course 1 template.
    And I log in as "admin"
    And I follow "Course 1"
    And I should see "C1_Austauschkurs_1"
    And I add a new discussion to "Test forum" forum with:
      | Subject | Discussion topic subject      |
      | Message | Discussion topic message body |
    And I navigate to "Template feedback" node in "Course templating"
    And I press "Publish"

  @javascript
  Scenario: Duplicate a template with anonymised user data
    Given I expand "Course templating" node
    And I follow "Duplicate course for use"
    And I click on "#id_restoreto_cat" "css_element"
    And I press "Select Sections and Activities to Duplicate"
    And I click on "#backup-all-included" "css_element"
    And I click on "#backup-all-userdata" "css_element"
    And I press "Duplicate Course from Template"
    When I wait to be redirected
    Then I should see "C1_Austauschkurs_1_dpl_1"
    And I follow "Test forum"
    And I should see "Discussion topic subject" in the ".topic a" "css_element"
    # Admin user is not anonymised.
    And I should see "Admin User" in the ".author a" "css_element"

    When I follow "C1_Austauschkurs_1_dpl_1"
    And I follow "Second forum"
    Then I should see "Second topic subject" in the ".topic a" "css_element"
    # Teacher 1 is anonymised.
    And I should see "anonfirstname1 anonlastname1" in the ".author a" "css_element"


  @javascript
  Scenario: Duplicate a template without user data
    Given I expand "Course templating" node
    And I follow "Duplicate course for use"
    And I click on "#id_restoreto_cat" "css_element"
    And I press "Select Sections and Activities to Duplicate"
    And I click on "#backup-all-included" "css_element"
    And I press "Duplicate Course from Template"
    When I wait to be redirected
    Then I should see "C1_Austauschkurs_1_dpl_1"
    And I follow "Test forum"
    And I should see "(There are no discussion topics yet in this forum)"
