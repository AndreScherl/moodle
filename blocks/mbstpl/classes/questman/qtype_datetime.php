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

class qtype_datetime extends qtype_base {
    public static function extend_form(\MoodleQuickForm $form, $islocked = false) {
        // Get the current calendar in use - see MDL-18375.
        $calendartype = \core_calendar\type_factory::get_calendar_instance();

        // Create variables to store start and end.
        list($year, $month, $day) = explode('_', date('Y_m_d'));
        $currentdate = $calendartype->convert_from_gregorian($year, $month, $day);
        $currentyear = $currentdate['year'];

        $arryears = $calendartype->get_years();
        $form->addElement('select', 'param1', get_string('startyear', 'profilefield_datetime'), $arryears);
        $form->setType('param1', PARAM_INT);
        $form->setDefault('param1', $currentyear);

        $form->addElement('select', 'param2', get_string('endyear', 'profilefield_datetime'), $arryears);
        $form->setType('param2', PARAM_INT);
        $form->setDefault('param2', $currentyear);

        $form->addElement('hidden', 'defaultdata', '0');
        $form->setType('defaultdata', PARAM_INT);
    }

    public function extend_validation($data, $files) {
        $errors = array();

        // Make sure the start year is not greater than the end year.
        if ($data->param1 > $data->param2) {
            $errors['param1'] = get_string('startyearafterend', 'profilefield_datetime');
        }

        return $errors;
    }

    public static function add_template_element(\MoodleQuickForm $form, $question) {
        $attributes = array(
            'startyear' => $question->param1,
            'stopyear'  => $question->param2,
            'optional'  => false,
        );

        $form->addElement('date_time_selector', $question->fieldname, $question->title, $attributes);
    }

    public static function save_answer($metaid, $questionid, $answer, $comment = null, $dataformat = FORMAT_MOODLE) {
        if (empty($answer)) {
            $answer = 0;
        }
        return parent::save_answer($metaid, $questionid, $answer, $comment);
    }

    public static function add_to_searchform(\MoodleQuickForm $form, $question, $elname) {
        $attributes = array(
            'startyear' => $question->param1,
            'stopyear'  => $question->param2,
            'optional'  => true,
        );
        $elgroup = array();
        $elgroup[] = $form->createElement('date_time_selector', $elname.'_from', get_string('from'), $attributes);
        $elgroup[] = $form->createElement('date_time_selector', $elname.'_until', get_string('to'), $attributes);
        $separator = \html_writer::empty_tag('br') . get_string('to') . \html_writer::empty_tag('br');
        $form->addGroup($elgroup, $elname, $question->title, $separator, false);
    }

    public static function get_query_filters($question, $answer) {
        $toreturn = array('wheres' => array(), 'params' => array());
        if (empty($answer['from']) && empty($answer['until'])) {
            return $toreturn;
        }
        $qparam = 'q' . $question->id;
        $toreturn['params'][$qparam] = $question->id;
        $filter = "";
        if (!empty($answer['from'])) {
            $aparam = 'af'.$question->id;
            $filter .= " AND datakeyword >= :$aparam";
            $toreturn['params'][$aparam] = $answer['from'];
        }
        if (!empty($answer['until'])) {
            $aparam = 'au'.$question->id;
            $filter .= " AND datakeyword <= :$aparam";
            $toreturn['params'][$aparam] = $answer['until'];
        }
        $toreturn['wheres'][] = self::get_whereexists($filter, $qparam);
        return $toreturn;
    }
}