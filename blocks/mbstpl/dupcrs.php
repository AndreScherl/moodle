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

$thisurl = new moodle_url('/blocks/mbstpl/dupcrs.php');
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');

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
$cats = array();
$courses = array();


$customdata = array('courseid' => $course->id, 'cats' => $cats, 'courses' => $courses);
$form = new mbst\form\dupcrs(null, $customdata);
$redirurl = new moodle_url('/course/view.php', array('id' => $courseid));
if ($form->is_cancelled()) {
    redirect($redirurl);
} else if ($data = $form->get_data()) {
    // TODO: launch task.
    redirect($redirurl);
}
echo $OUTPUT->header();

echo html_writer::tag('h2', $pagetitle);
$form->display();
echo $OUTPUT->footer();