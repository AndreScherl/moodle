<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package block_mbstpl
 * @copyright 2015 Janek Lasocki-Biczysko, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/utils.php');

/**
 * Test case for duplicating a course from a template
 * @group block_mbstpl
 */
class block_mbstpl_duplicatecourse extends advanced_testcase {

    public function test_duplicate_course() {

        global $USER;

        self::setAdminUser();
        $this->resetAfterTest(true);
        $mailsink = $this->redirectEmails();
        unset_config('noemailever');

        // Create a course and a template from which we'll be duplicating.
        $course = self::getDataGenerator()->create_course();
        $tpl = block_mbstpl_test_utils::create_template($course->id);

        // Create a category to duplicate to.
        $targetcategory = self::getDataGenerator()->create_category();
        $this->assertEquals(0, $targetcategory->get_courses_count());

        // Create a teacher role.
        $teacherrole = 'teacherrole'.random_string();
        $teacherroleid = create_role($teacherrole, $teacherrole, $teacherrole);
        set_config('teacherrole', $teacherroleid, 'block_mbstpl');

        // Duplicate the template into a course via task.
        $task = new \block_mbstpl\task\adhoc_deploy_secondary();
        $task->set_custom_data(array(
            'tplid' => $tpl->id,
            'requesterid' => $USER->id,
            'settings' => array(
                'restoreto' => 'cat',
                'tocat' => $targetcategory->id,
                'licence' => 'allrightsreserved'
            )
        ));
        $task->execute(true);

        // Verify a new course was created in the target category.
        $courses = $targetcategory->get_courses();
        $this->assertCount(1, $courses, "One course should have been added to this category");

        // Verify that this new course has a matching coursefromtpl entry.
        $duplicatedcourse = array_pop($courses);
        $coursefromtpl = \block_mbstpl\dataobj\coursefromtpl::fetch(array('courseid' => $duplicatedcourse->id));
        $this->assertNotEmpty($coursefromtpl, "A new coursefromtpl should have been created");
        $this->assertEquals($USER->id, $coursefromtpl->createdby);

        // Verify that an email was sent.
        $this->assertEquals($mailsink->count(), 1);

    }
}
