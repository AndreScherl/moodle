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
 * Adds instance form
 *
 * @package    enrol_class
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class enrol_class_edit_form extends moodleform {

    function definition() {
        global $CFG, $DB, $USER;

        $mform  = $this->_form;

        list($instance, $plugin, $course) = $this->_customdata;
        $coursecontext = context_course::instance($course->id);

        $enrol = enrol_get_plugin('class');


        $groups = array(0 => get_string('none'));
        foreach (groups_get_all_groups($course->id) as $group) {
            $groups[$group->id] = format_string($group->name, true, array('context'=>$coursecontext));
        }

        $mform->addElement('header','general', get_string('pluginname', 'enrol_class'));

        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));

        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        $mform->addElement('select', 'status', get_string('status', 'enrol_class'), $options);

        // SYNERGY LEARNING - display class selector (not cohort selector).
        $schoolfield = get_config('enrol_class', 'user_field_schoolid');
        $schoolid = $USER->{$schoolfield};
        $mform->addElement('static', 'schoolid', get_string('schoolid', 'enrol_class'), $schoolid);
        if ($instance->id) {
            $classes = array($instance->customchar1 => $instance->customchar1);
            $mform->addElement('select', 'customchar1', get_string('class', 'enrol_class'), $classes);
            $mform->setConstant('customchar1', $instance->customchar1);
            $mform->hardFreeze('customchar1');

        } else {
            $classes = array('' => get_string('choosedots'));
            $classlist = enrol_class_get_classes($USER);
            foreach ($classlist as $c) {
                $classes[$c] = format_string($c);
            }
            $mform->addElement('select', 'customchar1', get_string('class', 'enrol_class'), $classes);
            $mform->addRule('customchar1', get_string('required'), 'required', null, 'client');
        }

        // SYNERGY LEARNING - get list of roles that we are allowed to assign.
        $roles = enrol_class_get_available_roles();
        $roles[0] = get_string('none');
        $roles = array_reverse($roles, true); // Descending default sortorder.
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_class'), $roles);
        $mform->setDefault('roleid', $enrol->get_config('roleid'));
        if ($instance->id and !isset($roles[$instance->roleid])) {
            if ($role = $DB->get_record('role', array('id'=>$instance->roleid))) {
                $roles = role_fix_names($roles, $coursecontext, ROLENAME_ALIAS, true);
                $roles[$instance->roleid] = role_get_name($role, $coursecontext);
            } else {
                $roles[$instance->roleid] = get_string('error');
            }
        }
        $mform->addElement('select', 'customint2', get_string('addgroup', 'enrol_class'), $groups);

        // SYNERGY LEARNING - allow synchronisation to be disabled (whilst still leaving the plugin enroled).
        $mform->addElement('selectyesno', 'customint3', get_string('syncmembers', 'enrol_class'));

        $mform->addElement('hidden', 'courseid', null);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        if ($instance->id) {
            $this->add_action_buttons(true);
        } else {
            $this->add_action_buttons(true, get_string('addinstance', 'enrol'));
        }

        $this->set_data($instance);
    }

    function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        // SYNERGY LEARNING - check for duplicate enrolment instances.
        $instance = $this->_customdata[0];
        $params = array('roleid'=>$data['roleid'], 'customchar1'=>$data['customchar1'], 'customchar2' => $instance->customchar2,
                        'courseid'=>$data['courseid'], 'id'=>$data['id']);
        if ($DB->record_exists_select('enrol', "roleid = :roleid AND customchar1 = :customchar1 AND customchar2 = :customchar2
                                                AND courseid = :courseid AND enrol = 'class' AND id <> :id", $params)) {
            $errors['roleid'] = get_string('instanceexists', 'enrol_class');
        }

        return $errors;
    }
}
