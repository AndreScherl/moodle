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

class qtype_text extends qtype_base {
    public static function extend_form(\MoodleQuickForm $form, $islocked = false) {
        // Default data.
        $form->addElement('text', 'defaultdata', get_string('profiledefaultdata', 'admin'), 'size="50"');
        $form->setType('defaultdata', PARAM_TEXT);

        // Param 1 for text type is the size of the field.
        $form->addElement('text', 'param1', get_string('profilefieldsize', 'admin'), 'size="6"');
        $form->setDefault('param1', 30);
        $form->setType('param1', PARAM_INT);

        // Param 2 for text type is the maxlength of the field.
        $form->addElement('text', 'param2', get_string('profilefieldmaxlength', 'admin'), 'size="6"');
        $form->setDefault('param2', 2048);
        $form->setType('param2', PARAM_INT);
    }

    public static function add_template_element(\MoodleQuickForm $form, $question) {
        $size = $question->param1;
        $maxlength = $question->param2;

        // Create the form field.
        $form->addElement('text', $question->fieldname, format_string($question->title), 'maxlength="'.$maxlength.'" size="'.$size.'" ');
        $form->setType($question->fieldname, PARAM_TEXT);
    }
}