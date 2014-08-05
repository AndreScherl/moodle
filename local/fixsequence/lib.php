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
defined('MOODLE_INTERNAL') || die;

/**
 * Checks the integrity of the course data.
 *
 * In summary - compares course_sections.sequence and course_modules.section.
 *
 * More detailed, checks that:
 * - course_sections.sequence contains each module id not more than once in the course
 * - for each moduleid from course_sections.sequence the field course_modules.section
 *   refers to the same section id (this means course_sections.sequence is more
 *   important if they are different)
 * - ($fullcheck only) each module in the course is present in one of
 *   course_sections.sequence
 * - ($fullcheck only) removes non-existing course modules from section sequences
 *
 * If there are any mismatches, the changes are made and records are updated in DB.
 *
 * Course cache is NOT rebuilt if there are any errors!
 *
 * This function is used each time when course cache is being rebuilt with $fullcheck = false
 * and in CLI script admin/cli/fix_course_sequence.php with $fullcheck = true
 *
 * @param int $courseid id of the course
 * @param array $rawmods result of funciton {@link get_course_mods()} - containst
 *     the list of enabled course modules in the course. Retrieved from DB if not specified.
 *     Argument ignored in cashe of $fullcheck, the list is retrieved form DB anyway.
 * @param array $sections records from course_sections table for this course.
 *     Retrieved from DB if not specified
 * @param bool $fullcheck Will add orphaned modules to their sections and remove non-existing
 *     course modules from sequences. Only to be used in site maintenance mode when we are
 *     sure that another user is not in the middle of the process of moving/removing a module.
 * @param bool $checkonly Only performs the check without updating DB, outputs all errors as debug messages.
 * @return array array of messages with found problems. Empty output means everything is ok
 */
