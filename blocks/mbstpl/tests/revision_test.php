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
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/utils.php');

/**
 * Test case for sending a course for revision
 * @group block_mbstpl
 */
class block_mbstpl_revision_test extends advanced_testcase {

    public function test_send_revision() {

        self::setAdminUser();
        $this->resetAfterTest(true);
        $mailsink = $this->redirectEmails();
        unset_config('noemailever');

        // Create a course and a template from which we'll be duplicating.
        $course = self::getDataGenerator()->create_course();
        $tpl = block_mbstpl_test_utils::create_template($course->id);

        // Give the template a reviewer.
        $reviewer = $this->getDataGenerator()->create_user();
        $tpl->reviewerid = $reviewer->id;
        $tpl->update();

        // Create a category to duplicate to.
        $targetcategory = self::getDataGenerator()->create_category();
        set_config('deploycat', $targetcategory->id, 'block_mbstpl');
        $this->assertEquals(0, $targetcategory->get_courses_count());

        // Create a teacher role.
        $teacherrole = 'teacherrole'.random_string();
        $teacherroleid = create_role($teacherrole, $teacherrole, $teacherrole);
        set_config('teacherrole', $teacherroleid, 'block_mbstpl');

        // Create the revision request via task.
        $task = new \block_mbstpl\task\adhoc_deploy_revision();
        $task->set_custom_data(array(
            'templateid' => $tpl->id,
            'reasons' => 'reasons',
        ));
        $task->execute(true);

        // Verify a new course was created in the target category.
        $courses = $targetcategory->get_courses();
        $this->assertCount(1, $courses, "One course should have been added to this category");

        // Verify that this new course has a matching template entry.
        $duplicatedcourse = array_pop($courses);
        $revtpl = \block_mbstpl\dataobj\template::fetch(array('courseid' => $duplicatedcourse->id));
        $this->assertNotEmpty($revtpl, "A new template should have been created");
        $this->assertEquals($revtpl->status, $revtpl::STATUS_UNDER_REVISION, "Template for revision should have Revision status");

        // Verify that an email was sent.
        $this->assertEquals(1, $mailsink->count(), "Email count incorrect");
    }
}
