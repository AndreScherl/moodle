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
 * search page for block mbssearch
 *
 * @package   block_search
 * @copyright 2015 Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');

$url = new moodle_url('/blocks/mbssearch/search.php');
$PAGE->set_url($url);

require_login();

$context = context_system::instance();
$PAGE->set_context($context);

$strtitle = get_string('search');
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add($strtitle, $PAGE->url);

$renderer = $PAGE->get_renderer('block_mbssearch');
$config = get_config('block_mbssearch');

$searchtext = optional_param('searchtext', '', PARAM_TEXT);
$limitfrom = optional_param('limitfrom', 0, PARAM_INT);
$limitnum = optional_param('limitnum', $config->moreresultscount, PARAM_INT);
$schoolcatid = optional_param('search_schoolcatid', 0, PARAM_INT);
if ($schoolcatid) {
    $filterby = 'course';
} else {
    $filterby = optional_param('filterby', 'nofilter', PARAM_TEXT);
}

$results = \block_mbssearch\local\mbssearch::search($searchtext, $limitfrom, $limitnum, $filterby, $schoolcatid);

echo $OUTPUT->header();
echo $renderer->render_search_page($results, $searchtext, $filterby);
echo $OUTPUT->footer();
