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

require('../../config.php');

global $DB, $PAGE, $OUTPUT;

$courseid   = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT);

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('enrol/mbs:config', $context);

$url = new moodle_url('/enrol/mbs/edit.php', array('courseid'=>$course->id, 'id'=>$instanceid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$return = new moodle_url('/enrol/instances.php', array('id'=>$course->id));
if (!enrol_is_enabled('mbs')) {
    redirect($return);
}

$template = \block_mbstpl\dataobj\template::fetch(array('courseid' => $courseid));
if (!$template) {
    redirect($return);
}

/** @var enrol_mbs_plugin $plugin */
$plugin = enrol_get_plugin('mbs');

if ($instanceid) {
    $instance = enrol_mbs_plugin::get_instance($instanceid, $courseid);

} else {
    require_capability('moodle/course:enrolconfig', $context);
    // No instance yet, we have to add new instance.
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id'=>$course->id)));

    $instance = (object)$plugin->get_instance_defaults();
    $instance->id       = null;
    $instance->courseid = $course->id;
    $instance->status   = ENROL_INSTANCE_ENABLED; // Do not use default for automatically created instances here.
}

$mform = new enrol_mbs\edit_form($url, array($instance));

if ($mform->is_cancelled()) {
    redirect($return);

} else if ($data = $mform->get_data()) {

    if ($instance->id) {

        $instance->customint1      = $plugin->get_customint1($data);
        $instance->customint2      = $plugin->get_customint2($data);
        $instance->customint3      = $plugin->get_customint3($data);
        $instance->customtext1     = $plugin->get_customtext1($data);

        $DB->update_record('enrol', $instance);

    } else {

        $fields = array(
            'customint1'       => $plugin->get_customint1($data),
            'customint2'       => $plugin->get_customint2($data),
            'customint3'       => $plugin->get_customint3($data),
            'customtext1'      => $plugin->get_customtext1($data),
        );

        $instanceid = $plugin->add_instance($course, $fields);

        $instance = enrol_mbs_plugin::get_instance($instanceid, $courseid);
    }

    \enrol_mbs\task\reset_course_userdata_task::schedule_single_reset_task($instance, true);

    redirect($return);
}

$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('pluginname', 'enrol_mbs'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_mbs'));
$mform->display();
echo $OUTPUT->footer();
