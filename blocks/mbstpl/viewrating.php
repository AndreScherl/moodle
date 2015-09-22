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

require_once(dirname(dirname(__DIR__)) . '/config.php');

global $PAGE, $OUTPUT, $USER;

use \block_mbstpl AS mbst;

$courseid = required_param('course', PARAM_INT);
$coursecontext = context_course::instance($courseid);
$course = get_course($courseid);
$redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));

$template = mbst\dataobj\template::get_from_course($courseid);
if (!$template->fetched) {
    redirect($redirecturl);
}

if (!mbst\perms::can_viewfeedback($template, $coursecontext)) {
    redirect($redirecturl);
}

$thisurl = new moodle_url('/blocks/mbstpl/viewrating.php');
$thisurl->param('course', $courseid);

$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');
$PAGE->set_context($coursecontext);

$renderer = mbst\course::get_renderer();
echo $OUTPUT->header();

echo $renderer->coursebox($course, $template, false);

echo $OUTPUT->footer();
