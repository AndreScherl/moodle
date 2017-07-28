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
 * Strings for component 'enrol_mbs', language 'en'.
 *
 * @package    enrol_mbs
 * @copyright  2015 Janek Lasocki-Biczysko, Synergy Learning for ALP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$string['auto_reset'] = 'Auto Reset';
$string['auto_reset_desc'] = 'Whenever a course template is created, automatically create a user data reset schedule for it.';
$string['cron_enable'] = 'Reset Course User Data';
$string['cron_days'] = 'Reset On';
$string['cron_days_desc'] = 'If Auto Reset is enabled, Course User Data will be reset on these days for all newly created templates.';
$string['cron_time'] = 'Reset At';
$string['cron_time_desc'] = 'If Auto Reset is enabled, Course User Data will be reset at this time (on the days configured above) for all newly created templates.';
$string['cron_time_hour'] = 'Hour';
$string['cron_time_minute'] = 'Minute';
$string['errorunabletoresetnontemplate'] = "Unable to reset user data for courses that were not created from a template";
$string['fixbrokencoursesgrades'] = 'Fix/Delete broken course grades';
$string['instanceexists'] = 'There is already an Course User Data Reset with same times.';
$string['instance_save'] = 'Save';
$string['mbs:config'] = 'Configure Template User Data Reset';
$string['pluginname'] = 'Course Template User Data Reset';
$string['pluginname_desc'] = 'A self-enorlment plugin for testing of templates that will reset the course periodically.';
$string['unenrol_role'] = 'Role for unenrol user list';
$string['unenrol_role_desc'] = 'If Auto Reset is enabled, users with this role will be unenroled.'."\n".
        'Please use shortnames! Example: teachsharestudent,teachsharecourseauthor';