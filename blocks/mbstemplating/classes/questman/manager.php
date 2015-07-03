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
 * @package block_mbstemplating
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstemplating\questman;

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
        );
    }

    /**
     * Retrieve a list of all the available data types
     * @return   array   a list of the datatypes suitable to use in a select statement
     */
    public static function list_datatypes() {
        $datatypes = array();

        $types = self::allowed_datatypes();
        foreach ($types as $type) {
            $datatypes[$type] = get_string('pluginname', 'profilefield_'.$type);
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
        list($qidin, $params) = $DB->get_in_or_equal($qids);
        $questsions = $DB->get_records_select('block_mbstemplating_question', "id $qidin", $params);

        // Now sort.
        $ordered = array();
        foreach($qids as $key) {
            if(array_key_exists($key, $questsions)) {
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
        list($qidin, $params) = $DB->get_in_or_equal($qids, SQL_PARAMS_QM, null, false);
        $questsions = $DB->get_records_select('block_mbstemplating_question', "id $qidin", $params);
        return $questsions;
    }

    /**
     * Gets the current draft of question form.
     * @return object
     */
    public static function get_qform_draft() {
        $draft = get_config('block_mbstemplating', 'qformdraft');
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
        return set_config('qformdraft', $draft, 'block_mbstemplating');
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
            return false; // Alread there.
        }
        $qids[] = $qid;
        $draft = implode(',', $qids);
        return self::set_qform_draft($draft);
    }

    /**
     * Delete a question if possible, otherwise just remove from draft.
     * @param object $question
     * @return bool success
     */
    public static function delete_question($question) {
        global $DB;

        if (!$question->inuse) {
            return $DB->delete_records('block_mbstemplating_question', array('id' => $question->id));
        }
        $draft = self::get_qform_draft();
        $qids = explode(',', $draft);
        $qindex = array_search($question->id, $qids);
        if ($qindex === false) {
            return false;
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
        return $DB->get_field_sql("SELECT MAX(id) FROM {block_mbstemplating_qform}");
    }

    /**
     * Gets the current active question form, false if not exists.
     * @return object
     */
    public static function get_active_qform() {
        global $DB;
        if (!$formid = self::get_active_qformid()) {
            return false;
        }
        return $DB->get_record('block_mbstemplating_qform', array('id' => $formid));
    }

    /**
     * Activates the current draft.
     * @param string $formname
     * @return bool success
     */
    public static function activate_draft($formname) {
        global $DB;
        if (!self::is_draft()) {
            return true;
        }
        $draft = self::get_qform_draft();
        $formobj = (object)array(
            'questions' => $draft,
            'name' => $formname,
            'timecreated' => time(),
        );
        $DB->insert_record('block_mbstemplating_qform', $formobj);

        // Update all active questions to in use.
        $qids = explode(',', $draft);
        list($qidin, $params) = $DB->get_in_or_equal($qids);
        $DB->execute("UPDATE {block_mbstemplating_question} SET inuse = 1 WHERE id $qidin", $params);
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
}