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
 * Unit tests for mbs
 *
 * @package   local_mbs
 * @copyright 2017 Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class local_mbs_testcase extends advanced_testcase {

    public function notest_plugin_installed() {
        $config = get_config('core', 'local_mbs_mebis_sites');
        $this->assertNotFalse($config);
    }

    /**
     * Test for the Assign Teach Hack (see Gitlab for description).
     * https://gitlab.mebis.alp.dillingen.de/mebis-moodle/mbsmoodle/wikis/hack-assign-teacher
     *
     */
    public function test_assign_teacherhack() {
        global $CFG, $DB, $PAGE;

        require_once($CFG->dirroot . '/enrol/locallib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        // Generate users.
        $users = array();
        $users[0] = $generator->create_user();
        $users[1] = $generator->create_user();
        $users[2] = $generator->create_user();

        $teachroleshortname = \local_mbs\local\core_changes::$teacherroleshortname;
        $teacherroleid = \local_mbs\local\core_changes::get_roleid_by_shortname($teachroleshortname);

        // Check setup of role assignment capbilities, teacher should not be able to assign teacher.
        $cap = $DB->get_record('role_allow_assign', array('roleid' => $teacherroleid, 'allowassign' => $teacherroleid));
        $this->assertFalse($cap);

        // Create course.
        $course = $generator->create_course();

        $generator->enrol_user($users[0]->id, $course->id, $teachroleshortname);
        $generator->enrol_user($users[1]->id, $course->id, 'student');
        $generator->enrol_user($users[2]->id, $course->id, 'student');

        // Prepare $users[1] to receive teacherrole by setting preference.
        set_user_preference('mbs_allow_teacherrole', 1, $users[1]);

        $this->setUser($users[0]->id);

        $userbyids = array();
        foreach ($users as $user) {
            $userbyids[$user->id] = (array) $user;
        }

        // Adds parameters to given argument array for javascript.
        $arguments = array('courseId' => $course->id, 'userIds' => array_keys($userbyids));
        \local_mbs\local\core_changes::add_allowteacher_role($arguments);

        $this->assertContains($users[1]->id, $arguments['allowteacherrole']);
        $this->assertNotContains($users[2]->id, $arguments['allowteacherrole']);

        $manager = new course_enrolment_manager($PAGE, $course);
        \local_mbs\local\core_changes::add_assignableroles($manager, $userbyids);

        $this->assertContains($teacherroleid, array_keys($userbyids[$users[1]->id]['assignableroles']));
        $this->assertNotContains($teacherroleid, array_keys($userbyids[$users[2]->id]['assignableroles']));

        // Security-Checks  unassign_role_from_user, assign_role_to_user
        $success = $manager->assign_role_to_user($teacherroleid, $users[1]->id);
        $this->assertNotFalse($success);

        try {
            $throwserror = true;
            $success = $manager->assign_role_to_user($teacherroleid, $users[2]->id);
            $throwserror = false;
        } catch (moodle_exception $e) {

        }

        $this->assertTrue($throwserror);

        $success = $manager->unassign_role_from_user($users[1]->id, $teacherroleid);
        $this->assertNotFalse($success);

        try {
            $throwserror = true;
            $success = $manager->unassign_role_from_user($users[2]->id, $teacherroleid);
            $throwserror = false;
        } catch (moodle_exception $e) {

        }

        $this->assertTrue($throwserror);
    }

}
