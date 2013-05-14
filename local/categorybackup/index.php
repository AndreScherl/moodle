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
 * Starting point for category backup plugin
 *
 * @package    local_categorybackup
 * @copyright  2012 Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE, $OUTPUT;
require_once($CFG->dirroot.'/local/categorybackup/backup_form.php');
require_once($CFG->dirroot.'/local/categorybackup/restore_form.php');

$url = new moodle_url('/local/categorybackup/index.php');
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_categorybackup'));

require_login();
require_capability('local/categorybackup:manage', $context);

$backupform = new local_categorybackup_backup_form();
$restoreform = new local_categorybackup_restore_form();

$backupform->set_data(new stdClass());
$restoreform->set_data(new stdClass());

if ($data = $backupform->get_data()) {
    // Redirect to backup page
    $redir = new moodle_url('/local/categorybackup/backup.php', array('category' => $data->category,
                                                                      'sesskey' => sesskey()));
    redirect($redir);
}

if ($data = $restoreform->get_data()) {
    // Redirect to restore page
    $redir = new moodle_url('/local/categorybackup/restore.php', array('category' => $data->destcategory,
                                                                       'backuppath' => $data->backuppath,
                                                                       'sesskey' => sesskey()));
    redirect($redir);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_categorybackup'));
$backupform->display();
$restoreform->display();
echo $OUTPUT->footer();
