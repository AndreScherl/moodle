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
 * Main entry point for Prüfungsarchiv activity
 *
 * @package   mod_pmediathek
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require_once(dirname(__FILE__).'/../../config.php');
global $DB, $PAGE, $OUTPUT;

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('pmediathek', $id, 0, false, MUST_EXIST);
$pmediathek = $DB->get_record('pmediathek', array('id' => $cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

$PAGE->set_url('/mod/pmediathek/view.php', array('id' => $cm->id));

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pmediathek:view', $context);

add_to_log($course->id, 'pmediathek', 'view', 'view.php?id='.$cm->id, $pmediathek->id, $cm->id);

$exturl = trim($pmediathek->externalurl);
$PAGE->set_title($course->shortname.': '.$pmediathek->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();
if (!empty($pmediathek->intro)) {
    echo format_module_intro('pmediathek', $pmediathek, $cm->id);
}
echo html_writer::tag('iframe', '', array('class' => 'pmediathek_embed', 'src' => $exturl));
echo $OUTPUT->footer();