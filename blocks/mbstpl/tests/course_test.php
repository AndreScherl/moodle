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

use block_mbstpl\dataobj\template,
    block_mbstpl\dataobj\coursefromtpl,
    block_mbstpl\course;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/utils.php');

/**
 * Unit tests for block_mbstpl\course
 * @group block_mbstpl
 */
class block_mbstpl_course_test extends advanced_testcase {

    /**
     * Test course deletion hook to ensure it removes template entries
     */
    public function test_course_deleted_hook_for_template() {

        $this->resetAfterTest(true);

        $coursefortemplate = $this->getDataGenerator()->create_course();
        $tpl = block_mbstpl_test_utils::create_template($coursefortemplate->id);

        $freshtpl = template::fetch(array('id' => $tpl->id));
        $this->assertNotNull($freshtpl);

        delete_course($coursefortemplate);

        $freshtpl = template::fetch(array('id' => $tpl->id));
        $this->assertEmpty($freshtpl, "Template should no longer exist");
    }

    /**
     * Test course deletion hook to ensure it removes coursefromtpl entries
     */
    public function test_course_deleted_hook_for_coursefromtpl() {

        $this->resetAfterTest(true);

        $courseforduplicated = $this->getDataGenerator()->create_course();
        $coursefromtpl = block_mbstpl_test_utils::create_coursefromtpl(1, $courseforduplicated->id);

        $coursefromtpl = coursefromtpl::fetch(array('id' => $coursefromtpl->id));
        $this->assertNotNull($coursefromtpl);

        delete_course($courseforduplicated);

        $coursefromtpl = coursefromtpl::fetch(array('id' => $coursefromtpl->id));
        $this->assertEmpty($coursefromtpl, "Coursefromtpl should no longer exist");
    }

    /**
     * Test get_courses_with_creators function
     */
    public function test_get_courses_with_creators() {

        global $USER;

        $this->resetAfterTest(true);

        self::setAdminUser();

        // create a template from which we'll be creating courses
        $templatecourse = self::getDataGenerator()->create_course();
        $tpl = block_mbstpl_test_utils::create_template($templatecourse->id, 1, $USER->id);

        // verify no courses have been created for this template
        $courses = course::get_courses_with_creators($tpl->id);
        $this->assertCount(0, $courses, "Should have no courses based on this template");

        // create a course from this template, and specify a creator
        $coursefromtplcourse = self::getDataGenerator()->create_course();
        $cid = $coursefromtplcourse->id;
        block_mbstpl_test_utils::create_coursefromtpl($tpl->id, $cid, $USER->id);

        $courses = course::get_courses_with_creators($tpl->id);
        $this->assertCount(1, $courses, "Should have one course from template");
        $this->assertEquals($coursefromtplcourse->fullname, $courses[$cid]->course_fullname);
        $this->assertEquals(fullname($USER), $courses[$cid]->course_creator_name);

        // create a course from this template, without a creator
        $coursefromtplcourse2 = self::getDataGenerator()->create_course();
        $cid2 = $coursefromtplcourse2->id;
        block_mbstpl_test_utils::create_coursefromtpl($tpl->id, $cid2);

        $courses = course::get_courses_with_creators($tpl->id);
        $this->assertCount(2, $courses, "Should have two courses from template");
        $this->assertEquals($coursefromtplcourse2->fullname, $courses[$cid2]->course_fullname);
        $this->assertEmpty($courses[$cid2]->course_creator_name);

    }
}
