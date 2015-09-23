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


require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $PAGE, $USER, $CFG, $DB, $OUTPUT;

use \block_mbstpl\questman\manager;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('manageqforms', 'block_mbstpl'));
$thisurl = new moodle_url('/blocks/mbstpl/questman/managesearch.php');
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('admin');

require_login(SITEID, false);
if (!is_siteadmin()) {
    require_capability('moodle/site:config', $systemcontext);
}

$activateid = optional_param('activateid', 0, PARAM_INT);
$deactivateid = optional_param('deactivateid', 0, PARAM_INT);
$moveupid = optional_param('moveup', 0, PARAM_INT);
$movedownid = optional_param('movedown', 0, PARAM_INT);

if ($activateid) {
    require_sesskey();
}
if ($deactivateid) {
    require_sesskey();
}
if ($moveupid) {
    require_sesskey();
}
if ($movedownid) {
    require_sesskey();
}

$questions = manager::searchmanage_getall();

echo $OUTPUT->header();

$pagetitle = get_string('managesearch', 'block_mbstpl');

echo html_writer::tag('h2', $pagetitle);

echo $OUTPUT->footer();