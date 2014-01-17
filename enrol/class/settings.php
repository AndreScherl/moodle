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
 * Class enrolment plugin settings and presets.
 *
 * @package    enrol_class
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_class_settings', '', get_string('pluginname_desc', 'enrol_class')));


    //--- enrol instance defaults ----------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_class_defaults', get_string('enrolinstancedefaults', 'admin'),
                                             get_string('enrolinstancedefaults_desc', 'admin')));

    $settings->add(new admin_setting_configcheckbox('enrol_class/defaultenrol', get_string('defaultenrol', 'enrol'),
                                                    get_string('defaultenrol_desc', 'enrol'), 1));

    $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                     ENROL_INSTANCE_DISABLED => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_class/status', get_string('status', 'enrol_class'),
                                                  get_string('status_desc', 'enrol_class'), ENROL_INSTANCE_ENABLED, $options));

    if (!during_initial_install()) {
        $options = get_roles_with_capability('enrol/class:assignable', CAP_ALLOW, context_system::instance());
        $options = role_fix_names($options, null, ROLENAME_BOTH, true);
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_class/roleid', get_string('defaultrole', 'role'),
                                                      '', $student->id, $options));
    }

    //--- more global settings -------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_class_more', get_string('moreglobalsettings', 'enrol_class'), ''));

    $userfields = array('id', 'auth', 'confirmed', 'policyagreed', 'deleted', 'suspended', 'mnethostid', 'username', 'password',
                     'idnumber', 'firstname', 'lastname', 'email', 'emailstop', 'icq', 'skype', 'yahoo', 'aim', 'msn', 'phone1',
                     'phone2', 'institution', 'department', 'address', 'city', 'country', 'lang', 'theme', 'timezone',
                     'firstaccess', 'lastaccess', 'lastlogin', 'currentlogin', 'lastip', 'secret', 'picture', 'url', 'description',
                     'descriptionformat', 'mailformat', 'maildigest', 'maildisplay', 'htmleditor', 'autosubscribe', 'trackforums',
                     'timecreated', 'timemodified', 'trustbitmask', 'imagealt');
    $options = array_combine($userfields, $userfields);

    $settings->add(new admin_setting_configselect('emrol_class/user_field_classname',
                                                  get_string('userfieldclassname', 'enrol_class'),
                                                  get_string('userfieldclassname_desc', 'enrol_class'), 'department', $options));

    $settings->add(new admin_setting_configselect('emrol_class/user_field_schoolid',
                                                  get_string('userfieldschoolid', 'enrol_class'),
                                                  get_string('userfieldschoolid_desc', 'enrol_class'), 'institution', $options));

    $options = array(
        ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol'),
        ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'));
    $settings->add(new admin_setting_configselect('enrol_cohort/unenrolaction', get_string('extremovedaction', 'enrol'),
                                                  get_string('extremovedaction_help', 'enrol'), ENROL_EXT_REMOVED_UNENROL,
                                                  $options));

}
