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

class mbstpl_deploy_test extends advanced_testcase {
    public function test_deploytemplate() {

        // Set up.
        $this->resetAfterTest(true);
        $mailsink = $this->redirectEmails();
        $mailcount = 0;
        unset_config('noemailever');

        // Users and courses.
        $reviewer = $this->getDataGenerator()->create_user();
        $author = $this->getDataGenerator()->create_user();
        $origcourse = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('assign', array('course' => $origcourse->id));
        $deploycat = $this->getDataGenerator()->create_category(array('name' => 'deployhere'));
        set_config('deploycat', $deploycat->id, 'block_mbstpl');

        // Create backup and template.
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
        \block_mbstpl\course::backup_template($backup);
        $courseid = \block_mbstpl\course::restore_template($backup);
        $this->assertNotEmpty($courseid);
        $template = new mbst\dataobj\template(array('courseid' => $courseid), true);
        $this->assertNotEmpty($template->id);
        $mods = get_course_mods($courseid);
        $this->assertCount(1, $mods, 'Expecting 1 module in restored template');

        // Roles and capabilities.
        $systemcontext = context_system::instance();
        $deploycatcontext = context_coursecat::instance($deploycat->id);
        $reviewrolename = 'reviewrole'.random_string();
        $reviewroleid = create_role($reviewrolename, $reviewrolename, $reviewrolename);
        assign_capability('block/mbstpl:coursetemplatereview', CAP_ALLOW, $reviewroleid, $systemcontext->id);
        set_config('reviewerrole', $reviewroleid, 'block_mbstpl');
        role_assign($reviewroleid, $reviewer->id, $deploycatcontext->id);

        // Assign reviewer.
        mbst\course::assign_reviewer($courseid, $reviewer->id);
        $template = new mbst\dataobj\template(array('courseid' => $courseid), true);
        $this->assertEquals($template->reviewerid, $reviewer->id);
        $this->assertEquals($mailsink->count(), ++$mailcount);

        // Publish.
        $this->setUser($reviewer);
        mbst\course::publish($template);
        $this->assertEquals($mailsink->count(), ++$mailcount);
    }
}