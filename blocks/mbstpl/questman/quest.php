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

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $PAGE, $USER, $CFG, $DB, $OUTPUT;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('manageqforms', 'block_mbstpl'));
$PAGE->set_url(new moodle_url('/blocks/mbstpl/questman/quest.php'));

$id = optional_param('id', 0, PARAM_INT);

require_login(SITEID, false);
if (!is_siteadmin()) {
    require_capability('moodle/site:config', $systemcontext);
}

if (!$id) {
    $datatype = required_param('datatype', PARAM_TEXT);
    $inuse = 0;
    $alloweds = \block_mbstpl\questman\manager::allowed_datatypes();
    if (!in_array($datatype, $alloweds)) {
        throw new \moodle_exception('errorincorrectdatatype', 'block_mbstpl');
    }
} else {
    $question = $DB->get_record('block_mbstpl_question', array('id' => $id), '*', MUST_EXIST);
    $inuse = $question->inuse;
    $datatype = $question->datatype;
}
$typeobj = \block_mbstpl\questman\qtype_base::qtype_factory($datatype);

$customdata = array('id' => $id, 'datatype' => $datatype, 'typeobj' => $typeobj, 'inuse' => $inuse);
$mform = new \block_mbstpl\form\questedit(null, $customdata);
if ($id) {
    if ($editors = $typeobj->get_editors()) {
        foreach($editors as $editor) {
            if (!empty($question->{$editor})) {
                $question->{$editor} = array('text' => $question->{$editor}, 'format' => FORMAT_HTML);
            }
        }
    }
    $mform->set_data($question);
}
$redirurl = new moodle_url('/blocks/mbstpl/questman/index.php');
if ($mform->is_cancelled()){
    redirect($redirurl);
} else if ($data = $mform->get_data()) {
    if ($editors = $typeobj->get_editors()) {
        foreach($editors as $editor) {
            if (!empty($data->{$editor})) {
                $data->{$editor} = $data->{$editor}['text'];
            }
        }
    }
    $dataobj = (object)array(
        'name' => $data->name,
        'title' => $data->title,
        'defaultdata' => $data->defaultdata,
    );
    if (isset($data->param1)) {
        $dataobj->param1 = $data->param1;
    }
    if (isset($data->param2)) {
        $dataobj->param2 = $data->param2;
    }
    if ($id) {
        $dataobj->id = $id;
        $DB->update_record('block_mbstpl_question', $dataobj);
    } else {
        $dataobj->datatype = $datatype;
        $qid = $DB->insert_record('block_mbstpl_question', $dataobj);
        \block_mbstpl\questman\manager::add_question_to_draft($qid);
    }
    redirect($redirurl);
}

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();