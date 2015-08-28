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

require_once (dirname(dirname(__DIR__)) . '/config.php');

global $PAGE, $OUTPUT, $USER;

$courseid = required_param('course', PARAM_INT);

$thisurl = new moodle_url('/blocks/mbstpl/ratetemplate.php');
$thisurl->param('course', $courseid);

$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');

$coursecontext = context_course::instance($courseid);

$PAGE->set_context($coursecontext);

$form = new block_mbstpl\form\starrating(null, array('courseid' => $courseid));
$redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));
if ($form->is_cancelled()) {
    redirect($redirecturl);
} else if ($data = $form->get_data()) {

    $template = new \block_mbstpl\dataobj\template(array('courseid' => $courseid), true, MUST_EXIST);

    $ratingdata = array(
        'userid' => $USER->id,
        'templateid' => $template->id,
        'rating' => $data->block_mbstpl_rating,
        'comment' => $data->block_mbstpl_rating_comment
    );

    $rating = new \block_mbstpl\dataobj\starrating($ratingdata);
    $rating->insert();

    redirect($redirecturl);
}

echo $OUTPUT->header();

echo html_writer::div($form->render(), 'template_rating');

echo $OUTPUT->footer();
