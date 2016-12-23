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
 * Report courses (orphaned)
 *
 * @package    report_mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagner@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../../config.php');

global $CFG, $PAGE, $OUTPUT;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/lib/tablelib.php');

admin_externalpage_setup('reportorphanedcourses', '', null, '', array('pagelayout' => 'admin'));

$baseurl = new moodle_url('/report/mbs/reportcourses/index.php');
$PAGE->set_url($baseurl);

$download = optional_param('download', '', PARAM_ALPHA);

$default = get_config('report_mbs', 'reportcourseperpage');
$baseurl->param('perpage', $default);

$perpage = optional_param('perpage', $default, PARAM_INT);

$filterform = new \report_mbs\form\reportcourses_form($baseurl, array(), 'post', '', array('id' => 'mbs-report-coursesform'));
$reportcourseshelper = new \report_mbs\local\reportcourses();

// Get data.
if ($filterdata = $filterform->get_data()) {

    // Convert for use in tableurl.
    $tableurlparams = $filterform->get_url_params($filterdata);
} else {
    // Try to catch data from url and convert to form default.
    $filterdata = $filterform->get_request_data();
    $tableurlparams = $filterform->get_url_params($filterdata);
}

$filterform->set_data($filterdata);
$baseurl->params($tableurlparams);

$PAGE->set_heading(get_string('reportcourses', 'report_mbs'));
$PAGE->set_title(get_string('reportcourses', 'report_mbs'));

$table = new flexible_table('reportcourses-table');

if ($download) {
    $table->is_downloading($download, userdate(time(), '%Y-%m-%d-%H%M%S') . '_reportcourses');
}

$table->set_attribute('cellspacing', '0');
$table->set_attribute('cellpadding', '3');
$table->set_attribute('class', 'generaltable');
$table->set_attribute('id', 'reportcourses-table');

$columns = array(
    'checkbox', 'id', 'coursename', 'lastviewed', 'timemodified', 'trainerscount',
    'participantscount', 'modulescount', 'categoryname', 'filessize'
);

$headers = array(
    '', 'id', 'coursename', 'lastviewed', 'timemodified', 'trainerscount',
    'participantscount', 'modulescount', 'categoryname', 'filessize'
);

foreach ($headers as $i => $header) {
    if (!empty($header)) {
        $headers[$i] = get_string($header, 'report_mbs');
    } else {
        $headers[$i] = '';
    }
}

$headers[0] = \html_writer::empty_tag('input', array('type' => 'checkbox', 'id' => 'coursecheckall'));

$table->headers = $headers;
$table->define_columns($columns);

$table->define_baseurl($baseurl);
$table->sortable(true, 'coursename', SORT_DESC);
$table->no_sorting('checkbox');

$table->pageable(true);
$table->is_downloadable(true);

$table->defaultdownloadformat = 'csv';

$table->set_control_variables(
    array(
        TABLE_VAR_SORT => 'tsort',
        TABLE_VAR_PAGE => 'page'));

$table->setup();

$courses = $reportcourseshelper->get_courses_stats($filterdata, $table, $perpage, $download);

if (!$download) {

    echo $OUTPUT->header();

    $filterform->display();

    echo $reportcourseshelper->render_cron_info();

    $numstr = get_string('numberofcourses', 'report_mbs', $table->totalrows);
    echo html_writer::tag('div',  $numstr, array('id' => 'local-impact-numinfo'));
    echo html_writer::start_tag('div', array('id' => 'local-impact-table-wrapper'));
}

foreach ($courses as $course) {

    $row = array();

    $row[] = \html_writer::empty_tag('input', array('type' => 'checkbox', 'value' => $course->id, 'id' => 'course_' . $course->id));

    $row[] = $course->id;

    // Course name.
    $coursename = $course->coursename . " [id: {$course->id}]";
    if (!$download) {
        $url = new moodle_url('/course/view.php', array('id' => $course->id));
        $coursename = html_writer::link($url, $course->coursename, array('target' => '_blank'));
    }
    $row[] = $coursename;

    $row[] = userdate($course->lastviewed, get_string('strftimedatetimeshort', 'langconfig'));
    $row[] = userdate($course->timemodified, get_string('strftimedatetimeshort', 'langconfig'));

    $row[] = $course->trainerscount;

    $row[] = $course->participantscount;
    $row[] = $course->modulescount;

    // Category.
    $category = $course->categoryname . " [id: {$course->categoryid}]";
    if (!$download) {
        $url = new moodle_url('/course/index.php', array('categoryid' => $course->categoryid));
        $category = html_writer::link($url, $course->categoryname, array('target' => '_blank'));
    }

    $row[] = $category;
    $row[] = number_format(ceil($course->filessize / 1048576)) . " MB";

    $table->add_data($row);
}

if ($download) {

    $table->finish_output();
} else {

    $table->finish_html();
    echo html_writer::end_div();

    $url = new moodle_url('/report/mbs/reportcourses/bulkaction.php');
    $bulkactionform = new \report_mbs\form\bulkaction_form($url, array(), 'post', '', array('id' => 'bulkaction-form'));
    $bulkactionform->display();

    $PAGE->requires->js_call_amd('report_mbs/reportcourses', 'init', array());

    echo $OUTPUT->footer();
}