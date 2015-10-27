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
 * report texed tables
 *
 * @package    report
 * @subpackage mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagern@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/report/mbs/reporttex_form.php');
require_once($CFG->dirroot . '/lib/tablelib.php');

// Check access.
require_login();

// Check capability.
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$baseurl = new moodle_url('/report/mbs/reporttex.php');

$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_heading(get_string('reporttex', 'report_mbs'));
$PAGE->set_title(get_string('reporttex', 'report_mbs'));

$reporttexform = new reporttex_form($baseurl);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('reporttex', 'report_mbs'));

$reporttexform->display();

echo html_writer::start_tag('div', array('class' => 'reporttex-tablewrapper'));


if ($reporttexform->is_submitted()) {

    $table = new flexible_table('report-mbs-pimped');

    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('cellpadding', '3');
    $table->set_attribute('class', 'generaltable');

    $columns = array('check', 'tablename', 'count');
    $headers = array('check', 'tablename', 'count');

    foreach ($headers as $i => $header) {
        $headers[$i] = get_string($header, 'report_mbs');
    }

    $table->headers = $headers;
    $table->define_columns($columns);
    $table->define_baseurl($baseurl);
    $table->sortable(false);

    $table->is_downloadable(false);
    $table->defaultdownloadformat = 'excel';

    $table->setup();

    echo html_writer::start_tag('form', array('method' => 'post', 'action' => 'replacetex.php'));

    $data = \report_mbs\local\reporttex::get_reports_data();

    foreach ($data as $tablename => $counts) {

        $checkbox = html_writer::checkbox("table[$tablename]", 1, false);

        $countstr = '';
        if (!empty($counts)) {

            $colstr = array();
            foreach ($counts as $key => $count) {
                $colstr[] = $key . " (" . $count . ")";
            }
            $countstr = implode(", ", $colstr);
        }

        $row = array($checkbox, $tablename, $countstr);

        $table->add_data($row);
    }

    $table->finish_html();

    echo html_writer::empty_tag('input', array('value' => get_string('replacetex', 'report_mbs'), 'type' => 'submit'));
    echo html_writer::end_tag('form');
}

echo html_writer::end_tag('div');
echo $OUTPUT->footer();
