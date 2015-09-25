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

namespace block_mbstpl\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class questedit
 * @package block_mbstpl
 * Main question form
 */

class questedit extends \moodleform {
    protected function definition() {
        $form = $this->_form;

        $strrequired = get_string('required');

        $form->addElement('hidden', 'id', $this->_customdata['id']);
        $form->setType('id', PARAM_INT);

        $form->addElement('hidden', 'datatype', $this->_customdata['datatype']);
        $form->setType('datatype', PARAM_TEXT);

        $form->addElement('text', 'name', get_string('questionname', 'block_mbstpl'), 'maxlength="250" size="25"');
        $form->addRule('name', $strrequired, 'required', null, 'client');
        $form->setType('name', PARAM_TEXT);

        $form->addElement('textarea', 'title', get_string('questiontitle', 'block_mbstpl'),
            array('rows' => 3, 'cols' => 70, 'class' => 'smalltext'));
        $form->addRule('title', $strrequired, 'required', null, 'client');
        $form->setType('title', PARAM_TEXT);

        // Type-specific fields.
        $this->get_typeobj()->extend_form($form, $this->_customdata['inuse']);

        $this->add_action_buttons(true);
    }

    function validation($data, $files) {
        return $this->get_typeobj()->extend_validation((object)$data, $files);
    }

    public static function add_template_element(\MoodleQuickForm $form, $question) {
        $size = $question->param1;
        $maxlength = $question->param2;

        // Create the form field.
        $form->addElement('text', $question->fieldname, format_string($question->title),
            'maxlength="'.$maxlength.'" size="'.$size.'" ');
        $form->setType($question->fieldname, PARAM_TEXT);
    }

    /**
     * Returns the type object of the question.
     * @return \block_mbstpl\questman\qtype_base
     */
    private function get_typeobj() {
        return $this->_customdata['typeobj'];
    }
}
