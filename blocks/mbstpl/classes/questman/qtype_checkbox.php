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

defined('MOODLE_INTERNAL') || die();

class qtype_checkbox extends qtype_base {

    public static function extend_form(\MoodleQuickForm $form, $islocked = false) {
        $form->addElement('selectyesno', 'defaultdata', get_string('profiledefaultchecked', 'admin'));
        $form->setDefault('defaultdata', 0); // Defaults to 'no'.
        $form->setType('defaultdata', PARAM_BOOL);
    }

    public static function add_template_element(\MoodleQuickForm $form, $question) {
        $form->addElement('checkbox', $question->fieldname, $question->title);
        if ($question->defaultdata) {
            $form->setDefault($question->fieldname, true);
        }
    }

    public static function save_answer($metaid, $questionid, $answer, $dataformat = FORMAT_MOODLE) {
        $answer = empty($answer) ? 0 : 1;
        return parent::save_answer($metaid, $questionid, $answer);
    }

    public static function add_to_searchform(\MoodleQuickForm $form, $question, $elname) {
        $options = array(
            '*' => get_string('any'),
            '1' => get_string('yes'),
            '0' => get_string('no'),
        );
        $form->addElement('select', $elname, $question->title, $options);
    }

    public static function get_query_filters($question, $answer) {
        $toreturn = array('wheres' => array(), 'params' => array());
        if ($answer == '*') {
            return $toreturn;
        }
        $qparam = 'q' . $question->id;
        $aparam = 'a' . $question->id;
        $toreturn['wheres'][] = self::get_whereexists("AND datakeyword = :$aparam", $qparam);
        $toreturn['params'][$qparam] = $question->id;
        $toreturn['params'][$aparam] = $answer;
        return $toreturn;
    }

}