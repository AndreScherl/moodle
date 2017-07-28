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
 * Code to delete a course utterly. 
 * 
 * This file is a customized copy of /course/delete.php.
 *
 * @package local_mbs
 * @copyright 2016 Franziska Hübler franziska.huebler@isb.bayern.de
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

$id = required_param('id', PARAM_INT); // Course ID.
$delete = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

require_login();

if ($SITE->id == $course->id || !can_delete_course($id)) {
    // Can not delete frontpage or don't have permission to delete the course.
    print_error('cannotdeletecourse');
}

$categorycontext = context_coursecat::instance($course->category);
$PAGE->set_url('/local/mbs/course_delete.php', array('id' => $id));
$PAGE->set_context($categorycontext);
$PAGE->set_pagelayout('admin');

$courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
$coursefullname = format_string($course->fullname, true, array('context' => $coursecontext));

// Check if we've got confirmation.
if ($delete === md5($course->timemodified)) {
    // We do - time to delete the course.
    require_sesskey();

    $strdeletingcourse = get_string("deletingcourse", "", $courseshortname);

    $PAGE->navbar->add($strdeletingcourse);
    $PAGE->set_title("$SITE->shortname: $strdeletingcourse");
    $PAGE->set_heading($SITE->fullname);

    echo $OUTPUT->header();
    echo $OUTPUT->heading($strdeletingcourse);
    // This might take a while. Raise the execution time limit.
    core_php_time_limit::raise();
    // We do this here because it spits out feedback as it goes.
    delete_course($course);
    echo $OUTPUT->heading( get_string("deletedcourse", "", $courseshortname) );
    // Update course count in categories.
    fix_course_sortorder();
    
    $dashboardurl = new moodle_url('/my');
    echo $OUTPUT->continue_button($dashboardurl);
    echo $OUTPUT->footer();
    exit; // We must exit here!!!
}

$strdeletecheck = get_string("deletecheck", "", $courseshortname);
$strdeletecoursecheck = get_string("deletecoursecheck");
$message = "{$strdeletecoursecheck}<br /><br />{$coursefullname} ({$courseshortname})";

$continueurl = new moodle_url('/local/mbs/course_delete.php', array('id' => $course->id, 'delete' => md5($course->timemodified)));
$continuebutton = new single_button($continueurl, get_string('delete'), 'post');

$viewcourseurl = new moodle_url('/course/view.php', array('id' => $course->id));
$coursebutton = new single_button($viewcourseurl, get_string('cancel'), 'get');

$PAGE->navbar->add($strdeletecheck);
$PAGE->set_title("$SITE->shortname: $strdeletecheck");
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
echo $OUTPUT->confirm($message, $continuebutton, $coursebutton);
echo $OUTPUT->footer();
exit;
