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

namespace block_mbstpl;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class sendtemplateform
 * @package block_mbstpl
 * Main question form
 */

class sendtemplateform extends \moodleform {
    function definition() {
        $form = $this->_form;

        $form->addElement('hidden', 'course', $this->_customdata['courseid']);
        $form->setType('course', PARAM_INT);

        $form->addElement('text', 'coursename', get_string('coursename', 'block_mbstpl'));
        $form->setType('coursename', PARAM_TEXT);

        $form->addElement('date', 'sendtpldate', get_string('sendtpldate', 'block_mbstpl'));

        // Add custom questions.
        $questions = $this->_customdata['questions'];
        foreach($questions as $question) {
            $typeclass = questman\qtype_base::qtype_factory($question->datatype);
            $typeclass::add_template_element($form, $question);
        }

        $radioarray = array();
        $radioarray[] = $form->createElement('radio', 'deleted', '', get_string('withanon', 'block_mbstpl'), 0);
        $radioarray[] = $form->createElement('radio', 'deleted', '', get_string('withoutanon', 'block_mbstpl'), 1);
        $form->addGroup($radioarray, 'incluserdata', get_string('incluserdata', 'block_mbstpl'), array(' ', ' '), false);

        $form->addElement('checkbox', 'copyright', get_string('copyright', 'block_mbstpl'));
        $form->addRule('copyright', get_string('required'), 'required');

        $this->add_action_buttons(true, get_string('sendforreviewing', 'block_mbstpl'));

        $form->freeze(array('coursename', 'sendtpldate'));
    }

}