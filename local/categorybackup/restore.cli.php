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
 * Start a category restore in category backup plugin
 *
 * @package    local_categorybackup
 * @copyright  2012 Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT',true);

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE, $DB, $OUTPUT;
require_once($CFG->dirroot.'/local/categorybackup/lib.php');

require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->libdir.'/cronlib.php');

cron_setup_user();

if (empty($argv[1])) {
    throw new moodle_exception('nobackuppath', 'local_categorybackup');
}
$backuppath = $argv[1];
$categoryid = 0;
if (isset($argv[2])) {
    $categoryid = intval($argv[2]);
}

echo "|$backuppath|\n";

$url = new moodle_url('/local/categorybackup/index.php'); // Don't want to kick-off a second restore by accident
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_categorybackup'));

if ($categoryid != 0) {
    $category = $DB->get_record('course_categories', array('id' => $categoryid));
    if (!$category) {
        throw new moodle_exception('invalidcategory', 'local_categorybackup');
    }
} else {
    $category = new stdClass();
    $category->id = 0;
    $category->name = get_string('top');
}

if (!file_exists($backuppath) || is_dir($backuppath)) {
    print_error('invalidpath', 'local_categorybackup', $url, $backuppath);
}

// Ensure the folder to restore from exists and is empty
$srcdir = $CFG->dataroot.'/categorybackup';

if (!file_exists($srcdir)) {
    mkdir($srcdir, 0777, true);
}

categorybackup::delete_files($srcdir);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_categorybackup'));

echo get_string('startingrestore', 'local_categorybackup', $category->name)."\n";

// Extract the files to the dest folder
if (strtolower(substr($backuppath, -4)) == '.zip') {
    if (!categorybackup::unzip_files($backuppath, $srcdir)) {
        print_error('unziperror', 'local_categorybackup', $backuppath, $url);
    }
} else if (strtolower(substr($backuppath, -4)) == '.tgz') {
    if (!categorybackup::untgz_files($backuppath, $srcdir)) {
        print_error('unziperror', 'local_categorybackup', $backuppath, $url);
    }
} else {
    print_error('invalidextension', 'local_categorybackup');
}

// Create the categories
$catfile = $srcdir.'/categories.lst';
if (!file_exists($catfile)) {
    print_error('missingcatfile', 'local_categorybackup');
}
$newcats = file($catfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$categorymapping = categorybackup::create_categories($newcats, $category);

echo get_string('restoringcourses', 'local_categorybackup')."\n";
categorybackup::restore_courses($srcdir, $categorymapping);

categorybackup::delete_files($srcdir);

fix_course_sortorder();

echo get_string('restorecomplete', 'local_categorybackup')."\n";

