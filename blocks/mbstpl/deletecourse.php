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
 * Page for deleting a course. Needed for giving teachSHARE Course Reviewer the possibility to delete a course only in reviewing
 * process (no published courses).
 * 
 * @see moodledir\course\delete.php.
 * @package block_mbstpl
 * @copyright 2016 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__DIR__)) . '/config.php');
require_once($CFG->dirroot . '/course/lib.php');

$courseid = required_param('id', PARAM_INT); // Course ID.
$delete = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

require_login();

$template = new \block_mbstpl\dataobj\template(array('courseid' => $courseid), true);
if ($SITE->id == $course->id || !\block_mbstpl\perms::can_delete($template)) {
    // Can not delete frontpage or don't have permission to delete the course.
    print_error('cannotdeletecourse');
}

$categorycontext = context_coursecat::instance($course->category);
$categoryurl = new moodle_url('/course/index.php', array('categoryid' => $course->category));
$PAGE->set_url('/blocks/mbstpl/deletecourse.php', array('id' => $courseid));
$PAGE->set_context($categorycontext);
$PAGE->set_pagelayout('admin');
navigation_node::override_active_url($categoryurl);

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
    // We do this here because it spits out feedback as it goes.
    delete_course($course);
    echo $OUTPUT->heading( get_string("deletedcourse", "", $courseshortname) );
    // Update course count in categories.
    fix_course_sortorder();
    
    echo $OUTPUT->continue_button($categoryurl);
    echo $OUTPUT->footer();
    exit; // We must exit here!!!
}

$strdeletecheck = get_string("deletecheck", "", $courseshortname);
$strdeletecoursecheck = get_string("deletecoursecheck");
$message = "{$strdeletecoursecheck}<br /><br />{$coursefullname} ({$courseshortname})";

$continueurl = new moodle_url('/blocks/mbstpl/deletecourse.php', array('id' => $course->id, 'delete' => md5($course->timemodified)));
$courseurl = new moodle_url('/course/view.php', array('id' => $courseid));

$PAGE->navbar->add($strdeletecheck);
$PAGE->set_title("$SITE->shortname: $strdeletecheck");
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
echo $OUTPUT->confirm($message, $continueurl, $courseurl);
echo $OUTPUT->footer();
exit;
