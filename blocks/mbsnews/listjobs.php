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
 * Versioninformation of mbsnews
 *
 * @package   block_mbsnews
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->dirroot . '/lib/tablelib.php');

$pageparams = array();
$pageurl = new moodle_url('/blocks/mbsnews/listjobs.php', $pageparams);
$PAGE->set_url($pageurl);

require_login();

$context = context_system::instance();
require_capability('block/mbsnews:sendnews', $context);

$PAGE->set_context($context);
$PAGE->set_heading(get_string('jobs', 'block_mbsnews'));
$PAGE->set_pagelayout('admin');

$perpage = optional_param('perpage', 50, PARAM_INT);

$table = new flexible_table('news-joblist');

$table->set_attribute('cellspacing', '0');
$table->set_attribute('cellpadding', '3');
$table->set_attribute('class', 'generaltable');

$columns = array('timecreated', 'contextlevel', 'roleid', 'subject', 'recipients', 'progress', 'action');
$headers = array();

foreach ($columns as $i => $column) {
    $headers[$i] = get_string($column, 'block_mbsnews');
}

$table->headers = $headers;
$table->define_columns($columns);

$tableurl = new moodle_url($pageurl, $pageparams);
$table->define_baseurl($tableurl);

$table->no_sorting('action');
$table->sortable(true, 'timecreated', SORT_DESC);

$table->pageable(true);
$table->is_downloadable(false);

$table->set_control_variables(
        array(
            TABLE_VAR_SORT => 'tsort',
            TABLE_VAR_PAGE => 'page'));

echo $OUTPUT->header();

$icon = $OUTPUT->pix_icon('t/add', get_string('addnotificationjob', 'block_mbsnews'));
$url = new moodle_url('/blocks/mbsnews/editjob.php');
echo html_writer::link($url, $icon.' '.get_string('addnotificationjob', 'block_mbsnews'));

$table->setup();

$jobs = \block_mbsnews\local\newshelper::get_jobs($pageparams, $table, $perpage);
$roles = $DB->get_records('role');

foreach ($jobs as $job) {

    $timecreated = userdate($job->timecreated);

    if ($job->contextlevel == 0) {
        $contextlevelname = get_string('all', 'block_mbsnews');
    } else {
        $contextlevelname = get_string(\block_mbsnews\local\newshelper::$contextlevelnames[$job->contextlevel], 'block_mbsnews');
    }

    if ($job->roleid == 0) {
        $rolename = get_string('all', 'block_mbsnews');
    } else {
        $rolename = (isset($roles[$job->roleid])) ? role_get_name($roles[$job->roleid]) : get_string('unknownrole', 'block_mbsnews');
    }

    $subject = shorten_text($job->subject);

    $countrecipients = $job->countrecipients;

    if ($countrecipients == 0) {
        $progress = 100;
    } else {
        $progress = min(100, round($job->countprocessed / $job->countrecipients * 100));
    }

    $actionlinks = '';
    
    $deleteicon = $OUTPUT->pix_icon('t/delete', get_string('delete'));
    $deleteurl = new moodle_url('/blocks/mbsnews/deletejob.php', array('id' => $job->id));
    $actionlinks .= html_writer::link($deleteurl, $deleteicon);
    
    if ($progress < 100) {
        $editicon = $OUTPUT->pix_icon('t/edit', get_string('edit'));
        $editurl = new moodle_url('/blocks/mbsnews/editjob.php', array('id' => $job->id));
        $actionlinks .= ' '.html_writer::link($editurl, $editicon);
    }
    
    $row = array($timecreated, $contextlevelname, $rolename, $subject, $countrecipients, $progress, $actionlinks);

    $table->add_data($row);
}

$table->finish_html();
echo $OUTPUT->footer();
