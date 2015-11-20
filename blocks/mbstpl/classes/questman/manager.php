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

namespace block_mbstpl\questman;

use \block_mbstpl as mbst;

defined('MOODLE_INTERNAL') || die();

class manager {

    /**
     * Returns array of class names of allowed datatypes.
     */
    public static function allowed_datatypes() {
        return array(
            'checkbox',
            'datetime',
            'menu',
            'text',
            'textarea',
            'checklist',
        );
    }

    /**
     * Retrieve a list of all the available data types
     * @return   array   a list of the datatypes suitable to use in a select statement
     */
    public static function list_datatypes() {
        $datatypes = array();

        $sm = get_string_manager();
        $types = self::allowed_datatypes();
        foreach ($types as $type) {
            if ($sm->string_exists('pluginname', 'profilefield_'.$type)) {
                $datatypes[$type] = get_string('pluginname', 'profilefield_'.$type);
            } else {
                $datatypes[$type] = get_string('field_'.$type, 'block_mbstpl');
            }
        }
        asort($datatypes);

        return $datatypes;
    }

    /**
     * Returns array of current draft's questions in order.
     * @return array
     */
    public static function get_draft_questions() {
        $draft = self::get_qform_draft();
        return self::get_questsions_in_order($draft);
    }

    /**
     * Returns array of current active form's questions in order.
     * @return array
     */
    public static function get_active_questions() {
        if (!$active = self::get_active_qform()) {
            return array();
        }
        return self::get_questsions_in_order($active->questions);
    }

    /**
     * Returns questions in order
     * @param mixed $questions comma-separated list of question ids, or array
     */
    public static function get_questsions_in_order($qids) {
        global $DB;

        if (!is_array($qids)) {
            $qids = explode(',', $qids);
        }
        if (empty($qids)) {
            return array();
        }
        list($qidin, $params) = $DB->get_in_or_equal($qids);
        $questsions = $DB->get_records_select('block_mbstpl_question', "id $qidin", $params);

        // Now sort.
        $ordered = array();
        foreach ($qids as $key) {
            if (array_key_exists($key, $questsions)) {
                $ordered[$key] = $questsions[$key];
                unset($questsions[$key]);
            }
        }
        return $ordered + $questsions;
    }

    /**
     * Returns array of questions in bank (i.e not in draft).
     * @return array
     */
    public static function get_bank_questions() {
        global $DB;

        $draft = self::get_qform_draft();
        $qids = explode(',', $draft);
        if (empty($qids)) {
            return array();
        }
        list($qidin, $params) = $DB->get_in_or_equal($qids, SQL_PARAMS_QM, null, false);
        $questsions = $DB->get_records_select('block_mbstpl_question', "id $qidin", $params);
        return $questsions;
    }

    /**
     * Gets the current draft of question form.
     * @return object
     */
    public static function get_qform_draft() {
        $draft = get_config('block_mbstpl', 'qformdraft');
        if (empty($draft)) {
            return '';
        }
        return $draft;
    }

    /**
     * Saves changes in the draft.
     * @return bool success
     */
    public static function set_qform_draft($draft) {
        $draft = trim($draft, ',');
        return set_config('qformdraft', $draft, 'block_mbstpl');
    }

    /**
     * Add one question to the existing draft.
     * @param $qid
     * @return bool success
     */
    public static function add_question_to_draft($qid) {
        $draft = self::get_qform_draft();
        $qids = explode(',', $draft);
        if (in_array($qid, $qids)) {
            return false; // Already there.
        }
        $qids[] = $qid;
        $draft = implode(',', $qids);
        return self::set_qform_draft($draft);
    }

    /**
     * Delete a question if possible, otherwise just remove from draft.
     * @param object $question
     */
    public static function delete_question($question) {
        global $DB;

        if (!$question->inuse) {
            $DB->delete_records('block_mbstpl_question', array('id' => $question->id));
            return;
        }
        $draft = self::get_qform_draft();
        $qids = explode(',', $draft);
        $qindex = array_search($question->id, $qids);
        if ($qindex === false) {
            return;
        }
        unset($qids[$qindex]);
        $draft = implode(',', $qids);
        self::set_qform_draft($draft);
    }

    /**
     * Gets the id of the active question form.
     * @return mixed int|false when not found
     */
    public static function get_active_qformid() {
        global $DB;
        return $DB->get_field_sql("SELECT MAX(id) FROM {block_mbstpl_qform}");
    }

