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
 * @package     block_mbslicenseinfo
 * @copyright   2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

$course = required_param('course', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', get_config('block_mbslicenseinfo', 'filesperpage'), PARAM_INT);
$coursecontext = context_course::instance($course);
$redirecturl = new moodle_url('/course/view.php', array('id' => $course));

$thisurl = new moodle_url('/blocks/mbslicenseinfo/editlicenses.php', array('course' => $course));
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('incourse');
$PAGE->set_context($coursecontext);

if (!has_capability('block/mbslicenseinfo:editlicenses', $coursecontext)) {
    throw new moodle_exception('errorcannotedit', 'block_mbslicenseinfo');
}

//Adding breadcrumb navigation. 
require_login($course, false);

//Extending the navigation for the course. 
$coursenode = $PAGE->navigation->find($course, navigation_node::TYPE_COURSE);
$pagenode = $coursenode->add(get_string('editlicenses', 'block_mbslicenseinfo'));
$pagenode->make_active();

$pagetitle = get_string('editlicensesdescr', 'block_mbslicenseinfo');
$PAGE->set_title($pagetitle);

$form = new \block_mbslicenseinfo\form\editlicensesform(null, array('course' => $course, 'page' => $page, 'perpage' => $perpage));
if ($form->is_cancelled()) {
    redirect($redirecturl);
} else if ($data = $form->get_data()) {
    \block_mbslicenseinfo\local\mbslicenseinfo::update_course_files($data);
    redirect($thisurl);
}

$totalcount = \block_mbslicenseinfo\local\mbslicenseinfo::get_number_of_course_files($course);
if (get_config('block_mbslicenseinfo', 'filesperpage') != $perpage) {
    $thisurl->param('perpage', $perpage);
}

echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('editlicensesheader', 'block_mbslicenseinfo'));
$link = html_writer::link(get_string('editlicenses_notelink', 'block_mbslicenseinfo'), get_string('editlicenses_note', 'block_mbslicenseinfo'), array('class' => 'internal'));
echo html_writer::tag('p', $link);
echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $thisurl);
echo html_writer::div($form->render(), 'editlicenses');
echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $thisurl); 

echo $OUTPUT->footer();







