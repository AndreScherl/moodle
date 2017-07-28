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
 * Mbs tutor auto-enrolment plugin.
 *
 * @package    enrol_mbstplaenrl
 * @copyright  2016 Yair Spielmann, Synergy Learning for ALP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \block_mbstpl as mbst;

/**
 * MBS Template enrolment plugin
 */
class enrol_mbstplaenrl_plugin extends enrol_plugin {

   /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/mbstplaenrl:config', $context);
    } 
    
    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param object $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/mbstplaenrl:config', $context);
    }
    
    /**
     * Get the record for a course instance of this plugin
     *
     * @param int $id
     * @param int $courseid
     */
    public static function get_instance($id, $courseid = null) {
        global $DB;
        return $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'mbstplaenrl', 'id' => $id), '*', MUST_EXIST);
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
        return new moodle_url('/enrol/mbstplaenrl/edit.php', array('courseid' => $courseid));
    }

    /**
     * Returns edit icons for the page with list of instances
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        if ($instance->enrol !== 'mbstplaenrl') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);

        $icons = array();

        if (has_capability('enrol/mbstplaenrl:config', $context)) {
            $editlink = new moodle_url("/enrol/mbstplaenrl/edit.php", array('courseid'=>$instance->courseid, 'id'=>$instance->id));
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
        $fields['roleid']          = $this->get_config('defaultrole');

        return $fields;
    }

    public function allow_unenrol(stdClass $instance) {
        return true;
    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance) {
        global $DB, $USER;

        // Check instance.
        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            return get_string('canntenrol', 'enrol_self');
        }
        if ($instance->enrolstartdate != 0 and $instance->enrolstartdate > time()) {
            return get_string('canntenrol', 'enrol_self');
        }

        if ($instance->enrolenddate != 0 and $instance->enrolenddate < time()) {
            return get_string('canntenrol', 'enrol_self');
        }

        // Cannot enrol guest.
        if (isguestuser()) {
            return get_string('noguestaccess', 'enrol');
        }

        // Only enrol template searching tutors.
        if (!mbst\perms::can_searchtemplates()) {
            return get_string('noguestaccess', 'enrol');
        }

        if ($DB->record_exists('user_enrolments', array('userid' => $USER->id, 'enrolid' => $instance->id))) {
            return get_string('canntenrol', 'enrol_self');
        }

        // Template must be published and course visible.
        if(!$template = mbst\dataobj\template::fetch(array('courseid' => $instance->courseid))) {
            return get_string('canntenrol', 'enrol_self');
        }
        if ($template->status != $template::STATUS_PUBLISHED) {
            return get_string('canntenrol', 'enrol_self');
        }
        if(!$visible = $DB->get_field('course', 'visible', array('id' => $instance->courseid))) {
            return get_string('canntenrol', 'enrol_self');
        }

        $timestart = time();
        if ($instance->enrolperiod) {
            $timeend = $timestart + $instance->enrolperiod;
        } else {
            $timeend = 0;
        }

        $this->enrol_user($instance, $USER->id, $instance->roleid, $timestart, $timeend);
    }
    
    /**
     * Return an array of valid options for the status.
     *
     * @return array
     */
    protected function get_status_options() {
        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        return $options;
    }
    
    /**
     * Return an array of valid options for the roles.
     *
     * @param stdClass $instance
     * @param context $coursecontext
     * @return array
     */
    protected function get_role_options($instance, $coursecontext) {
        global $DB;

        $roles = get_assignable_roles($coursecontext);
        $roles[0] = get_string('none');
        $roles = array_reverse($roles, true); // Descending default sortorder.
        if ($instance->id and !isset($roles[$instance->roleid])) {
            if ($role = $DB->get_record('role', array('id' => $instance->roleid))) {
                $roles = role_fix_names($roles, $coursecontext, ROLENAME_ALIAS, true);
                $roles[$instance->roleid] = role_get_name($role, $coursecontext);
            } else {
                $roles[$instance->roleid] = get_string('error');
            }
        }

        return $roles;
    }
    
    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname" => value) of submitted data
     * @param array $files array of uploaded files "element_name" => tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name" => "error_description" if there are errors,
     *         or an empty array if everything is OK.
     * @return void
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        global $DB;

        $validstatus = array_keys($this->get_status_options());
        $validroles = array_keys($this->get_role_options($instance, $context));
        $tovalidate = array(
            'status' => $validstatus,
            'roleid' => $validroles
        );
        $errors = $this->validate_param_types($data, $tovalidate);

        return $errors;
    }
}
