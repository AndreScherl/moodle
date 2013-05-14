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
 * English language file for category backup plugin
 *
 * @package    local_categorybackup
 * @copyright  2012 Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['backupcategory'] = 'Backup category';
$string['backupcourse'] = 'Backup course: {$a}';
$string['backuppath'] = 'Backup path';
$string['backuppath_help'] = 'Full local path to where the backup is saved on this Moodle server (e.g. /var/moodle_data/backup-category.zip)';
$string['cannotcreatecategory'] = 'Error creating category \'{$a}\'';
$string['category'] = 'Backup category';
$string['category_help'] = 'The selected category (including sub-categories and courses) will be backed up to a single zip file';
$string['categorybackup:manage'] = 'Manage backup and restore of categories';
$string['coursealreadyexists'] = 'A course called \'{$a->shortname}\' already exists in \'{$a->category}\' - restore skipped';
$string['createdbackup'] = 'Finished creating backup: \'{$a}\'';
$string['createdcategory'] = 'Created category \'{$a}\'';
$string['destcategory'] = 'Parent category';
$string['destcategory_help'] = "If you backed up category 'CatA' and want this to appear as a top-level category, then select 'Top'.\nIf you want this to appear within another category, then select the name of that category (e.g. selecting 'CatB' would result in 'CatB/CatA/' containing all the courses & sub-categories you backed up)";
$string['dobackup'] = 'Start backup';
$string['dorestore'] = 'Start restore';
$string['existingcategory'] = 'Using existing category \'{$a}\'';
$string['invalidcategory'] = 'Non-existent category specified';
$string['invalidextension'] = 'Expected the category backup to end .zip or .tgz';
$string['invalidpath'] = 'The file \'{$a}\' is not a category backup';
$string['missingcatfile'] = 'The backup is missing the \'categories.lst\' file';
$string['nobackuppath'] = 'No backup path specified, e.g. php restore.cli.php /var/moodledata/category.zip [parent_categoryid]';
$string['outputcategories'] = 'Exporting category list';
$string['pluginname'] = 'Category backup';
$string['restorecategory'] = 'Restore category';
$string['restorecomplete'] = 'Restore complete';
$string['restorecourse'] = 'Restoring course \'{$a->fullname} ({$a->shortname})\'';
$string['restoringcourses'] = 'Restoring courses';
$string['startingbackup'] = 'Backing up all courses in \'{$a}\'';
$string['startingrestore'] = 'Restoring courses and categories into \'{$a}\'';
$string['unziperror'] = 'An error occurred whilst extracting files from \'{$a}\'';
$string['ziperror'] = 'An error occurred whilst creating the final backup zip file';