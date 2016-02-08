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
 * Adds new instance of enrol_mbs to specified course
 * or edits current instance.
 *
 * @package    enrol_mbs
 * @copyright  2015 Janek Lasocki-Biczysko, Synergy Learning for ALP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_mbs;

defined('MOODLE_INTERNAL') || die();

class reset_course_userdata {

    /**
     * Resets the user data for a course that was created from a template
     *
     * @param int $courseid
     */
    public static function reset_course_from_template($courseid) {
        global $DB, $CFG;

        $template = \block_mbstpl\dataobj\template::get_from_course($courseid);
        if (!$template) {
            throw new \moodle_exception('errorunabletoresetnontemplate', 'enrol_mbs');
        }
        if ($template->status != $template::STATUS_PUBLISHED) {
            return;
        }
        
        $course = get_course($courseid);

        $data = array(
            'id' => $courseid,
            'reset_events' => true,
            'reset_notes' => true,
            'delete_blog_associations' => true,
            'reset_completion' => true,
            'reset_roles_overrides' => true,
            'reset_roles_local' => true,
            'reset_groups_members' => true,
            'reset_groups_remove' => true,
            'reset_groupings_members' => true,
            'reset_groupings_remove' => true,
            'reset_gradebook_items' => true,
            'reset_gradebook_grades' => true,
            'reset_comments' => true
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
}
