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
 * @package block_mbstpl
 * @copyright 2016 Andreas Wagner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $PAGE, $USER, $DB, $OUTPUT;

use \block_mbstpl AS mbst;

$courseid = required_param('course', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_INT);
$fileid = optional_param('fileid', 0, PARAM_INT);

$thisurl = new moodle_url('/blocks/mbstpl/publishedbackup.php', array('course' => $courseid));
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('incourse');

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$courseurl = new moodle_url('/course/view.php', array('id' => $courseid));

require_login($courseid, false);
$coursecontext = context_course::instance($courseid);

$template = new \block_mbstpl\dataobj\template(array('courseid' => $courseid), true);
$backup = new \block_mbstpl\dataobj\backup(array('id' => $template->backupid), true, MUST_EXIST);

if (!\block_mbstpl\perms::can_createdpublishedbackup($template, $coursecontext)) {
    print_error('missingcapability');
}

$rethrowexception = false;

if ($action == 'createnewbackupfile') {

    try {
        \block_mbstpl\backup::backup_published($courseid, $template);
        redirect($thisurl, get_string('backupcreated', 'block_mbstpl'));
    } catch (\moodle_exception $e) {
        \block_mbstpl\notifications::notify_error('errordeploying', $e);
        if ($rethrowexception) {
            throw $e;
        }
        print_r($e->getMessage());
        print_r($e->getTrace());
        print_r($backup);
    }
}

if (($action == 'restorebackupfile') and (confirm_sesskey())) {

    try {
        \block_mbstpl\backup::restore_published($courseid, $template);
        redirect($thisurl, get_string('courserestored', 'block_mbstpl'));
    } catch (\moodle_exception $e) {
        \block_mbstpl\notifications::notify_error('errordeploying', $e);
        if ($rethrowexception) {
            throw $e;
        }
        print_r($e->getMessage());
        print_r($e->getTrace());
        print_r($backup);
    }
}

if (($action == 'deletebackupfile') and (confirm_sesskey())) {

    if ($fileid == 0) {
        print_error('unknownfile');
    }

    if ($confirm != 0) {
        $fs = get_file_storage();
        $file = $fs->get_file_by_id($fileid);
        $file->delete();
        redirect($thisurl, get_string('filedeleted', 'block_mbstpl'));
    } else {

        echo $OUTPUT->header();

        $filerecord = $DB->get_record('files', array('id' => $fileid), '*', MUST_EXIST);
        echo html_writer::tag('h2', get_string('deletebackupfile', 'block_mbstpl'));
        $url = new moodle_url($thisurl, array('confirm' => 1, 'action' => 'deletebackupfile', 'fileid' => $fileid));
        $message = get_string('deletebackupfiledesc', 'block_mbstpl', $filerecord->filename);
        echo $OUTPUT->confirm($message, $url, $thisurl);

        echo $OUTPUT->footer();
    }
}

// Get all available backup files.
$backupfiles = $template->get_backup_files($courseid);
$backupinfo = $backup->get_backup_info();

$PAGE->set_context($coursecontext);
$pagetitle = get_string('publishedbackup', 'block_mbstpl');
$PAGE->set_title($pagetitle);

echo $OUTPUT->header();

$renderer = mbst\course::get_renderer();


echo html_writer::tag('h2', $pagetitle);

echo $renderer->render_tpl_backup_info($backupinfo);
echo $renderer->render_tpl_backupfiles($course->id, $backupfiles, $backupinfo, $thisurl);

echo $OUTPUT->footer();
