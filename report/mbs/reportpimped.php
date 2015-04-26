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
 * report pimped courses (style and js customisations using html - block)
 *
 * @package    report
 * @subpackage mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagern@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/report/mbs/reportpimped_form.php');
require_once($CFG->dirroot . '/lib/tablelib.php');

// Check access.
require_login();

// Check capability.
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$searchpattern = optional_param('searchpattern', '', PARAM_RAW);

$baseparams = array(
    'searchpattern' => $searchpattern,
);
$baseurl = new moodle_url('/report/mbs/reportpimped.php', $baseparams);

$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_heading(get_string('reportpimped', 'report_mbs'));
$PAGE->set_title(get_string('reportpimped', 'report_mbs'));

$opts = array();
$PAGE->requires->yui_module('moodle-report_mbs-toggleinfo', 'M.report_mbs.toggleinit', array($opts));

$reportpimpedform = new reportpimped_form($baseurl, $baseparams);

$download = optional_param('download', false, PARAM_ALPHA);

$table = new flexible_table('report-mbs-pimped');

if ($download) {
    $table->is_downloading($download);
}

$table->set_attribute('cellspacing', '0');
$table->set_attribute('cellpadding', '3');
$table->set_attribute('class', 'generaltable');

$columns = array('courseid', 'coursename', 'trainer', 'school', 'coordinators');
$headers = array('courseid', 'coursename', 'trainer', 'school', 'coordinators');

foreach ($headers as $i => $header) {
    $headers[$i] = get_string($header, 'report_mbs');
}

$table->headers = $headers;
$table->define_columns($columns);
$table->define_baseurl($baseurl);
$table->sortable(false);

$table->is_downloadable(true);
$table->defaultdownloadformat = 'excel';

$table->setup();

$statsdata = \report_mbs\local\reportpimped::get_reports_data($baseparams);

if (!$download) {

    echo $OUTPUT->header();

    echo $OUTPUT->heading(get_string('reportpimped', 'report_mbs'));
    $reportpimpedform->display();

    echo html_writer::start_tag('div', array('class' => 'reportpimped-tablewrapper'));
}

foreach ($statsdata as $data) {

    $row = array($data->id);

    $course = $data->coursename;
    if (!$download) {

        $url = new moodle_url('/course/view.php', array('id' => $data->id));
        $course = html_writer::link($url, " " . $course, array('target' => '_blank'));

        if (isset($data->blockscontent)) {
            $contents = implode('<br /><br />', $data->blockscontent);
            $icon = $OUTPUT->pix_icon('i/info', get_string('viewhtml', 'report_mbs'),
                    'moodle', array('class' => 'smallicon', 'id' => 'info_' . $data->id));
            $course .= html_writer::link('#', $icon);
            $course .= html_writer::tag('div', $contents, array('style' => 'display:none', 'id' => 'content_' . $data->id));
        }
    }
    $row[] = $course;

    $trainers = '';
    if (isset($data->trainers)) {

        foreach ($data->trainers as $trainer) {

            $email = (!empty($trainer->email)) ? $trainer->email : '';

            if (!$download) {
                $url = new moodle_url('/user/profile.php', array('id' => $trainer->userid));
                $link = html_writer::link($url, fullname($trainer), array('target' => '_blank'));

                $trainers .= html_writer::tag('li', $link . " [{$email}] ");
            } else {
                $trainers .= fullname($trainer) . " [{$email}] ";
            }
        }

        if (!$download) {
            $trainers = html_writer::tag('ul', $trainers);
        }
    }
    $row[] = $trainers;

    $school = $data->school->name;
    if (!$download) {
        if (isset($data->school)) {
            $url = new moodle_url('/course/index.php', array('categoryid' => $data->school->id));
            $school = html_writer::link($url, $school, array('target' => '_blank'));
        }
    } else {
        $school .= " [ID: {$data->school->id}] ";
    }
    $row[] = $school;

    $coordinators = '';
    if (isset($data->coordinators)) {

        $coordinators = '';
        foreach ($data->coordinators as $coordinator) {

            $email = (!empty($coordinator->email)) ? $coordinator->email : '';

            if (!$download) {
                $url = new moodle_url('/user/profile.php', array('id' => $coordinator->userid));
                $link = html_writer::link($url, fullname($coordinator), array('target' => '_blank'));
                $coordinators .= html_writer::tag('li', $link . " [$email] ");
            } else {
                $coordinators .= fullname($coordinator) . " [{$email}] ";
            }
        }

        if (!$download) {
            $coordinators = html_writer::tag('ul', $coordinators);
        }
    }
    $row[] = $coordinators;

    $table->add_data($row);
}

if ($download) {

    $table->finish_output();
} else {

    $table->finish_html();
    echo html_writer::end_tag('div');
    echo $OUTPUT->footer();
}