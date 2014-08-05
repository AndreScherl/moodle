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
 * Adds new instance of enrol_class to specified course.
 *
 * @package    enrol_class
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/enrol/class/edit_form.php");
require_once("$CFG->dirroot/enrol/class/locallib.php");
require_once("$CFG->dirroot/group/lib.php");

$courseid = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT);

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('moodle/course:enrolconfig', $context);
require_capability('enrol/class:config', $context);

$PAGE->set_url('/enrol/class/edit.php', array('courseid'=>$course->id, 'id'=>$instanceid));
$PAGE->set_pagelayout('admin');

$returnurl = new moodle_url('/enrol/instances.php', array('id'=>$course->id));
if (!enrol_is_enabled('class')) {
    redirect($returnurl);
}

$enrol = enrol_get_plugin('class');
$schoolfield = get_config('enrol_class', 'user_field_schoolid');

if ($instanceid) {
    $instance = $DB->get_record('enrol', array('courseid'=>$course->id, 'enrol'=>'class', 'id'=>$instanceid), '*', MUST_EXIST);

} else {
    // No instance yet, we have to add new instance.
    if (!$enrol->get_newinstance_link($course->id)) {
        redirect($returnurl);
    }
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id'=>$course->id)));
    $instance = new stdClass();
    $instance->id         = null;
    $instance->courseid   = $course->id;
    $instance->enrol      = 'class';
    $instance->customchar1 = ''; // SYNERGY LEARNING: Class name.
    $instance->customchar2 = $USER->{$schoolfield}; // SYNERGY LEARNING: School id.
    $instance->customint2 = 0;  // Optional group id.
    $instance->customint3 = 0;  // SYNERGY LEARNING: Sync enabled.
}

// Try and make the manage instances node on the navigation active.
$courseadmin = $PAGE->settingsnav->get('courseadmin');
if ($courseadmin && $courseadmin->get('users') && $courseadmin->get('users')->get('manageinstances')) {
    $courseadmin->get('users')->get('manageinstances')->make_active();
}


$mform = new enrol_class_edit_form(null, array($instance, $enrol, $course));

if ($mform->is_cancelled()) {
    redirect($returnurl);

} else if ($data = $mform->get_data()) {
    $trace = new null_progress_trace();
    if ($data->id) {
        // NOTE: no class changes here!!!
        if ($data->roleid != $instance->roleid) {
            // The sync script can only add roles, for perf reasons it does not modify them.
            role_unassign_all(array('contextid'=>$context->id, 'roleid'=>$instance->roleid, 'component'=>'enrol_class', 'itemid'=>$instance->id));
        }
        $instance->name         = $data->name;
        $instance->status       = $data->status;
        $instance->roleid       = $data->roleid;
        $instance->customint2   = $data->customint2;
        $instance->customint3   = $data->customint3;
        $instance->timemodified = time();
        $DB->update_record('enrol', $instance);
        enrol_class_sync($trace, $course->id); // SYNERGY LEARNING: Synchronise, if 'sync members' is enabled.
    }  else {
        // SYNERGY LEARNING - save different params from enrol_cohort.
        $globalconfig = get_config('enrol_class');
        $instanceid = $enrol->add_instance($course, array('name'=>$data->name, 'status'=>$data->status,
                                                          'customchar1'=>$data->customchar1,
                                                          'customchar2' => $USER->{$schoolfield},
                                                          'customint3' => $data->customint3,
                                                          'roleid'=>$data->roleid, 'customint2'=>$data->customint2));
        // When creating a new instance, force update even if 'sync members' is off.
        enrol_class_sync($trace, $course->id, $instanceid);
    }
    $trace->finished();
    redirect($returnurl);
}

$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('pluginname', 'enrol_class'));

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
