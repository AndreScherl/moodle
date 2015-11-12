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

class reset_course_userdata_task extends \core\task\adhoc_task {

    /**
     * Schedule a single "user data reset" task for an enrol_mbs instance.
     *
     * @param stdClass $instance enrol_mbs plugin instance
     * @param boolean $updateexisting update existing scheduled tasks instead of creating new ones
     */
    public static function schedule_single_reset_task($instance, $updateexisting = false) {

        global $DB;

        $nextruntime = task_helper::next_scheduled_time_from_enrol($instance);
        if ($nextruntime !== null) {

            $task = new reset_course_userdata_task();
            $task->set_custom_data(array('courseid' => $instance->courseid, 'instanceid' => $instance->id));
            $task->set_next_run_time($nextruntime);

            $record = $updateexisting ? self::get_task_record($task) : null;

            if ($record) {
                $record->nextruntime = $nextruntime;
                $DB->update_record('task_adhoc', $record);
            } else {
                $record = \core\task\manager::record_from_adhoc_task($task);
                $DB->insert_record('task_adhoc', $record);
            }
        }
    }

    /**
     * Get the task record for a task (matches 'component' and 'customdata')
     *
     * @param reset_course_userdata_task $task
     */
    private static function get_task_record(reset_course_userdata_task $task) {
        global $DB;
        return $DB->get_record_select(
            'task_adhoc',
            "{$DB->sql_compare_text('customdata')} = ? AND {$DB->sql_compare_text('component')} = ?",
            array($task->get_custom_data_as_string(), $task->get_component()));
    }

    public function __construct() {
        $this->set_component('enrol_mbs');
    }

    /**
     * Resets all course user data and schedules another (adhoc) task
     *
     * @see \core\task\task_base::execute()
     */
    public function execute() {

        global $CFG;

        $data = $this->get_custom_data();
        if (empty($data->courseid)) {
            throw new \moodle_exception("course reset task is missing courseid");
        }

        \enrol_mbs\reset_course_userdata::reset_course_from_template($data->courseid);

        require_once("$CFG->dirroot/enrol/mbs/lib.php");
        $instance = \enrol_mbs_plugin::get_instance($data->instanceid, $data->courseid);

        self::schedule_single_reset_task($instance);
    }

}
