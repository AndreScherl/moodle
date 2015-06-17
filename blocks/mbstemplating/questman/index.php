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
 * @package block
 * @subpackage mbstemplating
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $PAGE, $USER, $CFG, $DB, $OUTPUT;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('manageqforms', 'block_mbstemplating'));
$thisurl = new moodle_url('/blocks/mbstemplating/questman/index.php');
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
    \block_mbstemplating\questman\manager::move_question($moveupid, true);
}
if ($movedownid) {
    \block_mbstemplating\questman\manager::move_question($movedownid, false);
}
if ($useqid) {
    \block_mbstemplating\questman\manager::add_question_to_draft($useqid);
}
$isdraft = \block_mbstemplating\questman\manager::is_draft();
if ($isdraft) {
    $actform = new \block_mbstemplating\questman\activatedraftform();
    if ($data = $actform->get_data()) {
        \block_mbstemplating\questman\manager::activate_draft($data->formname);
        redirect($thisurl);
    }
}
echo $OUTPUT->header();

$pagetitle = $isdraft ? get_string('qformunsaved', 'block_mbstemplating') : get_string('qformactive', 'block_mbstemplating');
echo html_writer::tag('h2', $pagetitle);

$options = \block_mbstemplating\questman\manager::list_datatypes();
$popupurl = new moodle_url('/blocks/mbstemplating/questman/quest.php');
$strcreaquestion = get_string('addquestion', 'block_mbstemplating');
$renderer = $PAGE->get_renderer('block_mbstemplating');
$questions = \block_mbstemplating\questman\manager::get_draft_questions();
echo $renderer->list_questions($questions);
echo $OUTPUT->single_select($popupurl, 'datatype', $options, '', array('' => $strcreaquestion), 'newfieldform');
$bankurl = new moodle_url('/blocks/mbstemplating/questman/qbank.php');
echo html_writer::div(html_writer::link($bankurl, get_string('addfrombank', 'block_mbstemplating')));
if ($isdraft) {
    $actform->display();
}

echo $OUTPUT->footer();