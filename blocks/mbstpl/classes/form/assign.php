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

use block_mbstpl\questman\qtype_base;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class assign
 * @package block_mbstpl
 * Assign a reviewer or an author.
 */

class assign extends \moodleform {
    protected function definition() {
        $form = $this->_form;

        $form->addElement('hidden', 'course');
        $form->setType('course', PARAM_INT);

        $form->addElement('hidden', 'type');
        $form->setType('type', PARAM_ALPHA);

        if (!empty($this->_customdata['selector'])) {
            $form->addElement('static', 'selectuser', get_string('selectuser', 'block_mbstpl'), $this->_customdata['selector']);
        }

        $form->addElement('editor', 'feedback_editor', get_string('tasknote', 'block_mbstpl'), $this->_customdata['editoropts']);

        $form->addElement('filemanager', 'uploadfile_filemanager', get_string('uploadfile', 'block_mbstpl'),
                          $this->_customdata['fileopts']);

        if (isset($this->_customdata['questions'])) {
            foreach ($this->_customdata['questions'] as $question) {
                $typeclass = \block_mbstpl\questman\qtype_base::qtype_factory($question->datatype);
                $typeclass::add_template_element($form, $question);
            }
        }

        $this->add_action_buttons(true, get_string('assign'.$this->_customdata['type'], 'block_mbstpl'));
    }

    function definition_after_data() {
        parent::definition_after_data();
        qtype_base::definition_after_data($this->_form);
    }

}
