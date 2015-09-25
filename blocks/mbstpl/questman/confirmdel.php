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
$PAGE->set_url(new moodle_url('/blocks/mbstpl/questman/confirmdel.php'));

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', false, PARAM_BOOL);

require_login(SITEID, false);

if (!is_siteadmin()) {
    require_capability('moodle/site:config', $systemcontext);
}

$question = $DB->get_record('block_mbstpl_question', array('id' => $id), '*', MUST_EXIST);

$redirurl = new moodle_url('/blocks/mbstpl/questman/index.php');
if ($confirm && confirm_sesskey()) {
    \block_mbstpl\questman\manager::delete_question($question);
    redirect($redirurl);
}
$actionurl = new moodle_url('/blocks/mbstpl/questman/confirmdel.php', array('id' => $id, 'sesskey' => sesskey(), 'confirm' => 1));
echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox', 'notice');
$promptstr = $question->inuse ?
    get_string('confirmdelquest', 'block_mbstpl') :
    get_string('confirmdelquestforever', 'block_mbstpl');
echo html_writer::tag('p', $promptstr);
echo $OUTPUT->single_button($actionurl, get_string('delete'), 'post');
echo html_writer::empty_tag('br');
echo $OUTPUT->single_button($redirurl, get_string('cancel'), 'post');
echo $OUTPUT->box_end();
echo $OUTPUT->footer();


