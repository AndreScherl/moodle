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

use block_mbstpl\user;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/utils.php');

/**
 * Unit tests for block_mbstpl\user
 * @group block_mbstpl
 */
class block_mbstpl_user_test extends advanced_testcase {

    private $mailsink;
    private $course;
    private $user;

    public function setUp() {
        $this->resetAfterTest(true);
        $this->mailsink = $this->redirectEmails();
        unset_config('noemailever');

        $this->course = self::getDataGenerator()->create_course();
        $this->user = self::getDataGenerator()->create_user();
    }

    private function create_role($configname) {
        $rolename = $configname . random_string();
        $roleid = create_role($rolename, $rolename, $rolename);
        set_config($configname, $roleid, 'block_mbstpl');
        return $roleid;
    }



    private function assertHasRole($roleid, $message = null) {
        global $DB;

        $context = context_course::instance($this->course->id);

        $sql = "SELECT 1
                FROM {role_assignments}
                WHERE userid = :userid AND roleid = :roleid AND contextid = :contextid";

        $hasrole = $DB->record_exists_sql($sql, array(
            'userid' => $this->user->id,
            'roleid' => $roleid,
            'contextid' => $context->id));

        $this->assertTrue($hasrole, $message);
    }

    public function test_enrol_author() {

        $roleid = $this->create_role('authorrole');

        user::enrol_author($this->course->id, $this->user->id);
        $this->assertHasRole($roleid, "Should be enrolled as an author");

        $messages = $this->mailsink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals($this->user->email, $messages[0]->to);
        $this->assertEquals(get_string('emailassignedauthor_subj', 'block_mbstpl'), $messages[0]->subject);
    }

    public function test_enrol_reviewer() {

        $roleid = $this->create_role('reviewerrole');

        user::enrol_reviewer($this->course->id, $this->user->id);
        $this->assertHasRole($roleid, "Should be enrolled as a reviewer");

        $messages = $this->mailsink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals($this->user->email, $messages[0]->to);
        $this->assertEquals(get_string('emailassignedreviewer_subj', 'block_mbstpl'), $messages[0]->subject);
    }

    public function test_enrol_teacher() {

        $roleid = $this->create_role('teacherrole');

        user::enrol_teacher($this->course->id, $this->user->id);
        $this->assertHasRole($roleid, "Should be enrolled as a teacher");

        $messages = $this->mailsink->get_messages();
        $this->assertCount(0, $messages, "No emails should get sent");
    }
}
