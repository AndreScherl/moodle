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

$courseid = required_param('course', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$template = new mbst\dataobj\template(array('courseid' => $courseid), true, MUST_EXIST);
$meta = new mbst\dataobj\meta(array('templateid' => $template->id), true, MUST_EXIST);

$thisurl = new moodle_url('/blocks/mbstpl/editmeta.php', array('course' => $course->id));
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');

require_login($courseid, false);
$coursecontext = context_course::instance($courseid);

$PAGE->set_context($coursecontext);
$pagetitle = get_string('editmeta', 'block_mbstpl');
$PAGE->set_title($pagetitle);
if (!mbst\perms::can_editmeta($template, $coursecontext)) {
    throw new moodle_exception('errorcannoteditmeta', 'block_mbstpl');
}

// Gather the custom data for the form.
$backup = new mbst\dataobj\backup(array('id' => $template->backupid), true, MUST_EXIST);
$qform = mbst\questman\manager::get_qform($backup->qformid);
$qidlist = $qform ? $qform->questions : '';
$questions = mbst\questman\manager::get_questsions_in_order($qidlist);
foreach($questions as $questionid => $question) {
    $questions[$questionid]->fieldname = 'custq' . $questions[$questionid]->id;
}
$creator = $DB->get_record('user', array('id' => $backup->creatorid));
$customdata = array('courseid' => $courseid, 'questions' => $questions, 'creator' => $creator);

// Set up the form.
$form = new mbst\form\editmeta(null, $customdata);
// Load the answers to the dynamic questions.
$setdata = (object)array(
    'asset_id' => array(),
    'asset_url' => array(),
    'asset_license' => array(),
    'asset_owner' => array(),
    'license' => $meta->license,
    'tags' => $meta->get_tags_string(),
);
/** @var mbst\dataobj\answer[] $answers */
$answers = mbst\dataobj\answer::fetch_all(array('metaid' => $meta->id));
foreach($answers as $answer) {
    $setdata->{'custq'.$answer->questionid} = array('text' => $answer->data, 'format' => $answer->dataformat);
    $setdata->{'custq'.$answer->questionid.'_comment'} = $answer->comment;
}
// Load the assets fields.
$i = 0;
foreach ($meta->get_assets() as $asset) {
    $setdata->asset_id[$i] = $asset->id;
    $setdata->asset_url[$i] = $asset->url;
    $setdata->asset_license[$i] = $asset->license;
    $setdata->asset_owner[$i] = $asset->owner;
    $i++;
}
$form->set_data($setdata);

$redirurl = new moodle_url('/course/view.php', array('id' => $courseid));
if ($form->is_cancelled()) {
    redirect($redirurl);
}

if ($data = $form->get_data()) {
    // Save answers to dynamic questions.
    foreach($questions as $questionid => $question) {
        $typeclass = mbst\questman\qtype_base::qtype_factory($question->datatype);
        $answer = isset($data->{$question->fieldname}) ? $data->{$question->fieldname} : null;
        $comment = isset($data->{$question->fieldname.'_comment'}) ? $data->{$question->fieldname.'_comment'} : '';
        $typeclass::save_answer($meta->id, $question->id, $answer, $comment);
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

    redirect($redirurl);
}

echo $OUTPUT->header();

echo html_writer::tag('h2', $pagetitle);
$form->display();
echo $OUTPUT->footer();