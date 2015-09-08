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

class qtype_textarea extends qtype_base {
    public static function extend_form(\MoodleQuickForm $form, $islocked = false) {

        // Default data.
        $form->addElement('editor', 'defaultdata', get_string('profiledefaultdata', 'admin'));
        $form->setType('defaultdata', PARAM_RAW); // We have to trust person with capability to edit this default description.
    }

    public static function get_editors() {
        return array('defaultdata');
    }

    public static function add_template_element(\MoodleQuickForm $form, $question) {
        $form->addElement('editor', $question->fieldname, format_string($question->title));
        $form->setType($question->fieldname, PARAM_TEXT);
    }

    public static function save_answer($metaid, $questionid, $answer, $dataformat = FORMAT_MOODLE) {
        if (!is_array($answer) || !isset($answer['text'])) {
            $answer = array('text' => '', 'format' => FORMAT_MOODLE);
        }
        if (!isset($answer['format'])) {
            $answer['format'] = FORMAT_MOODLE;
        }
        return parent::save_answer($metaid, $questionid, $answer['text'], $answer['format']);
    }

    public static function process_answer($answer) {
        return array('text' => $answer->data, 'format' => $answer->dataformat);
    }

    public static function add_to_searchform(\MoodleQuickForm $form, $question, $elname) {
        qtype_text::add_to_searchform($form, $question, $elname);
    }
    public static function get_query_filters($question, $answer) {
        return qtype_text::get_query_filters($question, $answer);
    }


}