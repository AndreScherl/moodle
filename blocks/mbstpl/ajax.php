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
//define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');

use \block_mbstpl as mbst;

$action = required_param('action', PARAM_ALPHA);

require_login();

$url = new moodle_url('/blocks/mbstpl/ajax.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

switch ($action) {

    case 'loadmoreresults':

        $limitfrom = optional_param('limitfrom', 0, PARAM_INT);
        $limitnum = optional_param('limitnum', 0, PARAM_INT);
        
        // Load questions.
        $qidlist = \block_mbstpl\questman\manager::get_searchqs();
        $questions = \block_mbstpl\questman\manager::get_questsions_in_order($qidlist);

        $searchform = new mbst\form\searchform(null, array('questions' => $questions), 'post', '', array('id' => 'mbstpl-search-form'));
        
        $results = false;
        if ($data = $searchform->get_data()) {
           
            $search = new mbst\search($questions, $data);
            $results = $search->get_search_result($limitfrom, $limitnum);
            
            $renderer = mbst\course::get_renderer();
            $results->html = $renderer->render_moreresults_ajax($results);
        }

        $resp = array('error' => 0, 'results' => $results);

        break;

    default:

        print_error('unknownaction', 'block_mbssearch');
        die();
}

echo json_encode($resp);
