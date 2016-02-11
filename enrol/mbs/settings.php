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
 * Mbs enrolment plugin settings and presets.
 *
 * @package    enrol_mbs
 * @copyright  2015 Janek Lasocki-Biczysko, Synergy Learning for ALP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('enrol_mbs_settings', '', get_string('pluginname_desc', 'enrol_mbs')));

    $settings->add(new admin_setting_configcheckbox('enrol_mbs/auto_reset',
        get_string('auto_reset', 'enrol_mbs'), get_string('auto_reset_desc', 'enrol_mbs'), false));

    $options = enrol_mbs\edit_form::get_days();

    $settings->add(new admin_setting_configmulticheckbox('enrol_mbs/cron_days',
        get_string('cron_days', 'enrol_mbs'), get_string('cron_days_desc', 'enrol_mbs'), array(), $options));

    $settings->add(new admin_setting_configtime('enrol_mbs/cron_hour', 'cron_minute',
        get_string('cron_time', 'enrol_mbs'), get_string('cron_time_desc', 'enrol_mbs'), array('h' => 0, 'm' => 0)));

    $settings->add(new admin_setting_configtext('enrol_mbs/unenrol_role', 
        get_string('unenrol_role', 'enrol_mbs'), get_string('unenrol_role_desc', 'enrol_mbs'), 'teachsharestudent', PARAM_TEXT));
}
