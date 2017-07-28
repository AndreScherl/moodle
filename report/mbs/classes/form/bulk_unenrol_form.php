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
 * Form for bulkunenrol action. Extends base form.
 *
 * @package    report_mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagner@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_mbs\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/formslib.php');

class bulk_unenrol_form extends bulk_base_form {

    protected $bulkaction = 'unenrol';

    protected function render_information($courses) {

        $o = '';
        $o .= \html_writer::tag('h2', get_string('bulkaction_unenrol', 'report_mbs'));
        $o .= \html_writer::tag('p', get_string('bulkactionunenrolinfo', 'report_mbs'));
        $o .= $this->render_courselist($courses);

        return $o;
    }

    /**
     * Function to get all enrolled users of a course.
     * @param int $courseid
     * @return array $enrolments
     */
    protected static function get_all_enrolled_users($courseid) {
        global $DB;
        $enrolments = $DB->get_records('enrol', array('courseid' => $courseid));
        if (!empty($enrolments)) {
            list($searchcriteria, $params) = $DB->get_in_or_equal(array_keys($enrolments), SQL_PARAMS_NAMED);
            $searchcriteria = 'enrolid ' . $searchcriteria;
            $userenrolments = $DB->get_records_select('user_enrolments', $searchcriteria, $params);

            return $userenrolments;
        }
        return array();
    }

    /**
     * Function to unenrol all users for given enrolements of a course.
     * @param int $courseid
     * @param array $enrolments
     * @return void
     */
    protected function unenrol_users($course) {

        $enrolments = $this->get_all_enrolled_users($course->id);

        if (empty($enrolments)) {
            return true;
        }

        $plugins = enrol_get_plugins(true);
        $instances = enrol_get_instances($course->id, true);

        foreach ($instances as $key => $instance) {
            if (!isset($plugins[$instance->enrol])) {
                unset($instances[$key]);
                continue;
            }
        }

        foreach ($enrolments as $ue) {
            if (!isset($instances[$ue->enrolid])) {
                continue;
            }
            $instance = $instances[$ue->enrolid];
            $plugin = $plugins[$instance->enrol];
            if (!$plugin->allow_unenrol($instance) and ! $plugin->allow_unenrol_user($instance, $ue)) {
                continue;
            }
            $plugin->unenrol_user($instance, $ue->userid);
        }

        return true;
    }

    public function do_action($data) {

        ini_set('max_execution_time', 0);

        $courses = $this->get_courses($data->courseids);

        if (empty($courses)) {
            return ['error' => '1', 'message' => get_string('coursesmissing', 'report_mbs')];
        }

        foreach ($courses as $course) {
            $this->unenrol_users($course);
        }

        // Update stats data for the courses.
        \report_mbs\local\reportcourses::update_course_stats_data(array_keys($courses));

        return ['error' => '0', 'message' => get_string('coursesunenrolled', 'report_mbs')];
    }

}