    /**
     * Gets the current active question form, false if not exists.
     * @return object
     */
    public static function get_active_qform() {
        if (!$formid = self::get_active_qformid()) {
            // Draft hasn't been activated yet. Let's create one.
            self::activate_draft(get_string('initialform', 'block_mbstpl'), false);
            $formid = self::get_active_qformid();
        }
        return self::get_qform($formid);
    }

    /**
     * Gets a questionnaire form by id.
     * @param int $id
     * @return object
     */
    public static function get_qform($id) {
        global $DB;
        return $DB->get_record('block_mbstpl_qform', array('id' => $id));
    }

    /**
     * Activates the current draft.
     * @param string $formname
     * @param bool $checkdraft unless set false will check if draft mode first.
     */
    public static function activate_draft($formname, $checkdraft = true) {
        global $DB;
        if ($checkdraft && !self::is_draft()) {
            return;
        }
        $draft = self::get_qform_draft();
        $formobj = (object)array(
            'questions' => $draft,
            'name' => $formname,
            'timecreated' => time(),
        );
        $DB->insert_record('block_mbstpl_qform', $formobj);

        // Update all active questions to in use.
        $qids = explode(',', $draft);
        list($qidin, $params) = $DB->get_in_or_equal($qids);
        $DB->execute("UPDATE {block_mbstpl_question} SET inuse = 1 WHERE id $qidin", $params);
    }

    /**
     * Tells us if the current draft is different from the active
     * @return bool
     */
    public static function is_draft() {
        $draft = self::get_qform_draft();
        if (empty($draft)) {
            return false;
        }
        if (!$previous = self::get_active_qform()) {
            return true;
        }
        if ($previous->questions == $draft) {
            return false;
        }
        return true;
    }

    /**
     * Move question up or down in the draft.
     * @param int $qid
     * @param bool $up (false for down)
     * @return bool success
     */
    public static function move_question($qid, $up) {
        $draft = self::get_qform_draft();
        $quests = explode(',', $draft);
        $qindex = array_search($qid, $quests);
        if ($qindex === false) {
            return false;
        }
        $swapid = $up ? $qindex - 1 : $qindex + 1;
        if (!isset($quests[$swapid])) {
            return false;
        }
        $tempval = $quests[$swapid];
        $quests[$swapid] = $quests[$qindex];
        $quests[$qindex] = $tempval;
        $draft = implode(',', $quests);
        return self::set_qform_draft($draft);
    }

    /**
     * For questions with 'fieldname' value, get a list of answers to load in the form.
     * @param $questions
     * @param int $metaid
     */
    public static function map_answers_to_fieldname($questions, $metaid) {
        global $DB;

        $qids = array();
        $answers = array();
        foreach ($questions as $question) {
            $qids[$question->id] = $question->id;
        }
        if (empty($qids)) {
            return array();
        }

        list($qidin, $params) = $DB->get_in_or_equal($qids, SQL_PARAMS_NAMED);
        $params['meta'] = $metaid;
        $preprocesseds = $DB->get_records_select('block_mbstpl_answer', "metaid = :meta AND questionid $qidin", $params,
            '', 'id,data,dataformat,questionid');
        foreach ($preprocesseds as $prec) {
            $qid = $prec->questionid;
            if (!isset($questions[$qid])) {
                continue;
            }
            $question = $questions[$qid];
            $typeclass = qtype_base::qtype_factory($question->datatype);
            $answers[$question->fieldname] = $typeclass::process_answer($prec);
        }
        return $answers;
    }

    /**
     * Returns an array of the current enabled questions for the search form
     */
    public static function get_searchqs() {
        $ordered = get_config('block_mbstpl', 'searchqs');
        if (empty($ordered)) {
            return array();
        }
        return explode(',', $ordered);
    }

    /**
     * Saves enabled questions for the search form
     * @param array $qids question ids in order.
     */
    public static function set_searchqs($qids) {
        $qlist = implode(',', $qids);
        return set_config('searchqs', $qlist, 'block_mbstpl');
    }

    /**
     * Adds or removes a question from the search form.
     * @param $qid
     * @param bool|true $enable
     */
    public static function searchq_setenabled($qid, $enable = true) {
        $qids = self::get_searchqs();
        if ($enable) {
            if (in_array($qid, $qids)) {
                return; // Already there.
            }
            $qids[] = $qid;
            return self::set_searchqs($qids);
        }
        $pos = array_search($qid, $qids);
        if ($pos === false) {
            return; // Already removed.
        }
        unset($qids[$pos]);
        return self::set_searchqs($qids);
    }

