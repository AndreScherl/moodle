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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package block_mbstpl
 * @copyright 2015 Bence Laky <b.laky@intrallect.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');

global $PAGE, $USER, $CFG, $DB, $OUTPUT;

use \block_mbstpl;
use \block_mbstpl\dataobj\template;

// Page preparation.
$thisurl = new moodle_url('/blocks/mbstpl/templatesearch.php');
$layout = optional_param('layout', 'grid', PARAM_ALPHA);
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');

require_login();

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

$pagesize = 15;
$pagenumber = optional_param('page', 1, PARAM_INT);
$startrecord = ($pagenumber - 1) * $pagesize;

$searchform = new \block_mbstpl\form\searchform();
$searchcriteria = array();
if ($searchform->get_data()) {
    $formdata = get_object_vars($searchform->get_data());
    foreach (array_keys($formdata) as $settingkey) {
        if (preg_match("/^q[0-9]/", $settingkey)) {
            $questionid = intval(substr($settingkey, 1));
            $value = required_param($settingkey, PARAM_ALPHANUM);
            if ($value && strlen($value) > 0) {
                $searchcriteria[] = "(questionid = {$questionid} AND data = {$value})";
            }
        }
    }
}

if (count($searchcriteria) > 0) {
    $query = 'SELECT C.* FROM {block_mbstpl_answer} AS A';
    $query .= ' JOIN {block_mbstpl_meta} as M ON M.id = A.metaid ';
    $query .= ' JOIN {block_mbstpl_template} as T on M.templateid = T.id';
    $query .= ' JOIN {course} as C on T.courseid = C.id';
    $query .= ' WHERE T.status = ? AND (' . join(' OR ', $searchcriteria) . ')';
    $query .= ' GROUP BY metaid HAVING count(metaid) = ?';
    $courses = $DB->get_records_sql($query,
            array(template::STATUS_PUBLISHED, count($searchcriteria)
            ), $startrecord, $pagesize);
} else {
    $query = 'SELECT C.* FROM {course} as C';
    $query .= ' JOIN {block_mbstpl_template} as T ON C.id = T.courseid';
    $query .= ' WHERE T.status = ?';
    $courses = $DB->get_records_sql($query, array(template::STATUS_PUBLISHED
    ), $startrecord, $pagesize);
}

$PAGE->requires->yui_module('moodle-block_mbstpl-templatesearch',
        'M.block_mbstpl.templatesearch.init', array(), null, true);
$pagetitle = get_string('templatesearch', 'block_mbstpl');
$PAGE->set_title($pagetitle);
echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('block_mbstpl');
echo html_writer::tag('h3', $pagetitle);

echo $renderer->templatesearch($searchform, $courses, $layout);

echo $OUTPUT->footer();