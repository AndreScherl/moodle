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
 * @package local_mbslicenseinfo
 * @copyright 2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mbslicenseinfo\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/mbs/classes/form/MoodleQuickForm_license.php');
require_once($CFG->dirroot . '/local/mbs/classes/form/MoodleQuickForm_newlicense.php');

class editlicensesform extends \moodleform {

    protected function definition() {

        $mform = $this->_form;
        $filesdata = $this->_customdata['filesdata'];
        $locked = $this->_customdata['locked'];

        foreach ($filesdata as $contenthash => $files) {

            $file = reset($files);
            $mform->addElement('header', $contenthash, get_string('relatedfiles', 'local_mbslicenseinfo', $file->filename));

            $i = 0;
            foreach ($files as $fid => $file) {
                // Files.

                $mform->addElement('html', \html_writer::start_div('mbseditlicensegroup' . $i));
                $mform->addElement('hidden', 'fileid[' . $fid . ']', $file->id);
                $mform->addElement('text', 'filename[' . $fid . ']', get_string('editlicensesformfilename', 'local_mbslicenseinfo'), array('disabled' => 'disabled'));
                $mform->setDefault('filename[' . $fid . ']', $file->filename);

                $mform->addElement('text', 'title[' . $fid . ']', get_string('editlicensesformfiletitle', 'local_mbslicenseinfo'));
                if (!empty($file->title)) {
                    $mform->setDefault('title[' . $fid . ']', $file->title);
                }

                $mform->addElement('text', 'filesource[' . $fid . ']', get_string('editlicensesformfileurl', 'local_mbslicenseinfo'));
                if (!empty($file->source)) {
                    $mform->setDefault('filesource[' . $fid . ']', $file->source);
                }

                $mform->addElement('text', 'author[' . $fid . ']', get_string('editlicensesformfileautor', 'local_mbslicenseinfo'));
                if (!empty($file->author)) {
                    $mform->setDefault('author[' . $fid . ']', $file->author);
                }

                $mform->setTypes(array(
                    'fileid[' . $fid . ']' => PARAM_INT,
                    'filename[' . $fid . ']' => PARAM_TEXT,
                    'title[' . $fid . ']' => PARAM_TEXT,
                    'filesource[' . $fid . ']' => PARAM_TEXT,
                    'author[' . $fid . ']' => PARAM_TEXT
                ));

                // License.
                $mform->addElement('hidden', 'licenseid[' . $fid . ']', $file->license->id);

                $mform->addElement('hidden', 'licenseuserid[' . $fid . ']', $file->license->userid);
                $mform->setTypes(array(
                    'licenseid[' . $fid . ']' => PARAM_INT,
                    'licenseuserid[' . $fid . ']' => PARAM_INT
                ));

                // License drop down
                // @TODO: License Dropdown don't cache licenses,
                // so every dropdown needs a database query!
                $licensegr = array();
                $licensegr[0] = $mform->createElement('license', 'licenseshortname[' . $fid . ']', get_string('editlicensesformlicense', 'local_mbslicenseinfo'), null, true);
                $licensegr[1] = $mform->createElement('text', 'licensefullname[' . $fid . ']', '', array('placeholder' => get_string('newlicense_fullname', 'local_mbs')));
                $licensegr[2] = $mform->createElement('text', 'licensesource[' . $fid . ']', '', array('placeholder' => get_string('newlicense_source', 'local_mbs')));
                $mform->setTypes(array(
                    'licensefullname[' . $fid . ']' => PARAM_TEXT,
                    'licensesource[' . $fid . ']' => PARAM_URL
                ));
                $licensegroup = $mform->createElement('group', 'license', get_string('license'), $licensegr, null, false);
                $mform->addElement($licensegroup);
                $licensename = $file->license->shortname;
                if (!empty($licensename)) {
                    $licensegr[0]->setSelected($licensename);
                }
                $mform->addElement('html', \html_writer::end_div());

                $i++;
                $i = $i % 2;

                if ($locked) {

                    $licensegr[0]->freeze();
                    $licensegr[1]->updateAttributes(array('style' => 'display:none'));
                    $licensegr[2]->updateAttributes(array('style' => 'display:none'));
                    
                    $mform->freeze(array(
                        'fileid[' . $fid . ']',
                        'filename[' . $fid . ']',
                        'title[' . $fid . ']',
                        'filesource[' . $fid . ']',
                        'author[' . $fid . ']'
                    ));
                }
            }
        }

        if (empty($filesdata)) {

            $mform->addElement('html', get_string('nolicensestoedit', 'local_mbslicenseinfo'));
            $mform->addElement('cancel');
        } else {

            if (!$locked) {
                $this->add_action_buttons(true, get_string('submitbutton', 'local_mbslicenseinfo'));
            } else {
                $mform->addElement('cancel');
            }
        }

        $this->init_js();
    }

    /**
     * Load the js
     * 
     * @global type $PAGE
     */
    private function init_js() {
        global $PAGE;
        $args = array();
        $PAGE->requires->yui_module('moodle-local_mbs-newlicense', 'M.local_mbs.newlicense.init', $args, null, true);
    }

    /**
     * Validate the data
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = array();

        if (!empty($data['licenseshortname'])) {

            // Are there new licenses without fullname?
            foreach ($data['licenseshortname'] as $key => $shortname) {

                if ($shortname == '__createnewlicense__') {
                    if (empty($data['licensefullname'][$key])) {
                        return array("licensefullname[$key]" => get_string('validation_error_nofullname', 'local_mbslicenseinfo'));
                    }
                }
            }
        }

        return $errors;
    }

}
