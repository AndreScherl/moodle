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
 * @package block_mbstpl
 * @copyright 2015 Bence Laky <b.laky@intrallect.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $PAGE, $OUTPUT;

use \block_mbstpl as mbst;

// Page preparation.

$layout = optional_param('layout', 'grid', PARAM_ALPHA);
$limitfrom = optional_param('limitfrom', 0, PARAM_INT);
$limitnum = optional_param('limitnum', get_config('block_mbstpl', 'searchpagesize'), PARAM_INT);

$pageparams = array('layout' => $layout, 'limitfrom' => $limitfrom, 'limitnum' => $limitnum);
$pageurl = new moodle_url('/blocks/mbstpl/templatesearch.php', $pageparams);

$PAGE->set_url($pageurl, $pageparams);

require_login();

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('course');

if (!mbst\perms::can_searchtemplates()) {
    throw new moodle_exception('errorcannotsearch', 'block_mbstpl');
}

// Load questions.
$qidlist = \block_mbstpl\questman\manager::get_searchqs();
$questions = \block_mbstpl\questman\manager::get_questsions_in_order($qidlist);

$searchform = new mbst\form\searchform($pageurl, array('questions' => $questions), 'post', '', array('id' => 'mbstpl-search-form'));

$result = false;
if ($data = $searchform->get_data()) {
    $search = new mbst\search($questions, $data);
    $result = $search->get_search_result($limitfrom, $limitnum);
}

$pagetitle = get_string('templatesearch', 'block_mbstpl');
$PAGE->set_title($pagetitle);
echo $OUTPUT->header();

$renderer = mbst\course::get_renderer();
echo html_writer::tag('h3', $pagetitle);

echo $renderer->templatesearch($searchform, $result, $layout);

echo $OUTPUT->footer();
