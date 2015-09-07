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
 * Class dupcrs
 * @package block_mbstpl
 * Create template to course duplication task request.
 */

class dupcrs extends \moodleform {
    function definition() {
        $form = $this->_form;

        $course = $this->_customdata['course'];

        $form->addElement('hidden', 'course', $course->id);
        $form->setType('course', PARAM_INT);

        if (!empty($this->_customdata['cats'])) {
            $form->addElement('radio', 'restoreto', get_string('restoretonewcourse', 'backup'), '', 'cat');
            $options = array();
            foreach($this->_customdata['cats'] as $cat) {
                $options[$cat->id] = $cat->name;
            }
            $form->addElement('select', 'tocat', get_string('selectacategory', 'backup'), $options);
            $form->disabledIf('tocat', 'restoreto', 'neq', 'cat');
        }
        if (!empty($this->_customdata['courses'])) {
            $form->addElement('radio', 'restoreto', get_string('restoretoexistingcourse', 'backup'), '', 'course');
            $options = array();
            foreach($this->_customdata['courses'] as $crs) {
                $options[$crs->id] = $crs->fullname;
            }
            $form->addElement('select', 'tocrs', get_string('selectacourse', 'backup'), $options);
            $form->disabledIf('tocrs', 'restoreto', 'neq', 'course');
        }
        $form->addRule('restoreto', get_string('required'), 'required', null, 'client');

        $form->addElement('static', 'license', '',
            get_string('duplcourselicense', 'block_mbstpl',$this->_customdata['creator']));

        $this->add_action_buttons(true, get_string('duplcourseforuse', 'block_mbstpl'));
    }
}