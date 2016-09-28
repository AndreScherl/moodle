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
        global $DB, $CFG, $USER;

        // Set up.
        $this->resetAfterTest(true);
        $mailsink = $this->redirectEmails();
        $mailcount = 0;
        unset_config('noemailever');

        // Users and courses.
        $reviewer = $this->getDataGenerator()->create_user();
        $author = $this->getDataGenerator()->create_user(array('firstname' => 'test', 'lastname' => 'author'));
        set_config('authorrole', 5, 'block_mbstpl');

        $enrol = get_config('core', 'enrol_plugins_enabled');
        $enabled = explode(',', $enrol);
        $enabled[] = 'mbstplaenrl';
        $enrol = implode(',', $enabled);
        set_config('enrol_plugins_enabled', $enrol);

        set_config('teacherrole', 3, 'block_mbstpl');

        $student1 = $this->getDataGenerator()->create_user(array('firstname' => 'student', 'lastname' => '1'));
        $student2 = $this->getDataGenerator()->create_user(array('firstname' => 'student', 'lastname' => '2'));
        $student3 = $this->getDataGenerator()->create_user(array('firstname' => 'student', 'lastname' => '3'));

        $origcourse = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('assign', array('course' => $origcourse->id));
        $forum1 = $this->getDataGenerator()->create_module('forum', array('course' => $origcourse->id));

        $forumgenerator = self::getDataGenerator()->get_plugin_generator('mod_forum');

        // Add a discussion.
        $record = new stdClass();
        $record->course = $origcourse->id;
        $record->forum = $forum1->id;
        $record->userid = $student1->id;
        $discussion = $forumgenerator->create_discussion($record);

        // Do two posts in forum.
        $record = new stdClass();
        $record->discussion = $discussion->id;
        $record->userid = $student1->id;
        $forumgenerator->create_post($record);

        $record->userid = $student2->id;
        $forumgenerator->create_post($record);

        $posts = $DB->get_records('forum_posts', array('discussion' => $discussion->id));

        $this->assertEquals(3, count($posts));

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
        $this->assertCount(2, $mods, 'Expecting 2 modules in restored template');

        // Expecting a forum module with one discussion and two posts and anonymous users.
        $discussion = $DB->get_record('forum_discussions', array('course' => $courseid));
        $posts = $DB->get_records('forum_posts', array('discussion' => $discussion->id));
        $this->assertEquals(3, count($posts));

        foreach ($posts as $post) {
            $user = $DB->get_record('user', array('id' => $post->userid));
            $this->assertContains('anon', $user->firstname);
        }

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

        $DB->set_field('block_mbstpl_meta', 'license', 'public', array('templateid' => $template->id));

        $this->assertEquals($template->reviewerid, $reviewer->id);
        $this->assertEquals( ++$mailcount, $mailsink->count());

        // Publish by task.
        $this->setAdminUser();
        $deploypublish = new \block_mbstpl\task\adhoc_deploy_publish();
        $deploypublish->set_custom_data($template);
        $deploypublish->execute(true);
        $this->assertEquals( ++$mailcount, $mailsink->count());

        // Check, whether template is published.
        $course = $DB->get_record('course', array('id' => $courseid));
        $this->assertEquals(1, $course->visible);

        $template = new \block_mbstpl\dataobj\template(array('courseid' => $courseid), true);

        $this->assertEquals(\block_mbstpl\dataobj\template::STATUS_PUBLISHED, $template->status);

        // Expecting a forum module with one discussion and two posts and anonymous users.
        $discussion = $DB->get_record('forum_discussions', array('course' => $courseid));
        $posts = $DB->get_records('forum_posts', array('discussion' => $discussion->id));
        $this->assertEquals(3, count($posts));

        foreach ($posts as $post) {
            $user = $DB->get_record('user', array('id' => $post->userid));
            $this->assertContains('anon', $user->firstname);
        }

        // Now do a reset task, which should not change the discusion id, because
        // there were no changes in the course.
        \block_mbstpl\reset_course_userdata::reset_course_from_template($courseid);
        $discussionafter = $DB->get_record('forum_discussions', array('course' => $courseid));
        $this->assertEquals($discussion->id, $discussionafter->id);

        // Autoenrol user and do an new post.
        require_once($CFG->dirroot . '/enrol/mbstplaenrl/lib.php');
        $enrolplugin = enrol_get_plugin('mbstplaenrl');

        $instance = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'mbstplaenrl'), '*', MUST_EXIST);
        $enrolplugin->enrol_user($instance, $student3->id, 5, time(), 0);

        // Do a new post...
        $record = new stdClass();
        $record->discussion = $discussion->id;
        $record->userid = $student3->id;
        $forumgenerator->create_post($record);

        $posts = $DB->get_records('forum_posts', array('discussion' => $discussion->id));
        $this->assertEquals(4, count($posts));

        // ... and reset the course.
        \enrol_mbs\reset_course_userdata::reset_course_from_template($courseid);
        $discussionafter = $DB->get_record('forum_discussions', array('course' => $courseid));
        $this->assertNotEquals($discussion->id, $discussionafter->id);

        // Expecting a forum module with one discussion and two posts and anonymous users.
        $posts = $DB->get_records('forum_posts', array('discussion' => $discussionafter->id));
        $this->assertEquals(3, count($posts));

        foreach ($posts as $post) {
            $user = $DB->get_record('user', array('id' => $post->userid));
            $this->assertContains('anon', $user->firstname);
        }

        // Duplicate the course for use.
        // Initiate deployment task.
        $backupsettings = array(
            'forum_'.$discussionafter->id.'_included' => 1
        );

        $sections = $DB->get_records('course_sections', array('course' => $courseid));

        foreach ($sections as $sectionid => $unused) {
           $backupsettings["section_{$sectionid}_included"] = 1;
        }

        $taskdata = (object) array(
                'tplid' => $template->id,
                'settings' => array('tocat' => 1, 'backupsettings' => $backupsettings, 'licence' => ''),
                'requesterid' => $USER->id
        );

        // We do a deployment by first resetting the course.
        $deployment = new \block_mbstpl\task\adhoc_deploy_dupcrs();
        $deployment->set_custom_data($taskdata);
        $deployment->execute(true);

        // Delete User and keep firstname and lastname.
        delete_user($author);
        $deleteduser = $DB->get_record('block_mbstpl_userdeleted', array('userid' => $author->id));
        $this->assertNotEmpty($deleteduser);

        // Check whether the display name is read correctly.
        $qidlist = \block_mbstpl\questman\manager::get_searchqs();
        $questions = \block_mbstpl\questman\manager::get_questsions_in_order($qidlist);

        $search = new mbst\tplsearch($questions, array());
        $result = $search->get_search_result(0, 0);

        $item = reset($result->courses);
        $this->assertNotEmpty($item->deleteduserid);
    }

}
