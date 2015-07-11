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

    public static function add_template_element(\MoodleQuickForm &$form, $question) {
        $attributes = array(
            'startyear' => $question->param1,
            'stopyear'  => $question->param2,
            'optional'  => false,
        );

        $form->addElement('date_time_selector', $question->fieldname, $question->title, $attributes);
    }

    public static function save_answer($backupid, $questionid, $answer, $dataformat = FORMAT_MOODLE) {
        if (empty($answer)) {
            $answer = 0;
        }
        return parent::save_answer($backupid, $questionid, $answer);
    }

}