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
global $CFG, $DB, $PAGE, $OUTPUT;
require_once($CFG->libdir.'/resourcelib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('pmediathek', $id, 0, false, MUST_EXIST);
$pmediathek = $DB->get_record('pmediathek', array('id' => $cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

$PAGE->set_url('/mod/pmediathek/view.php', array('id' => $cm->id));

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pmediathek:view', $context);

$params = array(
    'context' => $context,
    'objectid' => $pmediathek->id
);
$event = \mod_pmediathek\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('pmediathek', $pmediathek);
$event->trigger();

$exturl = trim($pmediathek->externalurl);
$PAGE->set_title($course->shortname.': '.$pmediathek->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();
if (!empty($pmediathek->intro)) {
    echo format_module_intro('pmediathek', $pmediathek, $cm->id);
}

if ($pmediathek->display == RESOURCELIB_DISPLAY_EMBED) {
    echo html_writer::tag('iframe', '', array('class' => 'pmediathek_embed', 'src' => $exturl));
} else { // RESOURCELIB_DISPLAY_POPUP.

    $jsexturl = addslashes_js($exturl);
    $width  = 620;
    $height = 450;
    $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
    $extra = "onclick=\"window.open('$jsexturl', '', '$wh'); return false;\"";

    echo '<div class="urlworkaround">';
    print_string('clicktoopen', 'url', "<a href=\"$exturl\" $extra>$exturl</a>");
    echo '</div>';
}
echo $OUTPUT->footer();