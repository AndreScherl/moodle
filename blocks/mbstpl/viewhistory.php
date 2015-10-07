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
 * @copyright 2015 Janek Lasocki-Biczysko, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $PAGE, $OUTPUT;

use \block_mbstpl AS mbst;

$courseid = required_param('course', PARAM_INT);
$coursecontext = context_course::instance($courseid);

$thisurl = new moodle_url('/blocks/mbstpl/viewhistory.php', array('course' => $courseid));
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');
$PAGE->set_context($coursecontext);

require_login($courseid, false);

$courseurl = new moodle_url('/course/view.php', array('id' => $courseid));

if (!mbst\perms::can_viewhistory($coursecontext)) {
    redirect($courseurl);
}

$template = mbst\dataobj\template::get_from_course($courseid);
$templatecourse = get_course($template->courseid);

$renderer = $PAGE->get_renderer('block_mbstpl');

echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('templatehistoryreport', 'block_mbstpl', $templatecourse));

$courseswithcreators = mbst\course::get_courses_with_creators($template->id);
echo $renderer->templateusage($courseswithcreators);

$revhists = mbst\course::get_revhist($template->id);
echo $renderer->templatehistory($revhists);

echo $OUTPUT->footer();
