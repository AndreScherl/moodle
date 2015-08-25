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
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $PAGE, $USER, $CFG, $DB, $OUTPUT;

use \block_mbstpl AS mbst;

$thisurl = new moodle_url('/blocks/mbstpl/templatesearch.php');
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');

require_login();
$systemcontext = context_system::instance();

//TODO: should search somewhere here
$pagesize = 15;
$pagenumber = optional_param('page', 1, PARAM_INT);
$layout = optional_param('layout', 'grid', PARAM_ALPHA);
// Restrict to published templates.

foreach(array_keys($_POST) as $settingkey) {
    if (preg_match("/^q[0-9]/", $settingkey)) {
       $quuestionid = substr($settingkey, 1);
       $value = required_param($settingkey, PARAM_RAW);
    }
}
$templates = $DB->get_records('block_mbstpl_template', array('status' => 3), null, '*', $pagenumber - 1, $pagesize);

$PAGE->set_context($systemcontext);
$pagetitle = 'Template Search'; // TODO: Externalise
$PAGE->set_title($pagetitle);
echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('block_mbstpl');
echo html_writer::tag('h2', $pagetitle);

echo $renderer->templatesearch($templates, $layout);

echo $OUTPUT->footer();