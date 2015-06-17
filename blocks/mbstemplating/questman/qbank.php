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
$thisurl = new moodle_url('/blocks/mbstemplating/questman/qbank.php');
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('admin');

require_login(SITEID, false);
if (!is_siteadmin()) {
    require_capability('moodle/site:config', $systemcontext);
}

echo $OUTPUT->header();

$pagetitle = get_string('qbank', 'block_mbstemplating');
echo html_writer::tag('h2', $pagetitle);

$options = \block_mbstemplating\questman\manager::list_datatypes();
$popupurl = new moodle_url('/blocks/mbstemplating/questman/quest.php');
$strcreaquestion = get_string('addquestion', 'block_mbstemplating');
$renderer = $PAGE->get_renderer('block_mbstemplating');
$questions = \block_mbstemplating\questman\manager::get_bank_questions();
echo $renderer->list_bank_questions($questions);
$backurl = new moodle_url('/blocks/mbstemplating/questman/index.php');
echo $OUTPUT->single_button($backurl, get_string('back'), 'post');
echo $OUTPUT->footer();