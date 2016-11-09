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
 * Form for bulkdelete action. Extends base form.
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

class bulk_delete_form extends bulk_base_form {

    protected $bulkaction = 'delete';

    protected function render_information($courses) {

        $o = '';
        $o .= \html_writer::tag('h2', get_string('bulkaction_delete', 'report_mbs'));
        $o .= \html_writer::tag('p', get_string('bulkactiondeleteinfo', 'report_mbs'));
        $o .= $this->render_courselist($courses);

        return $o;
    }

    public function do_action($data) {

        ini_set('max_execution_time', 0);

        $courses = $this->get_courses($data->courseids);

        if (empty($courses)) {
            return ['error' => '1', 'message' => get_string('coursesmissing', 'report_mbs')];
        }

        foreach ($courses as $course) {
            delete_course($course, false);
        }

        // Also delete the cached data.
        $courseids = array_keys($courses);
        \report_mbs\local\reportcourses::delete_courses_stats_data($courseids);

        return ['error' => '0', 'message' => get_string('coursesdeleted', 'report_mbs')];
    }
}
