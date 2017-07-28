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
 * Reset courses using different methods depending on user data.
 * This is used by enrol_mbs to reset the course by restore done by a scheduled
 * adhoc task. (see \enrol_mbs\reset_course_userdata).
 *
 * @package    block_mbstpl
 * @copyright  2016 Andreas Wagner ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl;

defined('MOODLE_INTERNAL') || die();

class reset_course_userdata {

    private static function reset_course_userdata($course) {
        global $DB, $CFG;

        $data = array(
            'id' => $course->id,
            'delete_blog_associations' => true,
            'reset_comments' => true,
            'reset_completion' => true,
            'reset_data' => true,
            'reset_events' => true,
            'reset_game_all' => true,
            'reset_glossary_all' => true,
            'reset_gradebook_grades' => true,
            'reset_groups_members' => true,
            'reset_groups_remove' => true,
            'reset_groupings_members' => true,
            'reset_groupings_remove' => true,
            'reset_notes' => true,
            'reset_roles_overrides' => true,
            'reset_roles_local' => true,
            'reset_wiki_comments' => true,
            'reset_wiki_pages' => true,
            'reset_wiki_tags' => true
        );

        // Get roles for the unenrol user list
        $roles = explode(',', get_config('enrol_mbs', 'unenrol_role'));
        $roles = array_map('trim', $roles);
        $roleids = $DB->get_records_list('role', 'shortname', $roles, '', 'id');
        if (!empty($roleids)) {
            $data['unenrol_users'] = array_keys($roleids);
        }

        if ($allmods = $DB->get_records('modules')) {
            foreach ($allmods as $mod) {
                $modname = $mod->name;
                $modfile = $CFG->dirroot . "/mod/$modname/lib.php";
                $mod_reset_course_form_defaults = $modname . '_reset_course_form_defaults';
                if (file_exists($modfile)) {
                    @include_once($modfile);
                    if (function_exists($mod_reset_course_form_defaults)) {
                        if ($moddefs = $mod_reset_course_form_defaults($course)) {
                            $data = $data + $moddefs;
                        }
                    }
                }
            }
        }

        $data = (object) $data;

        reset_course_userdata($data);
    }

    /**
     * Check whether there where auto enrolled users in the course.
     * If not do no reset.

     * @return boolean true, if there are new users.
     */
    private static function has_auto_enrolled_users($courseid) {
        global $DB;

        $sql = "SELECT count(*)
                FROM {user_enrolments} ue
                JOIN {enrol} e on e.id = ue.enrolid
                AND e.courseid = :courseid
                AND e.enrol = :enrol ";

        $params = array(
            'courseid' => $courseid,
            'enrol' => 'mbstplaenrl'
        );

        $countenrolments = $DB->count_records_sql($sql, $params);

        if ($countenrolments == 0) {
            return false;
        }
        return true;
    }

    /**
     * Restore the template by using a public backup file.
     *
     * @param int $course
     * @param object $template
     * @throws \moodle_exception thrown, when no file  exists for restore.
     */
    private static function restore_course_template($course, $template) {

         // Unenrol everybody.
        $userenrolments = \block_mbstpl\course::get_all_enrolled_users($course->id);
        if (!empty($userenrolments)) {
            \block_mbstpl\course::unenrol($course->id, $userenrolments);
        }

        // If there is no pubbk_ file send email to admins.
        try {
            \block_mbstpl\backup::restore_published($course->id, $template);
        } catch (\moodle_exception $e) {
            \block_mbstpl\notifications::notify_error('errordeploying', $e);
            throw $e;
        }
    }

    /**
     * Resets the user data for a course that was created from a template
     *
     * @param int $courseid
     */
    public static function reset_course_from_template($courseid) {

        $template = \block_mbstpl\dataobj\template::get_from_course($courseid);
        if (!$template) {
            throw new \moodle_exception('errorunabletoresetnontemplate', 'enrol_mbs');
        }
        if ($template->status != $template::STATUS_PUBLISHED) {
            return;
        }

        // When there was no enrolment with tutor author enrol since last reset, do no new reset.
        $hasautoenrolled = self::has_auto_enrolled_users($courseid);
        if (!$hasautoenrolled) {
            return;
        }

        // Get the backup to given template to select restore strategy.
        $backup = new \block_mbstpl\dataobj\backup(array('id' => $template->backupid), true, MUST_EXIST);
        $course = get_course($courseid);

        if ($backup->incluserdata == 0) {
            // If there is no userdata in the course, do a standard course reset.
            self::reset_course_userdata($course);
        } else {
            // Try to detect, whether there are changes made, during the last reset.
            $modsunchecked = array();

            if (\block_mbstpl\course::has_course_content_changed($course, $template->lastresettime, $modsunchecked)) {
                self::restore_course_template($course, $template);
            }
        }

        // Log last template reset.
        $template->store_last_reset_time(time());
    }

}
