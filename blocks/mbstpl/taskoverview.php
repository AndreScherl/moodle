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
 * Overview page of ad hoc tasks create by this plugin
 *
 * @package   local_impact
 * @copyright 2016 Andreas Wagner, ISB BAyern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require(dirname(__FILE__) . '/../../config.php');

global $CFG, $PAGE, $OUTPUT;

require_once($CFG->dirroot . '/lib/tablelib.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('blockmbstpltasks');

$perpage = optional_param('perpage', 20, PARAM_INT);

$baseurl = new moodle_url('/blocks/mbstpl/taskoverview.php');
$filterform = new \block_mbstpl\form\tasksearchform($baseurl);

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

$PAGE->set_heading(get_string('pluginname', 'block_mbstpl'));
$PAGE->set_title(get_string('tasksoverview', 'block_mbstpl'));

$table = new flexible_table('block-mbstpl-taskoverview');

$download = optional_param('download', '', PARAM_ALPHA);
if ($download) {
    $table->is_downloading($download, 'taskslist');
}

$table->set_attribute('cellspacing', '0');
$table->set_attribute('cellpadding', '3');
$table->set_attribute('class', 'generaltable');

$columns = array('id', 'courseid1', 'authorid', 'status', 'userdataincluded', 'lastresettime', 'nextruntime', 'action');
$headers = array('', 'course', 'author', 'status', 'userdataincluded', 'lastresettime', 'nextruntime', '');

foreach ($headers as $i => $header) {
    if (!empty($header)) {
        $headers[$i] = get_string($header, 'block_mbstpl');
    } else {
        $headers[$i] = '';
    }
}

$table->headers = $headers;
$table->define_columns($columns);

$baseurl->params($tableurlparams);
$table->define_baseurl($baseurl);

$table->sortable(true, 'nextruntime', SORT_DESC);
$table->no_sorting('action');

$table->pageable(true);
$table->is_downloadable(true);

$table->defaultdownloadformat = 'excel';

$table->set_control_variables(
array(
    TABLE_VAR_SORT => 'tsort',
    TABLE_VAR_PAGE => 'page'));

$table->setup();

$templatedata = \block_mbstpl\local\tasksearch_helper::get_template_overview($filterdata, $table, $perpage, $download);

$renderer = $PAGE->get_renderer('block_mbstpl');

if (!$download) {

    echo $OUTPUT->header();

    $filterform->display();

    echo html_writer::start_tag('div', array('id' => 'block-mbstpl-table-wrapper'));
}

foreach ($templatedata as $tdate) {

    $id = $tdate->id;

    $templatecourse = get_string('notavailable', 'block_mbstpl');
    if ($tdate->courseid) {

        $fullname = (!empty($tdate->fullname)) ? $tdate->fullname : get_string('unknown', 'block_mbstpl');

        if (!$download) {
            $url = new moodle_url('/course/view.php', array('id' => $tdate->courseid));
            $templatecourse = html_writer::link($url, $fullname, array('target' => '_blank'));
        } else {
            $templatecourse = $fullname;
        }
    }

    $author = get_string('notavailable', 'block_mbstpl');

    if (!empty($tdate->uduserid)) {
        $author = $renderer->get_fullusername($tdate->udfirstname, $tdate->udlastname);
    } else {
        $author = $renderer->get_fullusername($tdate->firstname, $tdate->lastname);
    }

    $lastresettime = get_string('never');
    if (!empty($tdate->lastresettime)) {
        $lastresettime = userdate($tdate->lastresettime);
    }

    $nextruntime = get_string('notaskavailable', 'block_mbstpl');
    if (!empty($tdate->nextruntime)) {
        $nextruntime = userdate($tdate->nextruntime);
    }

    $action = '';
    if (!$download) {
        if (!empty($tdate->customdata)) {
            $customdata = json_decode($tdate->customdata);
            $url = new moodle_url('/enrol/mbs/edit.php', array('courseid' => $customdata->courseid, 'id' => $customdata->instanceid));
            $action .= html_writer::link($url, get_string('configreset', 'block_mbstpl'), array('target' => '_blank'));
        } else {
            if ($tdate->status == \block_mbstpl\dataobj\template::STATUS_PUBLISHED) {
                $url = new moodle_url('/enrol/instances.php', array('id' => $tdate->courseid));
                $action .= html_writer::link($url, get_string('addenrolmbs', 'block_mbstpl'), array('target' => '_blank'));
            }
        }
    }

    if (!$download) {
        $status = $renderer->status_box($tdate->status);
    } else {
        $status = \block_mbstpl\course::get_statusshortname($tdate->status);
    }

    $row = array($id, $templatecourse, $author, $status, $tdate->incluserdata, $lastresettime, $nextruntime, $action);
    $table->add_data($row);
}

if ($download) {

    $table->finish_output();
} else {

    $table->finish_html();
    echo html_writer::end_div();

    echo $OUTPUT->footer();
}