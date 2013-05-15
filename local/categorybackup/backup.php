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

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE, $DB, $OUTPUT, $USER;
require_once($CFG->dirroot.'/local/categorybackup/lib.php');
require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot.'/backup/util/helper/backup_cron_helper.class.php');

$categoryid = required_param('category', PARAM_INT);

$url = new moodle_url('/local/categorybackup/backup.php', array('category' => $categoryid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_categorybackup'));

require_login();
require_capability('local/categorybackup:manage', $context);
require_sesskey();

// Get a list of all the courses & categories in the selected category
if ($categoryid == 0) { // Backup all categories
    $category = (object)array(
        'id' => 0,
        'name' => get_string('allcategories', 'local_categorybackup'),
    );
} else {
    $category = $DB->get_record('course_categories', array('id' => $categoryid));
    if (!$category) {
        throw new moodle_exception('invalidcategory', 'local_categorybackup');
    }
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
    'backup_auto_commnets' => 1,
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

echo html_writer::tag('p', get_string('startingbackup', 'local_categorybackup', $category->name));

echo html_writer::start_tag('ul');
foreach ($catinfo as $cat) {
    foreach ($cat->courses as $course) {
        echo html_writer::tag('li', get_string('backupcourse', 'local_categorybackup', $course->shortname));
        flush();
        backup_cron_automated_helper::launch_automated_backup($course, time(), $USER->id);
    }
}
echo html_writer::end_tag('ul');

echo html_writer::tag('p', get_string('outputcategories', 'local_categorybackup'));
$fp = fopen($destdir.'/categories.lst', 'w');
categorybackup::export_categories($catinfo, $category->id, $fp);
fclose($fp);

$zipfilename = categorybackup::tgz_files($destdir, $category);
if (!$zipfilename) {
    echo html_writer::tag('div', get_string('ziperror', 'local_categorybackup'), array('class' => 'error'));
} else {
    echo '<p>'.get_string('createdbackup', 'local_categorybackup', $zipfilename).'</p>';
    categorybackup::delete_files($destdir);
}

echo $OUTPUT->footer();

// Restore the original 'auto backup' settings
foreach ($overridesettings as $setting => $value) {
    set_config($setting, $saveconfig->$setting, 'backup');
}
