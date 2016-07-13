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

use \block_mbstpl AS mbst;
use block_mbstpl\backup;

/**
 * Test case for deploying an original backup to template.
 * @group block_mbstpl
 */
class mbstpl_deploy_test extends advanced_testcase {

    public function test_deploytemplate() {
        global $DB;

        // Set up.
        $this->resetAfterTest(true);
        $mailsink = $this->redirectEmails();
        $mailcount = 0;
        unset_config('noemailever');

        // Users and courses.
        $reviewer = $this->getDataGenerator()->create_user();
        $author = $this->getDataGenerator()->create_user(array('firstname' => 'test', 'lastname' => 'author'));
        set_config('authorrole', 5, 'block_mbstpl');

        $origcourse = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('assign', array('course' => $origcourse->id));
        $deploycat = $this->getDataGenerator()->create_category(array('name' => 'deployhere'));
        set_config('deploycat', $deploycat->id, 'block_mbstpl');

        // Create backup.
        $activeform = mbst\questman\manager::get_active_qform();
        $backupdata = array(
            'origcourseid' => $origcourse->id,
            'creatorid' => $author->id,
            'qformid' => $activeform->id,
            'incluserdata' => 1,
        );
        $backup = new mbst\dataobj\backup($backupdata);
        $backup->insert();
        $this->assertNotEmpty($backup->id);

        // Manually execute task which creates template etc.
        $task = new \block_mbstpl\task\adhoc_deploy_primary();
        $task->set_custom_data(array('id' => $backup->id));
        $task->execute(true);
        $courseid = $task->get_courseid();

        $this->assertNotEmpty($courseid);
        $template = new mbst\dataobj\template(array('courseid' => $courseid), true);
        $this->assertNotEmpty($template->id);
        $mods = get_course_mods($courseid);
        $this->assertCount(1, $mods, 'Expecting 1 module in restored template');
        // Send email to manager and author (= 2 Mails).
        $mailcount = $mailcount + 2;
        $this->assertEquals($mailcount, $mailsink->count(), "An email should have been sent after the template course was created");

        // Roles and capabilities.
        $systemcontext = context_system::instance();
        $deploycatcontext = context_coursecat::instance($deploycat->id);
        $reviewrolename = 'reviewrole' . random_string();
        $reviewroleid = create_role($reviewrolename, $reviewrolename, $reviewrolename);
        assign_capability('block/mbstpl:coursetemplatereview', CAP_ALLOW, $reviewroleid, $systemcontext->id);
        set_config('reviewerrole', $reviewroleid, 'block_mbstpl');
        role_assign($reviewroleid, $reviewer->id, $deploycatcontext->id);

        // Assign reviewer.
        mbst\course::assign_reviewer($template, $reviewer->id);
        $template = new mbst\dataobj\template(array('courseid' => $courseid), true);
        $this->assertEquals($template->reviewerid, $reviewer->id);
        $this->assertEquals(++$mailcount, $mailsink->count());

        // Publish.
        $this->setUser($reviewer);
        mbst\course::publish($template);
        $this->assertEquals(++$mailcount, $mailsink->count());

        // Delete User and keep firstname and lastname.
        delete_user($author);
        $deleteduser = $DB->get_record('block_mbstpl_userdeleted', array('userid' => $author->id));
        $this->assertNotEmpty($deleteduser);

        // Check whether the display name is read correctly.
        $qidlist = \block_mbstpl\questman\manager::get_searchqs();
        $questions = \block_mbstpl\questman\manager::get_questsions_in_order($qidlist);

        $search = new mbst\search($questions, array());
        $result = $search->get_search_result(0, 0);

        $item = reset($result->courses);
        $this->assertNotEmpty($item->deleteduserid);
    }

}
