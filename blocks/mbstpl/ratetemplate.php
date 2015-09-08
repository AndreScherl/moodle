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

require_once(dirname(dirname(__DIR__)) . '/config.php');

global $PAGE, $OUTPUT, $USER;

use \block_mbstpl AS mbst;

$courseid = required_param('course', PARAM_INT);
$coursecontext = context_course::instance($courseid);
$course = get_course($courseid);
$redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));

if (!mbst\perms::can_leaverating($coursecontext)) {
    redirect($redirecturl);
}

$thisurl = new moodle_url('/blocks/mbstpl/ratetemplate.php');
$thisurl->param('course', $courseid);

$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');
$PAGE->set_context($coursecontext);

$template = mbst\dataobj\template::get_from_course($courseid);
if (!$template->fetched) {
    redirect($redirecturl);
}

// Show template details.
$backup = new mbst\dataobj\backup(array('id' => $template->backupid), true, MUST_EXIST);
$qform = mbst\questman\manager::get_qform($backup->qformid);
$qidlist = $qform ? $qform->questions : '';
$questions = mbst\questman\manager::get_questsions_in_order($qidlist);
foreach($questions as $questionid => $question) {
    $questions[$questionid]->fieldname = 'custq' . $questions[$questionid]->id;
}
$customdata = array('courseid' => $courseid, 'questions' => $questions, 'freeze' => true);
$tform = new mbst\form\editmeta(null, $customdata);


// Rate form.
$starrating = new mbst\dataobj\starrating(array('userid' => $USER->id, 'templateid' => $template->id));
$form = new mbst\form\starrating($thisurl);
if ($form->is_cancelled()) {
    redirect($redirecturl);
} else if ($data = $form->get_data()) {
    $starrating->rating = $data->block_mbstpl_rating;
    $starrating->comment = $data->block_mbstpl_rating_comment;
    mbst\rating::save_userrating($template, $starrating);

    redirect($redirecturl);
} else if ($starrating->fetched) {
    $form->set_data(array(
        'block_mbstpl_rating' => $starrating->rating,
        'block_mbstpl_rating_comment' => $starrating->comment
    ));
}
$renderer = $PAGE->get_renderer('block_mbstpl');
echo $OUTPUT->header();

echo html_writer::tag('h1', $course->fullname);

echo html_writer::div($tform->render(), 'template_rating');

if (!is_null($template->rating)) {
    echo $renderer->rating($template->rating);
}

echo html_writer::tag('h3', get_string('rating_header', 'block_mbstpl'));

echo html_writer::div($form->render(), 'template_rating');

echo $OUTPUT->footer();
