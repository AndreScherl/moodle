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
 * Adds new instance of enrol_mbs to specified course
 * or edits current instance.
 *
 * @package    enrol_mbs
 * @copyright  2015 Janek Lasocki-Biczysko, Synergy Learning for ALP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_mbs\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class to leverage \core\task\scheduled_task->get_next_scheduled_time
 *
 */
class task_helper extends \core\task\scheduled_task {

    /**
     * Get the next scheduled time for an enrol_mbs instance
     *
     * @param \stdClass $instance the instance data (ie. db record)
     */
    public static function next_scheduled_time_from_enrol($instance) {

        if ($instance->enrol != 'mbs') {
            return null;
        }

        // The 'customint1' property is the 'enabled' flag.
        if (!$instance->customint1) {
            return null;
        }

        return self::_calculate_next_scheduled_time($instance->customtext1, $instance->customint2, $instance->customint3);
    }

    private static function _calculate_next_scheduled_time($crondays, $cronhour, $cronminute) {

        $task = new task_helper();
        $task->set_day_of_week($crondays);
        $task->set_hour($cronhour);
        $task->set_minute($cronminute);

        return $task->get_next_scheduled_time();
    }

    private function __construct() { }

    public function execute() { }

    public function get_name() {
        return "MBS Enrollment task_helper";
    }
}
