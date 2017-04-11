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
 *
 * @package    report_mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagner@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_mbs\task;

class report_course_stats extends \core\task\scheduled_task {

    public function get_name() {
        // Shown in admin screens.
        return get_string('reportcoursestats', 'report_mbs');
    }

    public function execute() {

        $coursestatscronactiv = get_config('report_mbs', 'coursestatscronactiv');

        if (!empty($coursestatscronactiv)) {
            \report_mbs\local\reportcourses::sync_courses_stats();
        }
    }

}
