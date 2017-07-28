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
 * @copyright 2015 Andreas Wagner, ISB
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\questman;

defined('MOODLE_INTERNAL') || die();

class qtype_checkboxgroup extends qtype_menu {

    public static function add_template_element(\MoodleQuickForm $form, $question) {
        if (isset($question->param1)) {
            $rawoptions = explode("\n", $question->param1);
        } else {
            $rawoptions = array();
        }

        $boxes = array();
        foreach ($rawoptions as $key => $option) {
            $boxes[] = & $form->createElement('advcheckbox', $key, null, format_string($option), array('group' => $question->id));
        }

        $question->title = self::add_help_button($question);
        $form->addGroup($boxes, $question->fieldname, $question->title, "&nbsp;");
    }
    
    public static function validate_question($data, $question) {
        $errors = array();
        // If the checkboxgroup is required make sure that at least one advcheckbox is checked. 
        if (!empty($question->required) && (array_sum($data[$question->fieldname]) == 0)) {
            $errors[$question->fieldname] = get_string('leastoneoption', 'block_mbstpl');
        }
        
        return $errors;
    }

    /**
     * Save the answer when template is sended.
     * @param $metaid
     * @param $questionid
     * @param $data
     * @param $dataformat
     * @return bool;
     */
    public static function save_answer($metaid, $questionid, $answer, $comment = null, $dataformat = FORMAT_MOODLE) {
        if (!isset($answer)) {
            $answer = array();
        }

        // Implode all the checked options.
        $answer = '#'.implode('#', array_keys($answer, true)).'#';

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

    public static function add_to_searchform(\MoodleQuickForm $form, $question, $elname) {
        $values = explode("\n", $question->param1);
        $boxes = array();
        for ($i = 0; $i < count($values); $i++) {
            $boxes[] = & $form->createElement('advcheckbox', $i, null, $values[$i], array('group' => $question->id));
        }
        $form->addGroup($boxes, $elname, $question->title, "&nbsp;");
    }

    /**
     * Set the query filter for this metadata filter. Note that using like will
     * slow down performance when more options are selected.
     * 
     * @param type $question
     * @param type $answer
     * @return array
     */
    public static function get_query_filters($question, $answer) {
        $toreturn = array('joins' => array(), 'params' => array(), 'wheres' => array());

        if (!isset($answer)) {
            return array();
        }

        $checkids = array_keys($answer, true);
        if (empty($checkids)) {
            return array();
        }

        $where = array();
        $qparam = 'q' . $question->id;
        // For each checked option we do a search in the data string.
        foreach ($checkids as $optionid) {
            $optionid = '#'.$optionid.'#';
            $where[] = "INSTR({$qparam}.data, '$optionid') > 0";
        }

        if (!empty($where)) {
            $toreturn['wheres'][] = "(" . implode(" OR ", $where) . ")";
        }
        
        $toreturn['joins'][] = self::get_join('', $qparam);
        $toreturn['params'][$qparam] = $question->id;        

        return $toreturn;
    }

    /**
     * Gets answer according to type (by default the data, for some fields an array).
     * @param object $answer
     */
    public static function process_answer($question, $answer, $isfrozen = false) {
        if (!isset($answer->data)) {
            return '';
        } else {
            return array_fill_keys(explode('#', $answer->data), 1);
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
