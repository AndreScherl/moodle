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
 * @package     local_mbslicenseinfo
 * @copyright   2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

$course = required_param('course', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', get_config('local_mbslicenseinfo', 'filesperpage'), PARAM_INT);
$coursecontext = context_course::instance($course);

$pageparams = array('course' => $course, 'perpage' => $perpage);
$thisurl = new moodle_url('/local/mbslicenseinfo/editlicenses.php', $pageparams);

$PAGE->set_url($thisurl);

// Adding breadcrumb navigation.
require_login($course, false);
require_capability('local/mbslicenseinfo:editlicenses', $coursecontext, null, true, 'errorcannotedit', 'local_mbslicenseinfo');

$pagetitle = get_string('editlicensesdescr', 'local_mbslicenseinfo');
$PAGE->set_title($pagetitle);
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($coursecontext);

// Get and save show mode.
$onlyincomplete = optional_param('onlyincomplete', -1, PARAM_INT);
if ($onlyincomplete == -1) {
    $onlyincomplete = get_user_preferences('mbslicenseshowincomplete', 0);
} else {
    $userincomplete = get_user_preferences('mbslicenseshowincomplete', 0);
    if ($onlyincomplete <> $userincomplete) {
        set_user_preference('mbslicenseshowincomplete', $onlyincomplete);
    }
}

$mbslicenseinfo = new \local_mbslicenseinfo\local\mbslicenseinfo();
$files = $mbslicenseinfo->get_coursefiles_data($course, $page * $perpage, $perpage, $onlyincomplete);

$form = new \local_mbslicenseinfo\form\editlicensesform(null, array('course' => $course, 'filesdata' => $files->data));

$message = '';
if ($form->is_cancelled()) {
    $redirecturl = new moodle_url('/course/view.php', array('id' => $course));
    redirect($redirecturl);
} else if ($data = $form->get_data()) {
    \local_mbslicenseinfo\local\mbslicenseinfo::update_course_files($data);
    $message = get_string('licenseinfosaved', 'local_mbslicenseinfo');
}

echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('editlicensesheader', 'local_mbslicenseinfo'));
$link = html_writer::link(get_string('editlicenses_notelink', 'local_mbslicenseinfo'), get_string('editlicenses_note', 'local_mbslicenseinfo'), array('class' => 'internal'));
echo html_writer::tag('p', $link);

if (!empty($message)) {
   echo $OUTPUT->notification($message, 'notifysuccess'); 
}

$url = new moodle_url('/local/mbslicenseinfo/editlicenses.php', array('course' => $course, 'onlyincomplete' => !$onlyincomplete));
$text = (empty($onlyincomplete)) ? get_string('showonlyincomplete', 'local_mbslicenseinfo') : get_string('showall', 'local_mbslicenseinfo');
echo $OUTPUT->single_button($url, $text);

echo $OUTPUT->paging_bar($files->total, $page, $perpage, $thisurl);
echo html_writer::div($form->render(), 'editlicenses');
echo $OUTPUT->paging_bar($files->total, $page, $perpage, $thisurl);

echo $OUTPUT->footer();