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

global $PAGE, $USER, $CFG, $DB, $OUTPUT;

use \block_mbstpl AS mbst;

$courseid = required_param('course', PARAM_INT);

$thisurl = new moodle_url('/blocks/mbstpl/forrevision.php', array('course' => $courseid));
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('incourse');

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$courseurl = new moodle_url('/course/view.php', array('id' => $courseid));

require_login($courseid, false);
$coursecontext = context_course::instance($courseid);

$PAGE->set_context($coursecontext);
$pagetitle = get_string('forrevision', 'block_mbstpl');
$PAGE->set_title($pagetitle);

$template = new \block_mbstpl\dataobj\template(array('courseid' => $courseid), true);
if (!mbst\perms::can_sendrevision($template, $coursecontext)) {
    throw new moodle_exception('errorcannotviewfeedback', 'block_mbstpl');
}

$form = new mbst\form\forrevision(null, array('courseid' => $courseid));
if ($form->is_cancelled()) {
    $redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($redirecturl);
} else if ($data = $form->get_data()) {
    // Initiate duplication task.
    $task = new \block_mbstpl\task\adhoc_deploy_revision();
    $task->set_custom_data((object)array('templateid' => $template->id, 'requesterid' => $USER->id, 'reasons' => $data->reasons));
    \core\task\manager::queue_adhoc_task($task);
    mbst\course::archive($template);
    redirect($courseurl, get_string('forrevision', 'block_mbstpl'));
}
$renderer = mbst\course::get_renderer();

echo $OUTPUT->header();
echo html_writer::tag('h2', $pagetitle);
echo html_writer::tag('h3', $course->fullname);

$form->display();

echo $OUTPUT->footer();