function local_fixsequence_course_integrity_check($courseid, $rawmods = null, $sections = null, $fullcheck = false, $checkonly = false) {
    global $DB;
    $messages = array();
    if ($sections === null) {
        $sections = $DB->get_records('course_sections', array('course' => $courseid), 'section', 'id,section,sequence');
    }
    if ($fullcheck) {
        // Retrieve all records from course_modules regardless of module type visibility.
        $rawmods = $DB->get_records('course_modules', array('course' => $courseid), 'id', 'id,section');
    }
    if ($rawmods === null) {
        $rawmods = get_course_mods($courseid);
    }
    if (!$fullcheck && (empty($sections) || empty($rawmods))) {
        // If either of the arrays is empty, no modules are displayed anyway.
        return true;
    }
    $debuggingprefix = 'Failed integrity check for course [' . $courseid . ']. ';

    // First make sure that each module id appears in section sequences only once.
    // If it appears in several section sequences the last section wins.
    // If it appears twice in one section sequence, the first occurence wins.
    $modsection = array();
    foreach ($sections as $sectionid => $section) {
        $sections[$sectionid]->newsequence = $section->sequence;
        if (!empty($section->sequence)) {
            $sequence = explode(",", $section->sequence);
            $sequenceunique = array_unique($sequence);
            if (count($sequenceunique) != count($sequence)) {
                // Some course module id appears in this section sequence more than once.
                ksort($sequenceunique); // Preserve initial order of modules.
                $sequence = array_values($sequenceunique);
                $sections[$sectionid]->newsequence = join(',', $sequence);
                $messages[] = $debuggingprefix . 'Sequence for course section [' .
                        $sectionid . '] is "' . $sections[$sectionid]->sequence . '", must be "' . $sections[$sectionid]->newsequence . '"';
            }
            foreach ($sequence as $cmid) {
                if (array_key_exists($cmid, $modsection) && isset($rawmods[$cmid])) {
                    // Some course module id appears to be in more than one section's sequences.
                    $wrongsectionid = $modsection[$cmid];
                    $sections[$wrongsectionid]->newsequence = trim(preg_replace("/,$cmid,/", ',', ',' . $sections[$wrongsectionid]->newsequence . ','), ',');
                    $messages[] = $debuggingprefix . 'Course module [' . $cmid . '] must be removed from sequence of section [' .
                            $wrongsectionid . '] because it is also present in sequence of section [' . $sectionid . ']';
                }
                $modsection[$cmid] = $sectionid;
            }
        }
    }

    // Add orphaned modules to their sections if they exist or to section 0 otherwise.
    if ($fullcheck) {
        foreach ($rawmods as $cmid => $mod) {
            if (!isset($modsection[$cmid])) {
                // This is a module that is not mentioned in course_section.sequence at all.
                // Add it to the section $mod->section or to the last available section.
                if ($mod->section && isset($sections[$mod->section])) {
                    $modsection[$cmid] = $mod->section;
                } else {
                    $firstsection = reset($sections);
                    $modsection[$cmid] = $firstsection->id;
                }
                $sections[$modsection[$cmid]]->newsequence = trim($sections[$modsection[$cmid]]->newsequence . ',' . $cmid, ',');
                $messages[] = $debuggingprefix . 'Course module [' . $cmid . '] is missing from sequence of section [' .
                        $modsection[$cmid] . ']';
            }
        }
        foreach ($modsection as $cmid => $sectionid) {
            if (!isset($rawmods[$cmid])) {
                // Section $sectionid refers to module id that does not exist.
                $sections[$sectionid]->newsequence = trim(preg_replace("/,$cmid,/", ',', ',' . $sections[$sectionid]->newsequence . ','), ',');
                $messages[] = $debuggingprefix . 'Course module [' . $cmid .
                        '] does not exist but is present in the sequence of section [' . $sectionid . ']';
            }
        }
    }

    // Update changed sections.
    if (!$checkonly && !empty($messages)) {
        foreach ($sections as $sectionid => $section) {
            if ($section->newsequence !== $section->sequence) {
                $DB->update_record('course_sections', array('id' => $sectionid, 'sequence' => $section->newsequence));
            }
        }
    }

    // Now make sure that all modules point to the correct sections.
    foreach ($rawmods as $cmid => $mod) {
        if (isset($modsection[$cmid]) && $modsection[$cmid] != $mod->section) {
            if (!$checkonly) {
                $DB->update_record('course_modules', array('id' => $cmid, 'section' => $modsection[$cmid]));
            }
            $messages[] = $debuggingprefix . 'Course module [' . $cmid .
                    '] points to section [' . $mod->section . '] instead of [' . $modsection[$cmid] . ']';
        }
    }

    return $messages;
}

function local_fixsequence_searchorfixcourses($courseslist = array('*'), $options = array()) {
    global $DB;

    $courseslist = array('*');
    
    if (in_array('*', $courseslist)) {
        $where = '';
        $params = array();
    } else {
        list($sql, $params) = $DB->get_in_or_equal($courseslist, SQL_PARAMS_NAMED, 'id');
        $where = 'WHERE id ' . $sql;
    }
    $coursescount = $DB->get_field_sql('SELECT count(id) FROM {course} ' . $where, $params);

    if (!$coursescount) {
        return false;
    }
    
    $problems = array();
    $courses = $DB->get_fieldset_sql('SELECT id FROM {course} ' . $where, $params);
    $courseerrors = array();
    $courseok = array();
    foreach ($courses as $courseid) {
        $errors = local_fixsequence_course_integrity_check($courseid, null, null, true, empty($options['fix']));
        if ($errors) {

            if (!empty($options['fix'])) {
                // Reset the course cache to make sure cache is recalculated next time the course is viewed.
                rebuild_course_cache($courseid, true);
            }
            $problems[] = $courseid;
            $courseerrors[$courseid] = $errors;

        } else {
            $courseok[] = $courseid;
        }
    }
    return array('problems' => $problems, 'errors' => $courseerrors, 'courseok' => $courseok);
}
