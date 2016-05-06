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
            'checkboxgroup',
            'text',
            'textarea',
            'checklist',
            'lookupset'
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
            if ($sm->string_exists('pluginname', 'profilefield_' . $type)) {
                $datatypes[$type] = get_string('pluginname', 'profilefield_' . $type);
            } else {
                $datatypes[$type] = get_string('field_' . $type, 'block_mbstpl');
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
        $formobj = (object) array(
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
     * @param  bool $isfrozen
     */
    public static function map_answers_to_fieldname($questions, $metaid, $isfrozen = false) {
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
        $preprocesseds = $DB->get_records_select('block_mbstpl_answer', "metaid = :meta AND questionid $qidin", $params, '', 'id,data,dataformat,questionid');
        foreach ($preprocesseds as $prec) {
            $qid = $prec->questionid;
            if (!isset($questions[$qid])) {
                continue;
            }
            $question = $questions[$qid];
            $typeclass = qtype_base::qtype_factory($question->datatype);
            $answers[$question->fieldname] = $typeclass::process_answer($question, $prec, $isfrozen);
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
        list($qidin, $params) = $DB->get_in_or_equal($qids);
        $allqs = $DB->get_records_select_menu('block_mbstpl_question', "id $qidin", $params, 'name ASC', 'id,name');

        $searchqs = self::get_searchqs();
        $searchqskeys = array_flip($searchqs);
        $enableds = array();
        $disableds = array();
        foreach ($allqs as $id => $question) {
            $qobj = (object) array('id' => $id, 'name' => $question, 'enabled' => false);
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

    public static function build_form(mbst\dataobj\template $template, $course, $customdata = array()) {

        global $DB;

        $courseid = $course->id;
        $meta = $template->get_meta();
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
            'course' => $course,
            'creator' => $creator
        );

        $tform = new mbst\form\editmeta(null, $customdata);

        $answers = mbst\questman\manager::map_answers_to_fieldname($questions, $meta->id, !empty($customdata['freeze']));

        $tform->set_data($answers);

        self::populate_meta($tform, $meta, false);

        return $tform;
    }

    /**
     * Map the array keys to question types and call prepare_data functions
     * of the question type classes to prepare the data for calling set_data()
     * of the form.
     * 
     * @param array $default_values
     * @return array the prepared values for using in moodle forms.
     */
    private static function process_answers($answers, &$defaultformdata) {
        global $DB;

        $sql = "SELECT CONCAT('custq', id), q.* FROM {block_mbstpl_question} q";

        if (!$questions = $DB->get_records_sql($sql, array())) {
            return false;
        }

        $allowedtypes = self::allowed_datatypes();

        foreach ($answers as $key => $answer) {

            if (!isset($questions[$key])) {
                continue;
            }

            if (!in_array($questions[$key]->datatype, $allowedtypes)) {
                continue;
            }

            $typeclass = qtype_base::qtype_factory($questions[$key]->datatype);

            $defaultformdata->$key = $typeclass::process_answer($questions[$key], $answer);
        }
        return true;
    }

    public static function populate_meta(mbst\form\editmeta $form, mbst\dataobj\meta $meta, $setanswers = true) {

        $setdata = (object) array(
                    'license' => $meta->license,
                    'tags' => $meta->get_tags_string()
        );

        if ($setanswers) {
            // Load the answers to the dynamic questions.
            /* @var $answers mbst\dataobj\answer[] */
            $answers = mbst\dataobj\answer::fetch_all(array('metaid' => $meta->id));

            // Index ansers by field name.
            $answerdata = array();

            foreach ($answers as $answer) {
                $answerdata['custq' . $answer->questionid] = $answer;
            }

            self::process_answers($answerdata, $setdata);
        }

        $form->set_data($setdata);
    }

    /**
     * Install mebis build-in meta questions
     */
    public static function install_questions() {
        $question = new \stdClass();

        $question->datatype = 'checkboxgroup';
        $question->name = 'Jahrgangsstufe';
        $question->title = 'Jahrgangsstufe';
        $question->defaultdata = '';
        $question->defaultdataformat = 0; //FORMAT_MOODLE
        $question->param1 = '1
        2
        3
        4
        5
        6
        7
        8
        9
        10
        11
        12
        13';
        $question->param2 = NULL;
        $question->help = '<p>Mehrfachauswahl möglich</p>';
        $question->required = 0;
        $question->inuse = 0;
        self::add($question);

        $question->datatype = 'checkboxgroup';
        $question->name = 'Schulart';
        $question->title = 'Schulart';
        $question->defaultdata = '';
        $question->defaultdataformat = 0; //FORMAT_MOODLE
        $question->param1 = 'Grundschule
        Mittelschule
        Realschule
        Wirtschaftsschule
        Gymnasium
        Förderschule
        Berufsschule
        Fachoberschule
        Berufsoberschule
        Fachschule
        Fachakademie';
        $question->param2 = NULL;
        $question->help = '<p>Mehrfachauswahl möglich</p>';
        $question->required = 1;
        $question->inuse = 0;
        self::add($question);

        $question->datatype = 'checkboxgroup';
        $question->name = 'Computereinsatz';
        $question->title = 'Computereinsatz';
        $question->defaultdata = '';
        $question->defaultdataformat = 0; //FORMAT_MOODLE
        $question->param1 = 'zu Hause
        im Unterricht';
        $question->param2 = NULL;
        $question->help = '<ul><li>Geben Sie an, ob Sie für die Nutzung Ihres Kurses den Computereinsatz im Unterricht oder zu Hause angedacht haben.</li>'
                . '<li>Eine Mehrfachauswahl ist möglich.</li></ul>';
        $question->required = 0;
        $question->inuse = 0;
        self::add($question);

        $question->datatype = 'lookupset';
        $question->name = 'Fach';
        $question->title = 'Fach';
        $question->defaultdata = '';
        $question->defaultdataformat = 0; //FORMAT_MOODLE
        $question->param1 = '/blocks/mbstpl/lookupset_ajax.php?action=searchsubject';
        $question->param2 = 'block_mbstpl_subjects,id,subject';
        $question->help = '<ul><li>Nach Eingabe von mindestens drei Buchstaben werden Ihnen die verfügbaren Fächer vorgeschlagen.</li>'
                . '<li>Eine Mehrfachauswahl ist möglich.</li>'
                . '<li>Sollten Sie die gewünschte Fachbezeichnung nicht zur Verfügung haben weichen Sie bitte auf das Feld für Schlagworte aus.</li></ul>';
        $question->required = 0;
        $question->inuse = 0;
        self::add($question);

        $question->datatype = 'checkbox';
        $question->name = 'Fremdmaterial';
        $question->title = 'Fremdmaterial';
        $question->defaultdata = 0;
        $question->defaultdataformat = 0; //FORMAT_MOODLE
        $question->param1 = 'Sofern Fremdmaterial verwendet wurde, ist das Urheberrecht beachtet.';
        $question->param2 = NULL;
        $question->help = '<ul><li>Fremdmaterialien sind z. B. Texte, Bilder, Fotos, Audio-/Videodateien; die nicht ausschließlich von Ihnen selbst erstellt wurden.</li>'
                . '<li>Als Fremdmaterialien werden vornehmlich freie Werke verwendet (z. B. Creative Commons (CC) oder gemeinfreie Inhalte).</li>'
                . '<li>Für jedes Fremdmaterial ist die entsprechende Herkunft angegeben und das Verwendungsrecht geklärt, d. h. Name des <u>Urhebers</u>, <u>Direktlink</u> zum originalen Fremdmaterial, <u>Titel</u> des Werkes bzw. Dateiname, Art der <u>Lizenz</u><br> (z. B. <a href="https://de.wikipedia.org/wiki/Reichstagsgeb%C3%A4ude#/media/File:Reichstag_building_Berlin_view_from_west_before_sunset.jpg">Das Reichstagsgebäude</a> von Jürgen Matern, <a href="https://creativecommons.org/licenses/by-sa/3.0/de/">CC BY-SA 3.0 DE</a>).</li>'
                . '<li>Alle angegebenen CC-Lizenzen beinhalten einen Link zur Lizenz, z. B. <a target="_blank" href="https://creativecommons.org/licenses/by/3.0/de/">CC BY 3.0 DE</a>.</li>'
                . '<li>Für Fremdmaterialien, die nicht unter einer freien Lizenz stehen, aber mit freundlicher Genehmigung des Urhebers verwendet werden dürfen, wurde eine Weiterverbreitungserlaubnis eingeholt und archiviert (z. B. © Cornelsen Verlag. Mit freundlicher Genehmigung zur Weiterverbreitung.).</li>'
                . '</ul>';
        $question->required = 1;
        $question->inuse = 0;
        self::add($question);

        $question->datatype = 'checkbox';
        $question->name = 'Fremdmaterial bearbeitet';
        $question->title = 'Fremdmaterial bearbeitet';
        $question->defaultdata = 0;
        $question->defaultdataformat = 0; //FORMAT_MOODLE
        $question->param1 = 'Sofern Fremdmaterialien zulässigerweise verändert wurden, ist kenntlich gemacht, worin die Bearbeitung besteht, jeweils gefolgt von der jeweiligen (CC-)Kennzeichnung.';
        $question->param2 = NULL;
        $question->help = '<ul><li>z. B. Übersetzung des Werks … aus dem Englischen, Ausschnitt des Bildes …, Textüberarbeitung</li></ul>';
        $question->required = 1;
        $question->inuse = 0;
        self::add($question);

        $question->datatype = 'checkbox';
        $question->name = 'Eigenmaterial';
        $question->title = 'Eigenmaterial';
        $question->defaultdata = 0;
        $question->defaultdataformat = 0; //FORMAT_MOODLE
        $question->param1 = 'Sofern Eigenmaterial verwendet wurde, ist dieses kenntlich gemacht. Bei selbst erstellten Texten und Arbeitsaufträgen ist dies nicht erforderlich.';
        $question->param2 = NULL;
        $question->help = '<ul><li>Selbsterzeugte Bilder, Videos, Audios u. ä. sind mit Quellenangaben versehen.</li>'
                . '<li>Texte, Aufgabenstellungen u. ä. sind, soweit nicht anders angegeben, Eigenmaterialien und müssen nicht explizit mit dem eigenen Namen versehen werden.</li>'
                . '</ul>';
        $question->required = 1;
        $question->inuse = 0;
        self::add($question);

        $question->datatype = 'checkbox';
        $question->name = 'Stimmaufnahmen und Bildnisse';
        $question->title = 'Stimmaufnahmen und Bildnisse';
        $question->defaultdata = 0;
        $question->defaultdataformat = 0; //FORMAT_MOODLE
        $question->param1 = 'Sofern Stimmaufnahmen und Bildnisse im Sinne des § 22 KunstUrhG verwendet werden, wurden schriftliche Einverständniserklärungen eingeholt und archiviert.';
        $question->param2 = NULL;
        $question->help = NULL;
        $question->required = 1;
        $question->inuse = 0;
        self::add($question);

        $question->datatype = 'checkbox';
        $question->name = 'personenbezogene oder -beziehbare Daten';
        $question->title = 'personenbezogene oder -beziehbare Daten';
        $question->defaultdata = 0;
        $question->defaultdataformat = 0; //FORMAT_MOODLE
        $question->param1 = 'Darüber hinausgehende personenbezogene oder -beziehbare Daten (z. B. Schülernamen in Forenbeiträgen) werden nicht genannt oder sind unkenntlich gemacht.';
        $question->param2 = NULL;
        $question->help = '<ul><li>Profilnamen werden automatisch anonymisiert, z. B. Autor eines Forenbeitrags.</li>'
                . '<li>Namensnennungen in Texteingaben müssen selbstständig editiert werden, z. B. „Eva hat ein tolles Referat gehalten.“ -> „SchülerX hat ein tolles Referat gehalten.“</li>'
                . '</ul>';
        $question->required = 1;
        $question->inuse = 0;
        self::add($question);

        $question->datatype = 'checkbox';
        $question->name = 'Nutzungsbedingungen';
        $question->title = 'Nutzungsbedingungen';
        $question->defaultdata = 0;
        $question->defaultdataformat = 0; //FORMAT_MOODLE
        $question->param1 = 'Ich habe die <a target="_blank" href="https://www.mebis.bayern.de/nutzungsbedingungenteachshare/">Nutzungsbedingungen</a> gelesen und akzeptiere sie.';
        $question->param2 = NULL;
        $question->help = NULL;
        $question->required = 1;
        $question->inuse = 0;
        self::add($question);

        $question->datatype = 'textarea';
        $question->name = 'Kursbeschreibung';
        $question->title = 'Kursbeschreibung';
        $question->defaultdata = '';
        $question->defaultdataformat = 0; //FORMAT_MOODLE
        $question->param1 = 30;
        $question->param2 = 2048;
        $question->help = NULL;
        $question->required = 0;
        $question->inuse = 0;
        self::add($question);
    }

    /**
     * Adding a new question to table block_mbstpl_question
     * @param object $question {
     *            datatype => string the datatype of question, see function allowed_datatypes() [required]
     *            name  => string the fullname of the question [required]
     *            title => string the title of the question[required]
     *            defaultdata => string
     *            defaultdataformat => int default: 0 -> FORMAT_MOODLE [required]
     *            param1 => string
     *            param2 => string
     *            help => string text for a help button
     *            required => int is it required? [required]
     *            inuse => int is it used? [required]
     * }
     */
    public static function add($question) {
        global $DB;
        if ($existingquestion = $DB->get_record('block_mbstpl_question', array('datatype' => $question->datatype, 'name' => $question->name))) {
            $question->id = $existingquestion->id;
            return $DB->update_record('block_mbstpl_question', $question);
        } else {
            return $DB->insert_record('block_mbstpl_question', $question);
        }
    }

    /**
     * Install mebis build-in subjects
     */
    public static function install_subjects() {
        global $DB;
        $subjects = array("Agrarwirtschaft", "Allgemeine Betriebswirtschaftslehre", "Angewandte Informatik", "Arbeitssicherheit",
            "Astrophysik", "Augenoptik", "Aussenwirtschaft", "Bautechnik", "Bekleidungstechnik", "Betriebswirtschaftliche Steuerung und Kontrolle",
            "Betriebswirtschaftslehre mit Rechnungswesen", "Biologie", "Biophysik", "Blumenkunst", "Buchführung", "Bürokommunikation",
            "Chemie", "Chemietechnik", "Chinesisch", "Darstellung", "Datenverarbeitung", "Datenverarbeitungstechnik", "Deutsch",
            "Deutsch als Zweitsprache", "Drucktechnik", "Elektrotechnik", "Englisch", "Ergotherapie", "Ernährung und Hauswirtschaft",
            "Ernährung und Versorgung", "Ethik", "Euro-Management-Assistenten", "Evangelische Religionslehre", "Fahrzeugtechnik und Elektromobilität",
            "Familienpflege", "Farbtechnik und Raumgestaltung", "Fleischtechnik", "Förderschwerpunkt emotionale und soziale Entwicklung",
            "Förderschwerpunkt geistige Entwicklung", "Förderschwerpunkt Hören", "Förderschwerpunkt körperliche und motorische Entwicklung",
            "Förderschwerpunkt Lernen", "Förderschwerpunkt Sehen", "Förderschwerpunkt Sprache", "Französisch", "Fremdsprachenberufe",
            "Gastgewerbliche Berufe", "Geisteswissenschaften", "Geographie", "Geologie", "Geschichte", "Gestaltung", "Gestaltungslehre",
            "Gesundheit", "Gesundheit und Soziales", "Gesundheitswesen", "Glashüttentechnik", "Griechisch", "Hauswirtschaft",
            "Heilerziehungspflege", "Heilpädagogik", "Heimat- und Sachunterricht", "Holztechnik", "Hotel- und Gaststättengewerbe ",
            "Informatik", "Informatiktechnik", "Informationstechnologie", "Informationsverarbeitung", "Islamischer Unterricht", "Italienisch", "Katholische Religionslehre",
            "Kaufmännische Assistenten", "Keramik und Design", "Körperpflege", "Kunst", "Kunsterziehung", "Kunststofftechnik", "Landeskunde",
            "Latein", "Lebensmittelverarbeitungstechnik", "Maschinenbautechnik", "Mathematik", "Mechatronik", "Medien", "Medizinische Fachangestellte",
            "Meisterschule", "Mensch und Umwelt", "Metallbautechnik", "Metalltechnik", "Musik", "Musisch-ästhetische Bildung", "Natur und Technik",
            "Naturwissenschaften", "Neugriechisch", "Pädagogik", "Physik", "Physiotherapie", "Politik", "Psychologie", "Raum- und Objektdesign",
            "Rechnungswesen", "Rechtslehre", "Rechtswesen", "Russisch", "Sanitär-, Heizungs- und Klimatechnik", "Sozialkunde", "Sozialpädagogik",
            "Sozialpraktische Grundbildung", "Sozialwissenschaftliche Arbeitsfelder", "Spanisch", "Sport", "Steintechnik", "Technik",
            "Technische Assistenten", "Technisches Zeichnen", "Technologie", "Textiltechnik", "Textiltechnik und Bekleidung",
            "Textverarbeitung", "Tschechisch", "Türkisch", "Übungsunternehmen", "Umweltschutztechnik und regenerative Energien",
            "Volkswirtschaft", "Volkswirtschaftslehre", "Werken und Gestalten", "Wirtschaft", "Wirtschaft und Beruf",
            "Wirtschaft und Kommunikation", "Wirtschaft und Recht", "Wirtschaft und Verwaltung", "Wirtschaftsgeographie",
            "Wirtschaftsinformatik", "Wirtschaftslehre", "Wirtschaftsmathematik");

        foreach ($subjects as $subject) {
            $record = new \stdClass();
            $record->subject = $subject;
            if (!$DB->record_exists('block_mbstpl_subjects', array('subject' => $subject))) {
                $DB->insert_record('block_mbstpl_subjects', $record);
            }
        }
    }

    public static function update_lookupsetquestions() {
        global $DB;

        $params['comma'] = '%,%';
        $sql = "SELECT a.id, a.metaid, a.questionid, a.data, a.dataformat, a.datakeyword, a.comment 
                  FROM {block_mbstpl_answer} a
                  LEFT JOIN {block_mbstpl_question} q ON a.questionid = q.id
                 WHERE (q.name IN ('Jahrgangsstufe', 'Schulart', 'Computereinsatz', 'Fach')) and (a.questionid NOT IN (5,6,7))";
        if (!$records = $DB->get_records_sql($sql, $params)) {
            return;
        }

        foreach ($records as $record) {
            if (isset($record->data)) {
                $data = explode(',', $record->data);
                $record->data = '#' . implode('#', $data) . '#';
                $record->datakeyword = $record->data;
                $DB->update_record('block_mbstpl_answer', $record, true);
            }
        }
    }

}