    /**
     * Move a question in the search form up or down.
     * @param $qid
     * @param bool|true $up
     */
    public static function searchq_move($qid, $up = true) {
        $qids = self::get_searchqs();
        $pos = array_search($qid, $qids);
        if ($pos === false) {
            return; // Not there.
        }
        $swappos = $up ? $pos - 1 : $pos + 1;
        if (!isset($qids[$swappos])) {
            return; // Nowhere to move it.
        }
        $qids[$pos] = $qids[$swappos];
        $qids[$swappos] = $qid;
        return self::set_searchqs($qids);
    }

    /**
     * Get all questions for the search management page.
     * @return array of questions in display order with id, title and enabled.
     */
    public static function searchmanage_getall() {
        global $DB;

        $draft = self::get_qform_draft();
        $qids = explode(',', $draft);
        if (empty($qids)) {
            return array();
        }
        list($qidin, $params) = $DB->get_in_or_equal($qids, SQL_PARAMS_QM, null, false);
        $allqs = $DB->get_records_select_menu('block_mbstpl_question', "id $qidin", $params, 'name ASC', 'id,name');

        $searchqs = self::get_searchqs();
        $searchqskeys = array_flip($searchqs);
        $enableds = array();
        $disableds = array();
        foreach ($allqs as $id => $question) {
            $qobj = (object)array('id' => $id, 'name' => $question, 'enabled' => false);
            if (isset($searchqskeys[$id])) {
                $qobj->enabled = true;
                $enableds[$searchqskeys[$id]] = $qobj;
            } else {
                $disableds[] = $qobj;
            }
        }
        ksort($enableds);
        return array_merge($enableds, $disableds);
    }

    public static function build_form($template, $course, $customdata = array()) {

        global $DB;

        $courseid = $course->id;
        $meta = new mbst\dataobj\meta(array('templateid' => $template->id), true, MUST_EXIST);
        $backup = new mbst\dataobj\backup(array('id' => $template->backupid), true, MUST_EXIST);
        $qform = mbst\questman\manager::get_qform($backup->qformid);
        $qidlist = $qform ? $qform->questions : '';
        $questions = mbst\questman\manager::get_questsions_in_order($qidlist);
        $creator = $DB->get_record('user', array('id' => $backup->creatorid));
        foreach (array_keys($questions) as $questionid) {
            $questions[$questionid]->fieldname = 'custq' . $questions[$questionid]->id;
        }
        $customdata += array(
            'courseid' => $courseid,
            'questions' => $questions,
            'template' => $template,
            'assetcount' => $meta->get_asset_count(),
            'course' => $course,
            'creator' => $creator
        );

        $tform = new mbst\form\editmeta(null, $customdata);
        $answers = mbst\questman\manager::map_answers_to_fieldname($questions, $meta->id);
        $tform->set_data($answers);

        self::populate_meta_and_assets($tform, $meta, false);

        return $tform;
    }

    public static function populate_meta_and_assets(mbst\form\editmeta $form, mbst\dataobj\meta $meta, $setanswers = true) {

        $setdata = (object)array(
            'asset_id' => array(),
            'asset_url' => array(),
            'asset_license' => array(),
            'asset_owner' => array(),
            'asset_source' => array(),
            'license' => $meta->license,
            'tags' => $meta->get_tags_string(),
        );

        if ($setanswers) {
            // Load the answers to the dynamic questions.
            /* @var $answers mbst\dataobj\answer[] */
            $answers = mbst\dataobj\answer::fetch_all(array('metaid' => $meta->id));
            foreach ($answers as $answer) {
                $setdata->{'custq'.$answer->questionid} = array('text' => $answer->data, 'format' => $answer->dataformat);
                $setdata->{'custq'.$answer->questionid.'_comment'} = $answer->comment;
            }
        }

        // Load the assets fields.
        $i = 0;
        foreach ($meta->get_assets() as $asset) {
            $setdata->asset_id[$i] = $asset->id;
            $setdata->asset_url[$i] = $asset->url;
            $setdata->asset_license[$i] = $asset->license;
            $setdata->asset_owner[$i] = $asset->owner;
            $setdata->asset_source[$i] = $asset->source;
            $i++;
        }
        $form->set_data($setdata);
    }
}
