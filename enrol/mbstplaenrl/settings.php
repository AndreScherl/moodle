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
 * Mbs tutor auto-enrolment plugin settings.
 *
 * @package    enrol_mbstplaenrl
 * @copyright  2016 Yair Spielmann, Synergy Learning for ALP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $DB;
if ($ADMIN->fulltree) {
    // Load assignable roles.
    $roleobjs = $DB->get_records('role_context_levels', array('contextlevel' => CONTEXT_COURSE), null, 'roleid');
    $rolenames = role_fix_names($roleobjs);
    $options = array();
    foreach ($rolenames as $rolename) {
        $options[$rolename->id] = $rolename->localname;
    }
    $defrole = $DB->get_field('role', 'id', array('shortname' => 'teacher'));
    $settings->add(new admin_setting_configselect('enrol_mbstplaenrl/defaultrole', get_string('defaultrole', 'enrol_mbstplaenrl'),
        '', $defrole, $options));
}