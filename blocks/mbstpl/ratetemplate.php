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
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($coursecontext);

$template = mbst\dataobj\template::get_from_course($courseid);
if (!$template->fetched) {
    redirect($redirecturl);
}

//Adding breadcrumb navigation. 
require_login($courseid, false);

$pagetitle = get_string('mbstpl:ratetemplate', 'block_mbstpl');
$PAGE->set_title($pagetitle);

$tform = mbst\questman\manager::build_form($template, $course, array(
    'justtags' => true,
    'withrating' => true,
    'freeze' => true
));

// Rate form.
$starrating = new mbst\dataobj\starrating(array('userid' => $USER->id, 'templateid' => $template->id));
$form = new mbst\form\starrating($thisurl, array('freeze' => $starrating->fetched && $starrating->rating));
if ($form->is_cancelled()) {
    redirect($redirecturl);
} else if ($data = $form->get_data()) {
    $starrating->rating = $data->block_mbstpl_rating;
    mbst\rating::save_userrating($template, $starrating);

    redirect($redirecturl);
} else if ($starrating->fetched) {
    $form->set_data(array(
        'block_mbstpl_rating' => $starrating->rating
    ));
}

echo $OUTPUT->header();

echo html_writer::tag('h2', $pagetitle.' '.$course->fullname);

echo html_writer::div($tform->render(), 'template_rating');

echo html_writer::tag('h3', get_string('rating_header', 'block_mbstpl'));

echo html_writer::div($form->render(), 'template_rating');

echo $OUTPUT->footer();
