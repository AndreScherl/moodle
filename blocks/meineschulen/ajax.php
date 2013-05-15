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
 * Handle AJAX requests for the Meine Schulen block
 *
 * @package   block_meineschulen
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__).'/../../config.php');

global $DB, $CFG, $PAGE, $OUTPUT;
require_once($CFG->dirroot.'/blocks/meineschulen/lib.php');

$action = required_param('action', PARAM_ALPHA);
$schoolid = optional_param('id', null, PARAM_INT);

$url = new moodle_url('/blocks/meineschulen/ajax.php');
$PAGE->set_url($url);
require_login();

$meineschulen = null;
if ($schoolid) {
    $schoolcat = $DB->get_record('course_categories', array('id' => $schoolid, 'depth' => MEINEKURSE_SCHOOL_CAT_DEPTH),
                                 '*', MUST_EXIST);
    $context = context_coursecat::instance($schoolcat->id);
    $meineschulen = new meineschulen($schoolcat);
} else {
    $context = context_system::instance();
}
$PAGE->set_context($context);

switch ($action) {
case 'search':
    if (is_null($meineschulen)) {
        print_error('missingschoolid', 'block_meineschulen');
    }
    $searchtext = required_param('search', PARAM_TEXT);
    $sortby = optional_param('sortby', 'name', PARAM_ALPHA);
    $sortdir = optional_param('sortdir', 'asc', PARAM_ALPHA);
    $resp = (object)array(
        'error' => 0,
        'results' => $meineschulen->output_course_search_results($searchtext, $sortby, $sortdir),
    );
    break;

case 'schoolsearch':
    $searchtext = required_param('search', PARAM_TEXT);
    $schooltype = required_param('schooltype', PARAM_INT);
    $sortby = optional_param('sortby', 'name', PARAM_ALPHA);
    $sortdir = optional_param('sortdir', 'asc', PARAM_ALPHA);
    $numberofresults = optional_param('numberofresults', 20, PARAM_INT);
    $page = optional_param('page', 0, PARAM_INT);
    $resp = (object)array(
        'error' => 0,
        'results' => meineschulen::output_school_search_results($searchtext, $schooltype, $sortby, $sortdir, $numberofresults, $page),
    );
    break;
default:
    print_error('unknownaction', 'block_meineschulen');
    die();
}

echo json_encode($resp);
