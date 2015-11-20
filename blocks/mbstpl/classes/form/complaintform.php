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
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class activatedraft
 * @package block_mbstpl
 * Main question form
 */

class complaintform extends \moodleform {
    
    protected function definition() {
        $mform = $this->_form;

        $courseid = $this->_customdata['courseid'];
        $course = get_course($courseid);
        $useremail = $this->_customdata['useremail'];

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        
        $coursename = $mform->addElement('text', 'coursename', get_string('coursename', 'block_mbstpl'));
        $mform->setType('coursename', PARAM_TEXT);
        $mform->setDefault('coursename', $course->fullname);
        $coursename->updateAttributes(array('disabled' => 'disabled'));
        
        $radiobarray = array();
        $radiobarray[] =& $mform->createElement('radio', 'error', '', get_string('complaintformerrortype_1', 'block_mbstpl'), 'error1');
        $radiobarray[] =& $mform->createElement('radio', 'error', '', get_string('complaintformerrortype_2', 'block_mbstpl'), 'error2');
        $radiobarray[] =& $mform->createElement('radio', 'error', '', get_string('complaintformerrortype_3', 'block_mbstpl'), 'error3');
        $mform->addGroup($radiobarray, 'errorar', get_string('complaintformerrortype', 'block_mbstpl'), array('<br />'), false);
        $mform->addRule('errorar', get_string('required'), 'required', null, 'client');
     
        $attributes = array();
        if (empty($useremail)) {
            $attributes = array('placeholder' => get_string('complaintformemail_default', 'block_mbstpl')); 
        }
        $mform->addElement('text', 'email', get_string('email'), $attributes); 
        if (!empty($useremail)) {
            $mform->setDefault('email', $useremail);
        }        
        $mform->setType('email', PARAM_EMAIL);  
        $mform->addRule('email', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('email', 'complaintformemail', 'block_mbstpl');
        
        $attributes = array('placeholder' => get_string('complaintformdetails_default', 'block_mbstpl'));
        $mform->addElement('textarea', 'details', '', $attributes);
        $mform->setType('details', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('submitbutton', 'block_mbstpl')); 
    }
}