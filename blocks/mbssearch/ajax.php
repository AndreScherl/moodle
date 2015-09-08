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
 * search class for block mbssearch
 *
 * @package   block_search
 * @copyright 2015 Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');

$action = required_param('action', PARAM_ALPHA);

require_login();

$url = new moodle_url('/blocks/mbssearch/ajax.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

switch ($action) {

    case 'blockschoolsearch':

        $searchtext = required_param('searchtext', PARAM_TEXT);
        $schoolcatid = optional_param('schoolcatid', 0, PARAM_INT);

        $config = get_config('block_mbssearch');

        $resp = array('error' => 0, 'results' => \block_mbssearch\local\mbssearch::lookup_search($searchtext, $config->lookupcount, $schoolcatid));

        break;

    case 'loadmoreresults':

        $config = get_config('block_mbssearch');

        $searchtext = optional_param('searchtext', '', PARAM_TEXT);
        $limitfrom = optional_param('limitfrom', 0, PARAM_INT);
        $limitnum = optional_param('limitnum', $config->moreresultscount, PARAM_INT);
        $schoolcatid = optional_param('schoolcatid', 0, PARAM_INT);
        $filterby = optional_param('filterby', '', PARAM_TEXT); 
 
        $results = \block_mbssearch\local\mbssearch::search($searchtext, $limitfrom, $limitnum, $filterby, $schoolcatid);

        $renderer = $PAGE->get_renderer('block_mbssearch');
        $results->html = $renderer->render_more_results_ajax($results, $searchtext, $filterby);

        $resp = array('error' => 0, 'results' => $results);

        break;

    default:

        print_error('unknownaction', 'block_mbssearch');
        die();
}

echo json_encode($resp);