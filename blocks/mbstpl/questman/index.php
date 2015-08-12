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

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('manageqforms', 'block_mbstpl'));
$thisurl = new moodle_url('/blocks/mbstpl/questman/index.php');
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('admin');

require_login(SITEID, false);
if (!is_siteadmin()) {
    require_capability('moodle/site:config', $systemcontext);
}

$activate = optional_param('activate', false, PARAM_BOOL);
$moveupid = optional_param('moveup', 0, PARAM_INT);
$movedownid = optional_param('movedown', 0, PARAM_INT);
$useqid = optional_param('useq', 0, PARAM_INT);

if ($moveupid) {
    \block_mbstpl\questman\manager::move_question($moveupid, true);
}
if ($movedownid) {
    \block_mbstpl\questman\manager::move_question($movedownid, false);
}
if ($useqid) {
    \block_mbstpl\questman\manager::add_question_to_draft($useqid);
}
$isdraft = \block_mbstpl\questman\manager::is_draft();
if ($isdraft) {
    $actform = new \block_mbstpl\form\activatedraft();
    if ($data = $actform->get_data()) {
        \block_mbstpl\questman\manager::activate_draft($data->formname);
        redirect($thisurl);
    }
}
echo $OUTPUT->header();

$pagetitle = $isdraft ? get_string('qformunsaved', 'block_mbstpl') : get_string('qformactive', 'block_mbstpl');
echo html_writer::tag('h2', $pagetitle);

$options = \block_mbstpl\questman\manager::list_datatypes();
$popupurl = new moodle_url('/blocks/mbstpl/questman/quest.php');
$strcreaquestion = get_string('addquestion', 'block_mbstpl');
$renderer = $PAGE->get_renderer('block_mbstpl');
$questions = \block_mbstpl\questman\manager::get_draft_questions();
echo $renderer->list_questions($questions);
echo $OUTPUT->single_select($popupurl, 'datatype', $options, '', array('' => $strcreaquestion), 'newfieldform');
$bankurl = new moodle_url('/blocks/mbstpl/questman/qbank.php');
echo html_writer::div(html_writer::link($bankurl, get_string('addfrombank', 'block_mbstpl')));
if ($isdraft) {
    $actform->display();
}

echo $OUTPUT->footer();