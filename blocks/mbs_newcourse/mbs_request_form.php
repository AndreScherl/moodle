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
 * block_mbs_newcourse course request form
 * 
 * This form is mainly taken from moodle core (see course/request_form.php.
 * 
 * Modifications are made to display:
 * - the recipients (i. e. the users, which can approve the request),
 * - the (fixed) target category and
 * - the related school of the request.
 *
 * @package    block_mbs_newcourse
 * @copyright  2015 Andreas Wagner, ISB Bayern
 * @license    todo
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir . '/formslib.php');

/**
 * A form for a user to request a course.
 */
class mbs_course_request_form extends moodleform {

    public function definition() {
        global $DB, $USER, $PAGE;
        $mform = $this->_form;
        if ($pending = $DB->get_records('course_request', array('requester' => $USER->id))) {
            $mform->addElement('header', 'pendinglist', get_string('coursespending'));
            $list = array();
            foreach ($pending as $cp) {
                $list[] = format_string($cp->fullname);
            }
            $list = implode(', ', $list);
            $mform->addElement('static', 'pendingcourses', get_string('courses'), $list);
        }

        $mform->addElement('header', 'coursedetails', get_string('courserequestdetails'));

        // School name.
        $requestcategory = $this->_customdata['requestcategory'];

        if ($schoolcategory = \local_mbs\local\schoolcategory::get_schoolcategory($requestcategory->id)) {
            $url = new moodle_url('/course/index.php', array('categoryid' => $schoolcategory->id));
            $link = html_writer::link($url, $schoolcategory->name);
            $mform->addElement('static', 'schoolcategory', get_string('schoolcategory', 'block_mbs_newcourse'), $link);
        }

        // ...print out a select box with appropriate course cats (i. e. below school cat).
        $topcategory = ($schoolcategory) ? $schoolcategory : $requestcategory;
        $categories = \local_mbs\local\schoolcategory::make_schoolcategories_list($topcategory);
        $mform->addElement('select', 'category', get_string('requestcategory', 'block_mbs_newcourse'), $categories);
        $mform->setDefault('category', $requestcategory->id);
        $mform->addHelpButton('category', 'requestcategory', 'block_mbs_newcourse');

        // Approvers.
        $renderer = $PAGE->get_renderer('block_mbs_newcourse');
        $approvers = $renderer->render_approvers_list($requestcategory->id);
        $mform->addElement('static', 'approvers', get_string('approvers', 'block_mbs_newcourse'), $approvers);

        $mform->addElement('text', 'fullname', get_string('fullnamecourse'), 'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);

        $mform->addElement('text', 'shortname', get_string('shortnamecourse'), 'maxlength="100" size="20"');
        $mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_TEXT);

        $mform->addElement('editor', 'summary_editor', get_string('summary'), null, course_request::summary_editor_options());
        $mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);

        $mform->addElement('header', 'requestreason', get_string('courserequestreason', 'block_mbs_newcourse'));

        $mform->addElement('textarea', 'reason', get_string('courserequestsupport', 'block_mbs_newcourse'), array('rows' => '15', 'cols' => '50'));
        $mform->addRule('reason', get_string('missingreqreason'), 'required', null, 'client');
        $mform->setType('reason', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('requestcourse'));
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
        $foundcourses = null;
        $foundreqcourses = null;

        if (!empty($data['shortname'])) {
            $foundcourses = $DB->get_records('course', array('shortname' => $data['shortname']));
            $foundreqcourses = $DB->get_records('course_request', array('shortname' => $data['shortname']));
        }
        if (!empty($foundreqcourses)) {
            if (!empty($foundcourses)) {
                $foundcourses = array_merge($foundcourses, $foundreqcourses);
            } else {
                $foundcourses = $foundreqcourses;
            }
        }

        if (!empty($foundcourses)) {
            $foundcoursenames = array();
            foreach ($foundcourses as $foundcourse) {
                if (!empty($foundcourse->requester)) {
                    $pending = 1;
                    $foundcoursenames[] = $foundcourse->fullname . ' [*]';
                } else {
                    $foundcoursenames[] = $foundcourse->fullname;
                }
            }
            $foundcoursenamestring = implode(',', $foundcoursenames);

            $errors['shortname'] = get_string('shortnametaken', '', $foundcoursenamestring);
            if (!empty($pending)) {
                $errors['shortname'] .= get_string('starpending');
            }
        }

        return $errors;
    }



}
