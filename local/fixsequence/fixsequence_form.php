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
 * Main code for local plugin addpositionmanager
 *
 * @package   local_addpositionmanager
 * @copyright 2013 Andreas Wagner, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;


require_once($CFG->dirroot . '/lib/formslib.php');


class fixsequence_form extends moodleform {

    // Define the form.
    protected function definition() {
        
        $mform = & $this->_form;
       
        $mform->addElement('header', 'settings', get_string('settings', 'local_fixsequence'));
        
        $choices = array(
            '0' => get_string('searchcourses', 'local_fixsequence'),
            '1' => get_string('searchandfixcourses', 'local_fixsequence')
        );
        
        $mform->addElement('select', 'options', get_string('action', 'local_fixsequence'), $choices);

        if (isset($this->_customdata['foundcourses'])) {
            
            foreach ($this->_customdata['foundcourses'] as $course) {
                $mform->addElement('checkbox', 'courses', $course->name);
            }
        }
      
        $mform->addElement('submit', 'run', get_string('run', 'local_fixsequence' ));
    }
}