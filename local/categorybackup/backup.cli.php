<?php
// This file is part of the category backup plugin for Moodle - http://moodle.org/
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
 * File to start category backup in category backup plugin
 *
 * @package    local_categorybackup
 * @copyright  2012 Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT',true);

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $DB, $OUTPUT, $USER;
require_once($CFG->dirroot.'/local/categorybackup/lib.php');
require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot.'/backup/util/helper/backup_cron_helper.class.php');

require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->libdir.'/cronlib.php');

if (!isset($argv[1])) {
    die("No cat code given\n");
}
$categoryid = $argv[1];
cron_setup_user();

// Get a list of all the courses & categories in the selected category
$category = $DB->get_record('course_categories', array('id' => $categoryid));
if (!$category) {
    throw new moodle_exception('invalidcategory', 'local_categorybackup');
}
$catinfo = categorybackup::get_courses_and_categories($category);

// Settings to use (backup everything)
$overridesettings = array(
    'backup_auto_users' => 1,
    'backup_auto_role_assignments' => 1,
    'backup_auto_user_files' => 1,
    'backup_auto_activities' => 1,
    'backup_auto_blocks' => 1,
    'backup_auto_filters' => 1,
    'backup_auto_comments' => 1,
    'backup_auto_userscompletion' => 1,
    'backup_auto_logs' => 0,
    'backup_auto_histories' => 1,
    'backup_auto_destination' => $CFG->dataroot.'/categorybackup',
    'backup_auto_storage' => 1
);

// Make sure the dest directory exists and is empty (delete any existing files)
$destdir = $overridesettings['backup_auto_destination'];

if (!file_exists($destdir)) {
    mkdir($destdir, 0777, true);
}

categorybackup::delete_files($destdir);

// Backup and override the 'auto backup' settings
$saveconfig = get_config('backup');
foreach ($overridesettings as $setting => $value) {
    set_config($setting, $value, 'backup');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_categorybackup'));

echo get_string('startingbackup', 'local_categorybackup', $category->name)."\n";

foreach ($catinfo as $category) {
    foreach ($category->courses as $course) {
        echo "* ".get_string('backupcourse', 'local_categorybackup', $course->shortname)."\n";
        flush();
        backup_cron_automated_helper::launch_automated_backup($course, time(), $USER->id);
    }
}

echo get_string('outputcategories', 'local_categorybackup')."\n";
$fp = fopen($destdir.'/categories.lst', 'w');
categorybackup::export_categories($catinfo, $category->id, $fp);
fclose($fp);

$zipfilename = categorybackup::tgz_files($destdir, $category);
if (!$zipfilename) {
    echo get_string('ziperror', 'local_categorybackup');
} else {
    echo get_string('createdbackup', 'local_categorybackup', $zipfilename)."\n";
}
categorybackup::delete_files($destdir);

// Restore the original 'auto backup' settings
foreach ($overridesettings as $setting => $value) {
    if (isset($saveconfig->$setting)) {
        set_config($setting, $saveconfig->$setting, 'backup');
    }
}
