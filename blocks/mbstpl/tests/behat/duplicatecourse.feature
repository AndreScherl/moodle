@block @block_mbstpl
Feature: Duplicate a course
	In order to create a new couse from a template
	As a teacher
	I need to duplicate a template

	Background:
		Given the following "users" exist:
			| username | firstname | lastname | email |
			| teacher1 | Teacher | 1 | teacher1@asd.com |
		And I log in as "admin"
		And I expand "Site administration" node
		And I expand "Plugins" node
		And I expand "Blocks" node
		And I follow "Course templating"
		And I press "Save changes"
		And I create a course with:
			| Course full name | Course 1 |
			| Course short name | C1 |
		And I enrol "Teacher 1" user as "Teacher"
		And I follow "Course 1"
		And I expand "Users" node
		And I follow "Permissions"
		And I override the system permissions of "Teacher" role with:
			| block/mbstpl:sendcoursetemplate | Allow |
		And I log out
		And I log in as "teacher1"
		And I follow "Course 1"
		And I expand "Course templating" node
		And I follow "Send course template"
		And I click on "copyright" "checkbox"
		And I press "Send for reviewing"
		And I log out
		And I log in as "admin"
		And I trigger cron
		And I am on homepage
		And I follow "Course 1"
		And I should see "C1_musterkurs_1"
		And I follow "News forum"
		And I press "Add a new topic"
		And I set the field "Subject" to "Discussion topic subject"
		And I set the field "Message" to "Discussion topic message body"
		And I press "Post to forum"
		And I wait to be redirected
		And I follow "Course 1"
		And I expand "Course templating" node
		And I follow "Template feedback"
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
		Then I should see "C1_musterkurs_1_dpl_1"
		And I follow "News forum"
		And I should see "Discussion topic subject" in the ".topic a" "css_element"
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
		Then I should see "C1_musterkurs_1_dpl_1"
		And I follow "News forum"
		And I should see "(No news has been posted yet)"
