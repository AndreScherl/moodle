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

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class editmeta
 * @package block_mbstpl
 * Edit tempalte meta form.
 */

class editmeta extends \moodleform {
    function definition() {
        $form = $this->_form;

        $form->addElement('hidden', 'course', $this->_customdata['courseid']);
        $form->setType('course', PARAM_INT);

        // Add custom questions.
        $questions = $this->_customdata['questions'];
        foreach($questions as $question) {
            $typeclass = mbst\questman\qtype_base::qtype_factory($question->datatype);
            $typeclass::add_template_element($form, $question);
        }

        $this->add_action_buttons(true, get_string('save', 'block_mbstpl'));

        if (!empty($this->_customdata['freeze'])) {
            $form->hardFreeze();
        }
    }

    function set_data($default_values) {
        if (is_object($default_values)) {
            $default_values = (array)$default_values;
        }
        $data = array();
        foreach($default_values as $key => $value) {
            if (!is_array($value) || !isset($value['text'])) {
                continue;
            }
            $type = $this->_form->getElementType($key);
            if ($type == 'editor') {
                $data[$key] = $value;
            } else {
                $data[$key] = $value['text'];
            }
        }
        parent::set_data($data);
    }
}