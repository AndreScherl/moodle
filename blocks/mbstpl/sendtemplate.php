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
use block_mbstpl\dataobj\asset;

$systemcontext = context_system::instance();

$courseid = required_param('course', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$thisurl = new moodle_url('/blocks/mbstpl/sendtemplate.php', array('course' => $course->id));
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');

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
$customdata = array('courseid' => $courseid, 'questions' => $questions, 'creator' => $USER);
$form = new mbst\form\sendtemplate(null, $customdata);
$redirurl = new moodle_url('/course/view.php', array('id' => $courseid));
if ($form->is_cancelled()) {
    redirect($redirurl);
} else if ($data = $form->get_data()) {
    $backupdata = array(
        'origcourseid' => $courseid,
        'creatorid' => $USER->id,
        'qformid' => $activeform->id,
        'incluserdata' => empty($data->withanon) ? 0 :1,
    );
    $backup = new mbst\dataobj\backup($backupdata, false);
    $backup->insert();
    $meta = new mbst\dataobj\meta(array('backupid' => $backup->id), true, MUST_EXIST);

    // Save answers to dynamic questions.
    foreach($questions as $questionid => $question) {
        $typeclass = mbst\questman\qtype_base::qtype_factory($question->datatype);
        $answer = isset($data->{$question->fieldname}) ? $data->{$question->fieldname} : null;
        $typeclass::save_answer($meta->id, $question->id, $answer);
    }

    // Save the license field.
    if ($meta->license != $data->license) {
        $meta->license = $data->license;
        $meta->update();
    }

    // Save the assets fields.
    foreach ($data->asset_id as $idx => $assetid) {
        $url = isset($data->asset_url[$idx]) ? trim($data->asset_url[$idx]) : '';
        $license = isset($data->asset_license[$idx]) ? $data->asset_license[$idx] : $CFG->defaultsitelicense;
        $owner = isset($data->asset_owner[$idx]) ? trim($data->asset_owner[$idx]) : '';
        $hasdata = $url || $owner;

        if ($assetid) {
            $asset = new asset(array('id' => $assetid, 'metaid' => $meta->id), true, MUST_EXIST);
            if (!$hasdata) {
                $asset->delete();
            }
        } else {
            $asset = new asset(array('metaid' => $meta->id), false);
        }
        if ($hasdata) {
            $asset->url = $url;
            $asset->license = $license;
            $asset->owner = $owner;
            if ($asset->id) {
                $asset->update();
            } else {
                $asset->insert();
            }
        }
    }

    // Save the tags.
    $meta->save_tags_string($data->tags);

    // Initiate deployment task.
    $deployment = new \block_mbstpl\task\adhoc_deploy_primary();
    $deployment->set_custom_data($backup);
    \core\task\manager::queue_adhoc_task($deployment);

    redirect($redirurl);
}
$data = (object)array(
    'coursename' => $course->shortname,
    'sendtpldate' => time(),
);
$form->set_data($data);

echo $OUTPUT->header();

echo html_writer::tag('h2', $pagetitle);
echo html_writer::tag('h3', get_string('sendcoursetemplateheading', 'block_mbstpl'));
$form->display();
echo $OUTPUT->footer();
