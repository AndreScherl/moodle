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
 * @package   block_mbstpl
 * @copyright 2016 Franziska Hübler, ISB
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\questman;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/local/mbs/classes/form/MoodleQuickForm_lookupset.php');

class qtype_lookupset extends qtype_base {

    public static function extend_form(\MoodleQuickForm $form, $islocked = false) {
        $form->addElement('text', 'param1', get_string('ajaxurl', 'block_mbstpl'));
        $form->setType('param1', PARAM_URL);
        $form->addRule('param1', get_string('required'), 'required', null, 'client');

        $form->addElement('hidden', 'defaultdata', '0');
        $form->setType('defaultdata', PARAM_INT);
    }   
    
    public static function add_template_element(\MoodleQuickForm $form,
                                                $question) {

        $ajaxurl = new \moodle_url($question->param1);
        $question->title = self::add_help_button($question);   
        $form->addElement('lookupset', $question->fieldname, $question->title, $ajaxurl, array());
        $form->setType($question->fieldname, PARAM_INT);
    }

    /**
     * Save the answer when template is sended.
     * @param $metaid
     * @param $questionid
     * @param $data
     * @param $dataformat
     * @return bool;
     */
    public static function save_answer($metaid, $questionid, $answer,
                                       $comment = null,
                                       $dataformat = FORMAT_MOODLE) {

        if (is_null($answer)) {
            $answer = array();
        }

        // Implode all the checked options.
        $answer = implode(',', array_keys($answer));

        $answerdata = array(
            'metaid' => $metaid,
            'questionid' => $questionid,
            'data' => $answer,
            'dataformat' => $dataformat,
        );
        if ($comment !== null) {
            $answerdata['comment'] = trim($comment);
        }

        $answerobj = new \block_mbstpl\dataobj\answer($answerdata);
        $answerobj->insertorupdate();
        return true;
    }

    public static function add_to_searchform(\MoodleQuickForm $form, $question,
                                             $elname) {
        $ajaxurl = new \moodle_url($question->param1);
        $question->title = self::add_help_button($question);   
        $form->addElement('lookupset', $elname, $question->title, $ajaxurl, array());
        $form->setType($elname, PARAM_INT);
    }

    /**
     * TODO
     * Set the query filter for this matedata filter. Note that using like will
     * slow down performance when more options are selected.
     * 
     * @param type $question
     * @param type $answer
     * @return array
     */
    public static function get_query_filters($question, $answer) {
        global $DB;

        $toreturn = array('wheres' => array(), 'params' => array());
        if (empty($answer)) {
            return $toreturn;
        }

        $checkids = array_keys($answer);
        //IN() verwenden!
        $like = array();
        $qparam = 'q' . $question->id;

        foreach ($checkids as $optionid) {
            $like[] = $DB->sql_like($qparam . '.data', ':option' . $optionid);
            $toreturn['params']['option' . $optionid] = '%#' . $optionid . '#%';
        }

        $likes = "(" . implode(" OR ", $like) . ")";

        $toreturn['params'][$qparam] = $question->id;
        $toreturn['joins'][] = self::get_join("AND $likes", $qparam);

        return $toreturn;
    }

    /**
     * Prepare the data, which is given as numbers separated by #.
     * Note that data is not changed, when it don't start with '#'.
     * 
     * This method must be called before $form->set_data();
     * 
     * @param string $data, the data for the field
     * @return array|string the array of field indices, which should be checked or original data.
     */
    public static function prepare_data($data) {
        global $DB;
        
        if (empty($data[0])) {
            return $data;
        }

        $values = trim($data, ','); 

        if (empty($values)) {
            return array();
        } else {
            $subjects = $DB->get_records_list('block_mbstpl_subjects', 'subject', $values);
            return  array_combine(explode(',', $values), $subjects);
        }
    }

    /**
     * If the type has text editor fields, let them be known.
     * @return array
     */
    public static function get_editors() {
        return array('help');
    }
}

