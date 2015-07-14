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

$thisurl = new moodle_url('/blocks/mbstpl/assignreviewer.php');
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');

$courseid = required_param('course', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($courseid, false);
$coursecontext = context_course::instance($courseid);

$PAGE->set_context($coursecontext);
$pagetitle = get_string('assignreviewer', 'block_mbstpl');
$PAGE->set_title($pagetitle);
if (!mbst\course::can_assignreview($coursecontext)) {
    throw new moodle_exception('errorcannotassignreviewer', 'block_mbstpl');
}

// Load possible users.
$catcontext = context_coursecat::instance($course->category);
$users = get_users_by_capability($catcontext, 'block/mbstpl:coursetemplatereview',
                                 'u.id,u.firstname,u.lastname', 'u.lastname ASC, u.firstname ASC');
if (empty($users)) {
    throw new moodle_exception('errornoassignableusers', 'block_mbstpl');
}

$customdata = array('courseid' => $course->id, 'users' => $users);
$form = new mbst\assignreviewerform(null, $customdata);
$redirurl = new moodle_url('/course/view.php', array('id' => $courseid));
if ($form->is_cancelled()) {
    redirect($redirurl);
} else if ($data = $form->get_data()) {
    mbst\course::assign_reviewer($courseid, $data->reviewerid);
    redirect($redirurl);
}
echo $OUTPUT->header();

echo html_writer::tag('h2', $pagetitle);
$form->display();
echo $OUTPUT->footer();