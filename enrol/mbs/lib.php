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
 * Mbs enrolment plugin.
 *
 * @package    enrol_mbs
 * @copyright  2015 Janek Lasocki-Biczysko, Synergy Learning for ALP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * MBS Template enrolment plugin
 */
class enrol_mbs_plugin extends enrol_plugin {

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/mbs:config', $context);
    }    
    
    /**
     * Get the record for a course instance of this plugin
     *
     * @param int $id
     * @param int $courseid
     */
    public static function get_instance($id, $courseid = null) {
        global $DB;
        $params = array('enrol' => 'mbs', 'id' => $id);
        if(!empty($courseid)) {
            $params['courseid'] = $courseid;
        }
        return $DB->get_record('enrol', $params, '*', MUST_EXIST);
    }

    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/self:config', $context)) {
            return NULL;
        }

        $template = \block_mbstpl\dataobj\template::fetch(array('courseid' => $courseid));
        if (!$template) {
            return NULL;
        }

        // Multiple instances supported - different roles with different password.
        return new moodle_url('/enrol/mbs/edit.php', array('courseid' => $courseid));
    }

    /**
     * Returns edit icons for the page with list of instances
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        if ($instance->enrol !== 'mbs') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);

        $icons = array();

        if (has_capability('enrol/mbs:config', $context)) {
            $editlink = new moodle_url("/enrol/mbs/edit.php", array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon('t/edit', get_string('edit'), 'core',
                array('class' => 'iconsmall')));
        }

        return $icons;
    }

    /**
     * Returns defaults for new instances.
     * @return array
     */
    public function get_instance_defaults() {

        $fields = array();
        $fields['customint1']      = true;
        $fields['customint2']      = $this->get_config('cron_hour');
        $fields['customint3']      = $this->get_config('cron_minute');
        $fields['customtext1']     = $this->get_config('cron_days');

        return $fields;
    }

    public function get_customint1($data) {
        return isset($data->cron_enable) ? $data->cron_enable : 0;
    }

    public function get_customint2($data) {
        return $data->cron_time['hour'];
    }

    public function get_customint3($data) {
        return $data->cron_time['minute'];
    }

    public function get_customtext1($data) {
        return implode(',', array_keys($data->cron_days));
    }

}
