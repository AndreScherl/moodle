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
use \block_mbstpl as mbst;


defined('MOODLE_INTERNAL') || die();

/**
 * Class sendtemplate
 * @package block_mbstpl
 * Main question form
 */
class sendtemplate extends licenseandassetform {
    function definition() {

        $form = $this->_form;

        $form->addElement('header', 'coursemetadata', get_string('coursemetadata', 'block_mbstpl'));

        $form->addElement('hidden', 'course', $this->_customdata['courseid']);
        $form->setType('course', PARAM_INT);

        $form->addElement('text', 'coursename', get_string('coursename', 'block_mbstpl'));
        $form->setType('coursename', PARAM_TEXT);

        $form->addElement('static', 'sendtpldate', get_string('sendtpldate', 'block_mbstpl'));

        // Add custom questions.
        $questions = $this->_customdata['questions'];
        $excludequestions = array('checklist', 'checkbox');

        foreach ($questions as $question) {
            if (!in_array($question->datatype, $excludequestions)) {               
                $typeclass = mbst\questman\qtype_base::qtype_factory($question->datatype);
                $typeclass::add_template_element($form, $question);
                $typeclass::add_rule($form, $question);
                if ($question->datatype == 'checkboxgroup') {
                    $this->add_checkbox_controller($question->id, null, null, 0);
                }
            }
        }

        $radioarray = array();
        $radioarray[] = $form->createElement('radio', 'withanon', '', get_string('withanon', 'block_mbstpl'), 1);
        $radioarray[] = $form->createElement('radio', 'withanon', '', get_string('withoutanon', 'block_mbstpl'), 0);
        $form->addGroup($radioarray, 'incluserdata', get_string('incluserdata', 'block_mbstpl'), array(' ', ' '), false);
        $form->setDefault('withanon', 0);
        $form->addHelpButton('incluserdata', 'incluserdata', 'block_mbstpl');

        // Tags.
        $this->define_tags();

        // Creator.
        $this->define_creator();

        $form->setExpanded('coursemetadata');
        $form->closeHeaderBefore('coursemetadata');

        // Legal data questions and license information.
        $this->define_legalinfo_fieldset();

        $this->add_action_buttons(true, get_string('sendforreviewing', 'block_mbstpl'));

        $form->freeze(array('coursename'));
    }

    function definition_after_data() {
        parent::definition_after_data();
        mbst\questman\qtype_base::definition_after_data($this->_form);
    }
}
