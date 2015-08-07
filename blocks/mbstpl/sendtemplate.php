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

$systemcontext = context_system::instance();
$thisurl = new moodle_url('/blocks/mbstpl/sendtemplate.php');
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');

$courseid = required_param('course', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($courseid, false);
$coursecontext = context_course::instance($courseid);

$PAGE->set_context($coursecontext);
$pagetitle = get_string('sendcoursetemplate', 'block_mbstpl');
$PAGE->set_title($pagetitle);
require_capability('block/mbstpl:sendcoursetemplate', $coursecontext);

$activeform = mbst\questman\manager::get_active_qform();
$qidlist = $activeform ? $activeform->questions : '';
$questions = mbst\questman\manager::get_questsions_in_order($qidlist);
foreach($questions as $questionid => $question) {
    $questions[$questionid]->fieldname = 'custq' . $questions[$questionid]->id;
}
$customdata = array('courseid' => $courseid, 'questions' => $questions);
$form = new mbst\form\sendtemplate(null, $customdata);
$redirurl = new moodle_url('/course/view.php', array('id' => $courseid));
if ($form->is_cancelled()) {
    redirect($redirurl);
} else if ($data = $form->get_data()) {
    $backupdata = array(
        'origcourseid' => $courseid,
        'creatorid' => $USER->id,
        'timecreated' => time(),
        'qformid' => $activeform->id,
        'incluserdata' => empty($data->incluserdata) ? 0 :1,
    );
    $backup = new mbst\dataobj\backup($backupdata);
    $backup->insert();
    $meta = new mbst\dataobj\meta(array('backupid' => $backup->id), true, MUST_EXIST);

    // Save answers to dynamic questions.
    foreach($questions as $questionid => $question) {
        $typeclass = mbst\questman\qtype_base::qtype_factory($question->datatype);
        $answer = empty($data->{$question->fieldname}) ? null : $data->{$question->fieldname};
        $typeclass::save_answer($meta->id, $question->id, $answer);
    }

    // Initiate deployment task.
    $deployment = new \block_mbstpl\task\adhoc_deploy();
    $deployment->set_custom_data($backup);
    \core\task\manager::queue_adhoc_task($deployment);

    redirect($redirurl);
}
$data = (object)array(
    'coursename' => mbst\course::TPLPREFIX . $course->shortname,
    'sendtpldate' => time(),
);
$form->set_data($data);

echo $OUTPUT->header();

echo html_writer::tag('h2', $pagetitle);
echo html_writer::tag('h3', get_string('sendcoursetemplateheading', 'block_mbstpl'));
$form->display();
echo $OUTPUT->footer();