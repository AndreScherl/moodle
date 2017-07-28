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
 * @package    report_mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagner@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_mbs\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/formslib.php');

class bulk_base_form extends \moodleform {

    // Action, that is done by this form.
    protected $bulkaction = 'base';

    protected function definition() {

        $mform = $this->_form;

        $courses = $this->get_courses($this->_customdata['courseids']);
        $mform->addElement('html', $this->render_information($courses));

        $this->special_definition();

        $mform->addElement('hidden', 'courseids');
        $mform->setDefault('courseids', $this->_customdata['courseids']);
        $mform->setType('courseids', PARAM_TEXT);

        $mform->addElement('hidden', 'bulkaction', $this->bulkaction);
        $mform->setType('bulkaction', PARAM_TEXT);

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'confirm', get_string('doaction', 'report_mbs'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Get (and verify) courses for a commy separated list of course ids.
     *
     * @param string $courseidstr comma-separated list of course ids
     * @return array list of courses
     */
    protected function get_courses($courseidstr) {
        global $DB;

        $courseids = explode(",", $courseidstr);

        if (empty($courseids)) {
            print_error('courseidsmissing', 'report_mbs');
        }

        return $DB->get_records_list('course', 'id', $courseids);
    }

    /**
     * Render a list of courses, for that action is performed
     *
     * @param array $courses list of course objects
     * @return string HTML code for display within this form.
     */
    protected function render_courselist($courses) {

        $list = '';
        foreach ($courses as $course) {
            $list .= \html_writer::tag('li', $course->fullname);
        }

        if (!empty($list)) {
            $list = \html_writer::tag('ul', $list);
        }

        return $list;
    }

    /**
     * Render a section of informations for the current action.
     * Maybe overridden in subclasses.
     *
     * @param array $courses list of course objects
     * @return string HTML for displying within form.
     */
    protected function render_information($courses) {

        $o = '';
        $o .= $this->render_courselist($courses);

        return $o;
    }

    /**
     * This method is called from the definition method, to add additonal
     * form fields.
     *
     * May be overrideen.
     */
    protected function special_definition() {

    }

    /**
     * This method is called, when the form was submitted.
     * Mus be overrideen.
     *
     * @param object $data the submitted data.
     */
    public function do_action($data) {
        print_error('mustbeoverridden');
    }

}
