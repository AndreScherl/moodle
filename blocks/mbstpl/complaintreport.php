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
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__DIR__)) . '/config.php');

global $PAGE, $OUTPUT, $USER;

use \block_mbstpl AS mbst;

$courseid = required_param('courseid', PARAM_INT);
$coursecontext = context_course::instance($courseid);
$course = get_course($courseid);
$redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));

$thisurl = new moodle_url('/blocks/mbstpl/complaintreport.php', array('course' => $courseid));
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($coursecontext);

if (!mbst\perms::can_complain($coursecontext)) {
    throw new moodle_exception('errorcannotcomplain', 'block_mbstpl');
}

//Adding breadcrumb navigation. 
require_login($courseid, false);
//Extending the navigation for the course. 
$coursenode = $PAGE->navigation->find($courseid, navigation_node::TYPE_COURSE);
$pagenode = $coursenode->add(get_string('complaintform', 'block_mbstpl'));
$pagenode->make_active();

$pagetitle = get_string('complaintform', 'block_mbstpl');
$PAGE->set_title($pagetitle);

$form = new mbst\form\complaintform(null, array('courseid' => $courseid, 'useremail' => $USER->email));
if ($form->is_cancelled()) {
    redirect($redirecturl);
} else if ($data = $form->get_data()) {
    $errormessage = (object)array(
        'courseid' => $courseid,
        'errortype' => $data->error,
        'details' => $data->details,
        'email' => $data->email,
        'userid' => $USER->id
    );
    mbst\complaint::manage_complaint($errormessage);

    redirect($redirecturl);
}

echo $OUTPUT->header();

echo html_writer::tag('h2', $pagetitle);
echo html_writer::div($form->render(), 'complaintreport');

echo $OUTPUT->footer();
