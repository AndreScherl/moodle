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
 * mediathek module main user interface
 *
 * @package    mod
 * @subpackage mediathek
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
global $CFG, $DB, $PAGE, $OUTPUT;
require_once("$CFG->dirroot/mod/mediathek/locallib.php");
require_once($CFG->libdir.'/completionlib.php');

$id = optional_param('id', 0, PARAM_INT); // Course module ID.
$u = optional_param('u', 0, PARAM_INT); // Mediathek instance id.
$redirect = optional_param('redirect', 0, PARAM_BOOL);

if ($u) { // Two ways to specify the module.
    $url = $DB->get_record('mediathek', array('id' => $u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('mediathek', $url->id, $url->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('mediathek', $id, 0, false, MUST_EXIST);
    $url = $DB->get_record('mediathek', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/mediathek:view', $context);

$params = array(
    'context' => $context,
    'objectid' => $url->id
);
$event = \mod_pmediathek\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('mediathek', $url);
$event->trigger();

// Update 'viewed' state if required by completion system
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/mediathek/view.php', array('id' => $cm->id));

// Make sure URL exists before generating output - some older sites may contain empty urls
// Do not use PARAM_URL here, it is too strict and does not support general URIs!
$exturl = trim($url->externalurl);
if (empty($exturl) or $exturl === 'http://') {
    mediathek_print_header($url, $cm, $course);
    mediathek_print_heading($url, $cm, $course);
    mediathek_print_intro($url, $cm, $course);
    notice(get_string('invalidstoredurl', 'mediathek'), new moodle_url('/course/view.php', array('id' => $cm->course)));
    die;
}
unset($exturl);

$displaytype = mediathek_get_final_display_type($url);
if ($displaytype == RESOURCELIB_DISPLAY_OPEN) {
    // For 'open' links, we always redirect to the content - except if the user
    // just chose 'save and display' from the form then that would be confusing.
    if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'modedit.php') === false) {
        $redirect = true;
    }
}

if ($redirect) {
    // Coming from course page or url index page,
    // the redirection is needed for completion tracking and logging.
    $fullurl = mediathek_get_full_url($url, $cm, $course);
    redirect(str_replace('&amp;', '&', $fullurl));
}

switch ($displaytype) {
    case RESOURCELIB_DISPLAY_EMBED:
        mediathek_display_embed($url, $cm, $course);
        break;
    case RESOURCELIB_DISPLAY_FRAME:
        mediathek_display_frame($url, $cm, $course);
        break;
    default:
        mediathek_print_workaround($url, $cm, $course);
        break;
}
