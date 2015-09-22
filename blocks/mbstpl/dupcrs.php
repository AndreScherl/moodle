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


require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->libdir . '/coursecatlib.php');

global $PAGE, $USER, $CFG, $DB, $OUTPUT;

use \block_mbstpl AS mbst;

$thisurl = new moodle_url('/blocks/mbstpl/dupcrs.php');
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');
$PAGE->add_body_class('path-backup');

$courseid = required_param('course', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$template = new mbst\dataobj\template(array('courseid' => $courseid), true, MUST_EXIST);

require_login($courseid, false);
$coursecontext = context_course::instance($courseid);

$PAGE->set_context($coursecontext);
$pagetitle = get_string('duplcourseforuse', 'block_mbstpl');
$PAGE->set_title($pagetitle);
if (!mbst\perms::can_coursefromtpl($template, $coursecontext)) {
    throw new moodle_exception('errorcannotdupcrs', 'block_mbstpl');
}

// Load allowed courses and categories.
$catsearch = new restore_category_search();
$cats = $catsearch->get_results();
$coursesearch = new restore_course_search(array(), $course->id);
$courses = $coursesearch->get_results();
if (empty($cats) && empty($courses)) {
    throw new moodle_exception('errornowheretorestore', 'block_mbstpl');
}

$step = optional_param('step', 1, PARAM_INT);
$creator = mbst\course::get_creators($template->id);
$customdata = array(
    'course' => $course,
    'cats' => $cats,
    'courses' => $courses,
    'creator' => $creator,
    'step' => $step
);
$form = new mbst\form\dupcrs(null, $customdata);
$redirurl = new moodle_url('/course/view.php', array('id' => $courseid));
if ($form->is_cancelled()) {
    redirect($redirurl);
} else if ($form->get_data() && optional_param('doduplicate', 0, PARAM_RAW)) {

    // Initiate deployment task.

    $taskdata = (object)array(
        'tplid' => $template->id,
        'settings' => $form->get_task_settings(),
        'requesterid' => $USER->id,
    );
    $deployment = new \block_mbstpl\task\adhoc_deploy_secondary();
    $deployment->set_custom_data($taskdata);

    if (get_config('block_mbstpl', 'delayedrestore')) {
        \core\task\manager::queue_adhoc_task($deployment);
        redirect($redirurl, get_string('redirectdupcrsmsg', 'block_mbstpl'), 5);
    } else {
        $deployment->execute(true);
        $newcourseurl = new moodle_url('/course/view.php', array('id' => $deployment->get_courseid()));
        redirect($newcourseurl, get_string('redirectdupcrsmsg_done', 'block_mbstpl'), 5);
    }
}
echo $OUTPUT->header();

echo html_writer::tag('h2', $pagetitle);
$form->display();
echo $OUTPUT->footer();
