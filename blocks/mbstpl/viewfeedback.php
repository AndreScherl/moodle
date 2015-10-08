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

$thisurl = new moodle_url('/blocks/mbstpl/viewfeedback.php', array('course' => $courseid));
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$courseurl = new moodle_url('/course/view.php', array('id' => $courseid));

require_login($courseid, false);
$coursecontext = context_course::instance($courseid);

$template = new \block_mbstpl\dataobj\template(array('courseid' => $courseid), true);

$PAGE->set_context($coursecontext);
$pagetitle = get_string('templatefeedback', 'block_mbstpl');
$PAGE->set_title($pagetitle);
if (!mbst\perms::can_viewfeedback($template, $coursecontext)) {
    throw new moodle_exception('errorcannotviewfeedback', 'block_mbstpl');
}

$do = optional_param('do', '', PARAM_TEXT);
if ($do == 'publish' && mbst\course::publish($template)) {
    $template->status = $template::STATUS_PUBLISHED;
    $template->update();
    redirect($courseurl);
}
if ($do == 'archive' && mbst\perms::can_archive($template)) {
    $template->status = $template::STATUS_ARCHIVED;
    $template->update();
    redirect($courseurl);
}

$isreviewer = $template->reviewerid == $USER->id;
$isauthor = $template->authorid == $USER->id;
$cansendfeedback = $isreviewer || $isauthor;
if ($cansendfeedback) {
    $customdata = array('isreviewr' => $isreviewer, 'courseid' => $courseid);
    $feedbackform = new \block_mbstpl\form\feedback(null, $customdata);
    if ($data = $feedbackform->get_data()) {
        $newstatus = $isreviewer ? $template::STATUS_UNDER_REVISION : $template::STATUS_UNDER_REVIEW;
        mbst\course::set_feedback($template, $data->feedback, $newstatus);
		redirect($courseurl);
    }
}

echo $OUTPUT->header();

$renderer = mbst\course::get_renderer();
echo html_writer::tag('h2', $pagetitle);

$buttons = '';
if (mbst\perms::can_publish($template)) {
    $url = clone($thisurl);
    $url->param('do', 'publish');
    $buttons .= $OUTPUT->single_button($url, get_string('publish'));
}
if (mbst\perms::can_archive($template)) {
    $url = clone($thisurl);
    $url->param('do', 'archive');
    $buttons .= $OUTPUT->single_button($url, get_string('archive', 'block_mbstpl'));
}
if (!empty($buttons)) {
    echo html_writer::div($buttons, 'templateactionbtns');
}

echo $renderer->coursebox($course,$template);

if ($cansendfeedback) {
    $feedbackform->display();
}

$revhists = mbst\course::get_revhist($template->id);
echo $renderer->templatehistory($revhists);

if (mbst\perms::can_sendrevision($template, $coursecontext)) {
    $url = new moodle_url('/block/mbstpl/forrevision.php');
    echo $OUTPUT->single_button($url, get_string('forrevision', 'block_mbstpl'));
}

echo $OUTPUT->footer();