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
 * @package block_mbslicenseinfo
 * @copyright 2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbslicenseinfo\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class editlicensesform
 * @package block_mbslicenseinfo
 * Main licenses form
 */

class editlicensesform extends \moodleform {
    
    protected function definition() {
        $mform = $this->_form;

        $courseid = $this->_customdata['courseid'];
        $course = get_course($courseid);

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);     

        $files = \block_mbslicenseinfo\local\mbslicenseinfo::get_course_files($courseid);              
        print_r($files);
        foreach ($files as $fid => $file) {
            $filename = $file->filename;
            
            $mform->addElement('static', '', $filename);
            
            $mform->addElement('hidden', 'fileid['.$fid.']', $file->id);
            $mform->setType('fileid['.$fid.']', PARAM_INT);
            
            $mform->addElement('text', 'filename['.$fid.']', get_string('editlicensesformfilename', 'block_mbslicenseinfo'));
            $mform->setType('filename['.$fid.']', PARAM_TEXT);
            $mform->setDefault('filename['.$fid.']', $filename);
            
            $mform->addElement('text', 'title['.$fid.']', get_string('editlicensesformfiletitle', 'block_mbslicenseinfo'));
            $mform->setType('title['.$fid.']', PARAM_TEXT);
            if (!empty($file->title)) {
                $mform->setDefault('title['.$fid.']', $file->title);
            }
            
            $mform->addElement('text', 'filesource['.$fid.']', get_string('editlicensesformfileurl', 'block_mbslicenseinfo'));
            $mform->setType('filesource['.$fid.']', PARAM_TEXT);
            if (!empty($file->source)) {
                $mform->setDefault('filesource['.$fid.']', $file->source);
            }
            
            $mform->addElement('text', 'author['.$fid.']', get_string('editlicensesformfileautor', 'block_mbslicenseinfo'));
            $mform->setType('author['.$fid.']', PARAM_TEXT);
            if (!empty($file->author)) {
                $mform->setDefault('author['.$fid.']', $file->author);
            }
            
            $mform->addElement('hidden', 'licenseid['.$fid.']', $file->license->id);
            $mform->setType('licenseid['.$fid.']', PARAM_INT);            
            
            $mform->addElement('hidden', 'userid['.$fid.']', $file->license->userid);
            $mform->setType('userid['.$fid.']', PARAM_INT);
            
            $mform->addElement('hidden', 'shortname['.$fid.']', $file->license->shortname);
            $mform->setType('shortname['.$fid.']', PARAM_TEXT);            
            
            $licensename = $file->license->fullname;            
            $mform->addElement('text', 'license['.$fid.']', get_string('editlicensesformlicense', 'block_mbslicenseinfo'));
            $mform->setType('license['.$fid.']', PARAM_TEXT);
            if (!empty($licensename)) {
                $mform->setDefault('license['.$fid.']', $licensename);
            }
            
            $mform->addElement('text', 'fullname['.$fid.']', get_string('editlicensesformlicensename', 'block_mbslicenseinfo'));
            $mform->setType('fullname['.$fid.']', PARAM_TEXT);
            if (!empty($licensename)) {
                $mform->setDefault('fullname['.$fid.']', $licensename);
            }
            
            $mform->addElement('text', 'licensesource['.$fid.']', get_string('editlicensesformlicenseurl', 'block_mbslicenseinfo'));
            $mform->setType('licensesource['.$fid.']', PARAM_TEXT);  
            if (!empty($file->license->source)) {
                $mform->setDefault('licensesource['.$fid.']', $file->license->source);
            }
        }
        
        
//        
//        $attributes = array();
//        if (empty($useremail)) {
//            $attributes = array('placeholder' => get_string('complaintformemail_default', 'block_mbslicenseinfo')); 
//        }
//        $mform->addElement('text', 'email', get_string('email'), $attributes); 
//        if (!empty($useremail)) {
//            $mform->setDefault('email', $useremail);
//        }        
//        $mform->setType('email', PARAM_EMAIL);  
//        $mform->addRule('email', get_string('required'), 'required', null, 'client');
//        $mform->addHelpButton('email', 'complaintformemail', 'block_mbslicenseinfo');


        $this->add_action_buttons(true, get_string('submitbutton', 'block_mbslicenseinfo')); 
    }
}