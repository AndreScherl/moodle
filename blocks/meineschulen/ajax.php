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
$schoolid = required_param('id', PARAM_INT);
$schoolcat = $DB->get_record('course_categories', array('id' => $schoolid, 'depth' => MEINEKURSE_SCHOOL_CAT_DEPTH),
                             '*', MUST_EXIST);

$url = new moodle_url('/blocks/meineschulen/ajax.php', array('id' => $schoolcat->id));
$PAGE->set_url($url);
require_login();

$context = context_coursecat::instance($schoolcat->id);
$PAGE->set_context($context);

$meineschulen = new meineschulen($schoolcat);

switch ($action) {
case 'search':
    $searchtext = required_param('search', PARAM_TEXT);
    $resp = (object)array(
        'error' => 0,
        'results' => $meineschulen->output_course_search_results($searchtext),
    );
    break;
default:
    print_error('unknownaction', 'block_meineschulen');
    die();
}

echo json_encode($resp);
