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

use \local_mbslicenseinfo\local\mbslicenseinfo as mbslicenseinfo;

$course = required_param('course', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', get_config('local_mbslicenseinfo', 'filesperpage'), PARAM_INT);
$searchdata = optional_param('searchdata', null, PARAM_ALPHANUM);

// Adding parameters to the action url, this will create hidden fields in edit form!
$pageparams = array('course' => $course, 'page' => $page, 'perpage' => $perpage);
$filterurl = new moodle_url('/local/mbslicenseinfo/editlicenses.php', $pageparams);

$pageparams['onlyincomplete'] = mbslicenseinfo::get_onlyincomplete_pref();

$coursecontext = context_course::instance($course);
$pageparams['onlymine'] = mbslicenseinfo::get_onlymine_pref($coursecontext);

$pageurl = new moodle_url('/local/mbslicenseinfo/editlicenses.php', $pageparams);

$PAGE->set_url($pageurl);
require_login($course, false);

// Checking capability.
if (!$captype = mbslicenseinfo::get_license_capability($coursecontext)) {
    print_error('errorcannotedit', 'local_mbslicenseinfo');
}

$pagetitle = get_string('editlicensesdescr', 'local_mbslicenseinfo');
$PAGE->set_title($pagetitle);
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($coursecontext);

$customdata = $pageparams;
$customdata['captype'] = $captype;

$filterform = new \local_mbslicenseinfo\form\editlicensesformfilter(
        $filterurl, $customdata, 'post', '', array('id' => 'filterform'));

$searchform = new \local_mbslicenseinfo\form\editlicensesformsearch($pageurl, $pageparams);

$mbslicenseinfo = new mbslicenseinfo();

$locked = ($captype == mbslicenseinfo::$captype_viewall);

if (!$searchdata) {
    // Show all course files with pagination.
    $files = $mbslicenseinfo->get_coursefiles_data($course, $page * $perpage, $perpage, $pageparams);
    $form = new \local_mbslicenseinfo\form\editlicensesform($pageurl, array('course' => $course, 'filesdata' => $files->data, 'locked' => $locked));
} else {
    $files = unserialize(base64_decode($searchdata));
    $form = new \local_mbslicenseinfo\form\editlicensesform($pageurl, array('course' => $course, 'filesdata' => $files, 'locked' => $locked));
}

if ($data = $searchform->get_data()) {
    $files = $mbslicenseinfo->search_coursefiles($course, $pageparams, $data->filesearch);
    $searchdata = base64_encode(serialize($files));
    $cdata = array('course' => $course, 'filesdata' => $files, 'locked' => $locked, 'searchdata' => $searchdata); 
    $url = new moodle_url($PAGE->url, $pageparams);
    $url->param('searchdata', $searchdata);
    $form = new \local_mbslicenseinfo\form\editlicensesform($url, $cdata);
} else {
    if ($form->is_cancelled()) {
        $redirecturl = new moodle_url('/course/view.php', array('id' => $course));
        redirect($redirecturl);
    } else if ($data = $form->get_data()) {
        if (!$locked) {
                mbslicenseinfo::update_course_files($data);
                $pageurl->param('message', 'licenseinfosaved');
                unset($searchdata);
                // Redirect getting new added licenses and avoid resubmit.
                redirect($pageurl);
        } else {
            // Should normally not happen.
            print_error('missing permission to save');
        }
    }
}

echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('editlicensesheader', 'local_mbslicenseinfo'));

$message = optional_param('message', '', PARAM_TEXT);
if (!empty($message)) {
    $message = get_string($message, 'local_mbslicenseinfo');
    echo $OUTPUT->notification($message, 'notifysuccess');
}

// Output filter form.
echo $filterform->display();
echo $searchform->display();

if ($searchdata) {
    echo html_writer::div($form->render(), 'editlicenses');
} else {
    echo $OUTPUT->paging_bar($files->total, $page, $perpage, $pageurl);
    echo html_writer::div($form->render(), 'editlicenses');
    echo $OUTPUT->paging_bar($files->total, $page, $perpage, $pageurl);
}
echo $OUTPUT->footer();