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

$id = required_param('id', PARAM_INT);

$thisurl = new moodle_url('/blocks/mbstpl/feedbackdetail.php', array('id' => $id));
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');

// Load data from revision history id.
$revhist = new mbst\dataobj\revhist(array('id' => $id), true, MUST_EXIST);
$template = new mbst\dataobj\template(array('id' => $revhist->templateid), true, MUST_EXIST);
if (empty($template->id)) {
    throw new moodle_exception('invalidrecord', '', '', 'block_mbstpl_template');
}
$courseid = $template->courseid;

require_login($courseid, false);
$coursecontext = context_course::instance($courseid);
$course = $PAGE->course; // Save a DB query, as already loaded by require_login.

$PAGE->set_context($coursecontext);
$pagetitle = get_string('templatefeedback', 'block_mbstpl');
$PAGE->set_title($pagetitle);
if (!mbst\perms::can_viewfeedback($template, $coursecontext)) {
    throw new moodle_exception('errorcannotviewfeedback', 'block_mbstpl');
}
$overviewurl = new moodle_url('/blocks/mbstpl/viewfeedback.php', array('course' => $course->id));
$PAGE->navbar->add(get_string('templatefeedback', 'block_mbstpl'), $overviewurl);
$PAGE->navbar->add(userdate($revhist->timecreated));

/** @var block_mbstpl_renderer $output */
$output = $PAGE->get_renderer('block_mbstpl');

echo $output->header();

echo html_writer::tag('h2', $pagetitle);
echo $output->coursebox($course, $template);

echo $output->feedback($template, $revhist);

echo $output->single_button($overviewurl, get_string('backtemplatefeedback', 'block_mbstpl'), 'get');

echo $output->footer();