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
        // Editor field doesn't behave nicely when frozen, so use textarea instead
        $form->addElement('textarea', 'defaultdata', get_string('profiledefaultdata', 'admin'));
        $form->setType('defaultdata', PARAM_TEXT);
    }

    public static function get_editors() {
        return array('help');
    }

    public static function add_template_element(\MoodleQuickForm $form, $question) {        
        $question->title = self::add_help_button($question);
        $form->addElement('textarea', $question->fieldname, format_string($question->title));
        $form->setType($question->fieldname, PARAM_TEXT);
    }

    public static function save_answer($metaid, $questionid, $answer, $comment = null, $dataformat = FORMAT_MOODLE) {
        if (!isset($answer)) {
            $answer = '';
        }
        if (!isset($dataformat)) {
            $dataformat = FORMAT_MOODLE;
        }
        return parent::save_answer($metaid, $questionid, $answer, $comment, $dataformat);
    }

    public static function process_answer($question, $answer, $isfrozen = false) {
        return $answer->data;
    }

    public static function add_to_searchform(\MoodleQuickForm $form, $question, $elname) {
        qtype_text::add_to_searchform($form, $question, $elname);
    }
    
    public static function get_query_filters($question, $answer) {
        return qtype_text::get_query_filters($question, $answer);
    }
}