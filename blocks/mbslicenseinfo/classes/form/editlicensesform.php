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
require_once($CFG->dirroot . '/local/mbs/classes/form/MoodleQuickForm_license.php');
require_once($CFG->dirroot . '/local/mbs/classes/form/MoodleQuickForm_newlicense.php');

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
       
        foreach ($files as $fid => $file) {
            //Files.
            $filename = $file->filename;
            
            $mform->addElement('static', '', $filename);            
            $mform->addElement('hidden', 'fileid['.$fid.']', $file->id);            
            $mform->addElement('text', 'filename['.$fid.']', get_string('editlicensesformfilename', 'block_mbslicenseinfo'));
            $mform->setDefault('filename['.$fid.']', $filename); 
            
            $mform->addElement('text', 'title['.$fid.']', get_string('editlicensesformfiletitle', 'block_mbslicenseinfo'));
            if (!empty($file->title)) {
                $mform->setDefault('title['.$fid.']', $file->title);
            }
            
            $mform->addElement('text', 'filesource['.$fid.']', get_string('editlicensesformfileurl', 'block_mbslicenseinfo'));
            if (!empty($file->source)) {
                $mform->setDefault('filesource['.$fid.']', $file->source);
            }
            
            $mform->addElement('text', 'author['.$fid.']', get_string('editlicensesformfileautor', 'block_mbslicenseinfo'));
            if (!empty($file->author)) {
                $mform->setDefault('author['.$fid.']', $file->author);
            }
            
            $mform->setTypes(array(
                'fileid['.$fid.']' => PARAM_INT,
                'filename['.$fid.']' => PARAM_TEXT,
                'title['.$fid.']' => PARAM_TEXT,
                'filesource['.$fid.']' => PARAM_TEXT,
                'author['.$fid.']' => PARAM_TEXT                
            ));
            
            // License.
            $mform->addElement('hidden', 'licenseid['.$fid.']', $file->license->id);
            $mform->addElement('hidden', 'userid['.$fid.']', $file->license->userid);
            $mform->addElement('hidden', 'shortname['.$fid.']', $file->license->shortname); 
            $mform->setTypes(array(
                'licenseid['.$fid.']' => PARAM_INT,
                'userid['.$fid.']' => PARAM_INT,
                'shortname['.$fid.']' => PARAM_TEXT
            ));
            
            $licensename = $file->license->fullname;  

            // License. - drop down
            $licensegr = array();
            $licensegr[0] = $mform->createElement('license', 'asset_license['.$fid.']', get_string('editlicensesformlicense', 'block_mbslicenseinfo'), null, true);
            //leider immer zu sehen!
            $licensegr[1] = $mform->createElement('text', 'newlicense_fullname['.$fid.']', '', array('placeholder' => get_string('newlicense_fullname', 'local_mbs')));
            $licensegr[2] = $mform->createElement('text', 'newlicense_source['.$fid.']', '', array('placeholder' => get_string('newlicense_source', 'local_mbs')));
            $mform->setTypes(array(
                'newlicense_fullname['.$fid.']' => PARAM_TEXT,
                'newlicense_source['.$fid.']' => PARAM_URL
            ));
            
            $mform->addElement('group', 'newlicense', '', $licensegr, null, false);
            
            //geht nicht!
            if (!empty($licensename)) {
                $licensegr[0]->setSelected($licensename);
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

        $this->add_action_buttons(true, get_string('submitbutton', 'block_mbslicenseinfo')); 
    }
        
}