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
 * Main code for local_fixsequence
 *
 * @package   local_fixsequence
 * @copyright 2013 Andreas Wagner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $PAGE, $OUTPUT;
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/local/fixsequence/fixsequence_form.php');
require_once($CFG->dirroot . '/local/fixsequence/lib.php');

admin_externalpage_setup('fixsequence');

$fixsequenceform = new fixsequence_form();
$resultstr = "";

echo $OUTPUT->header();

if ($data = $fixsequenceform->get_data()) {

    if ($data->options == 0) {
        // Search courses.
        if (!$result = local_fixsequence_searchorfixcourses()) {

            $resultstr = get_string('nocourses', 'local_fixsequence');
        } else {

            $count = count($result['problems']);

            if ($count == 0) {

                $resultstr = get_string('noproblems', 'local_fixsequence', count($result['courseok']));

            } else {
                $courseids = implode(", ", $result['problems']);
                $resultstr = get_string('problems', 'local_fixsequence', $courseids);

                $errors = $result['errors'];

                foreach ($errors as $courseid => $error) {
                    $resultstr .= "<p>ID: {$courseid}</p>";
                    $resultstr .= "<ul><li>".implode('</li><li>', $error)."</li></ul>";
                }
            }
        }
    }

    if ($data->options == 1) {
        // Search courses and fix courses.
        if (!$result = local_fixsequence_searchorfixcourses(array('*'), array('fix' => 1))) {

            $resultstr = get_string('nocourses', 'local_fixsequence');
        } else {

            $count = count($result['problems']);

            if ($count == 0) {

                $resultstr = get_string('noproblems', 'local_fixsequence', count($result['courseok']));
            } else {

                $courseids = implode(", ", $result['problems']);
                $resultstr = get_string('problemsfixed', 'local_fixsequence', $courseids);

                $errors = $result['errors'];

                foreach ($errors as $courseid => $error) {
                    $resultstr .= "<p>ID: {$courseid}</p>";
                    $resultstr .= "<ul><li>".implode('</li><li>', $error)."</li></ul>";
                }
            }
        }
    }
}

if ($resultstr) {
    echo $OUTPUT->notification($resultstr, 'notifymessage');
}

$fixsequenceform->display();

echo $OUTPUT->footer();