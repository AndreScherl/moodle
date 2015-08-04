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
$revhist = mbst\dataobj\revhist::fetch(array('id' => $id));
if (empty($revhist->id)) {
    throw new moodle_exception('invalidrecord', '', '', 'block_mbstpl_revhist');
}
$template = mbst\dataobj\template::fetch(array('id' => $revhist->templateid));
if (empty($template->id)) {
    throw new moodle_exception('invalidrecord', '', '', 'block_mbstpl_template');
}
$courseid = $template->courseid;

require_login($courseid, false);
$coursecontext = context_course::instance($courseid);

$PAGE->set_context($coursecontext);
$pagetitle = get_string('templatefeedback', 'block_mbstpl');
$PAGE->set_title($pagetitle);
if (!mbst\course::can_viewfeedback($coursecontext, $template)) {
    throw new moodle_exception('errorcannotviewfeedback', 'block_mbstpl');
}

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('block_mbstpl');
echo html_writer::tag('h2', $pagetitle);

$feedback = format_text($revhist->feedback, $revhist->feedbackformat);
echo html_writer::div($feedback);

echo $OUTPUT->footer();