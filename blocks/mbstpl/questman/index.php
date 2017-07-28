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

use block_mbstpl\questman\manager;

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $PAGE, $OUTPUT;

require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('blockmbstplmanageqforms');

$moveupid = optional_param('moveup', 0, PARAM_INT);
$movedownid = optional_param('movedown', 0, PARAM_INT);
$useqid = optional_param('useq', 0, PARAM_INT);

if ($moveupid) {
    manager::move_question($moveupid, true);
}
if ($movedownid) {
    manager::move_question($movedownid, false);
}
if ($useqid) {
    manager::add_question_to_draft($useqid);
}
$isdraft = manager::is_draft();
if ($isdraft) {
    $actform = new \block_mbstpl\form\activatedraft();
    if ($data = $actform->get_data()) {
        manager::activate_draft($data->formname);
        redirect($PAGE->url);
    }
}
echo $OUTPUT->header();

if (!$isdraft && $active = manager::get_active_qform()) {
    $pagetitle = $active->name;
} else {
    $pagetitle = get_string('qformunsaved', 'block_mbstpl');
}
echo html_writer::tag('h2', $pagetitle);

$options = manager::list_datatypes();
$popupurl = new moodle_url('/blocks/mbstpl/questman/quest.php');
$strcreaquestion = get_string('addquestion', 'block_mbstpl');
$renderer = $PAGE->get_renderer('block_mbstpl');
$questions = manager::get_draft_questions();
echo $renderer->list_questions($questions);
echo $OUTPUT->single_select($popupurl, 'datatype', $options, '', array('' => $strcreaquestion), 'newfieldform');
$bankurl = new moodle_url('/blocks/mbstpl/questman/qbank.php');
echo html_writer::div(html_writer::link($bankurl, get_string('addfrombank', 'block_mbstpl')));
if ($isdraft) {
    $actform->display();
}

echo $OUTPUT->footer();
