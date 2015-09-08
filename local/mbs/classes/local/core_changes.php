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
 * To store core changes linked to this pluign.
 *
 * @package   local_mbs
 * @copyright 2014 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mbs\local;

use context_system;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

class core_changes {
    
    public static $teacherroleshortname = 'editingteacher';

        /**
     * Called from \course\index.php function definition() 
     */
    public static function check_view_courses() {
        global $PAGE;

        $context = context_system::instance();
        if (!has_capability('local/mbs:viewcourselist', $context)) {
            redirect(new moodle_url('/')); // Redirect to front page.
        }

        // Don't call course/index.php without any categorid, this will cause
        // a massive performance issue!
        // Note that $PAGE->category is a magic method (magic_get_category()) call!
        if (!$PAGE->category) {
            redirect(new moodle_url('/')); // Redirect to front page.
        }

        // Don't call course/index.php with below the schoolcategory depth, this will cause
        // a massive performance issue!
        if ($PAGE->category->depth < \local_mbs\local\schoolcategory::$schoolcatdepth) {
            redirect(new moodle_url('/')); // Redirect to front page.
        }
    }

    /**
     * Called from \course\edit_form.php function definition() 
     */
    public static function add_shortname_check() {
        global $PAGE;
        $PAGE->requires->yui_module('moodle-local_mbs-shortname', 'M.local_mbs.shortname.init');
    }

    /**
     * Assign-Teacher-Hack:
     * 
     * set the user preference mbs_allow_teacherrole to 1 for users 
     * with mebisRole "lehrer".
     * 
     * Called, when user logs in.
     * 
     * @return boolean true, if user is Mebis-Lehrer
     */
    public static function set_allow_teacherrole_preference() {
        global $USER;

        if (!isloggedin() or isguestuser()) {
            return false;
        }

        if (isset($USER->mebisRole)) {

            $ismebislehrer = in_array("lehrer", $USER->mebisRole);

            set_user_preference('mbs_allow_teacherrole', (int) $ismebislehrer);
            return $ismebislehrer;
        }

        return false;
    }

    /**
     * Assign-Teacher-Hack:
     * 
     * Get all the userids, which this user is allowed to assign as a teacher
     * 
     * @param int[] $userids
     * @return array()
     */
    private static function get_allowteacher_userids($userids) {
        global $DB;

        // Allow teacher role assignments for all users, when this user is admin.
        if (has_capability('moodle/site:config', context_system::instance())) {

            return $userids;
        }

        // ...otherwise, check whether user preference is set.
        list($inuserid, $inparams) = $DB->get_in_or_equal($userids);

        $sql = "SELECT userid
                FROM {user_preferences}
                WHERE name = 'mbs_allow_teacherrole' AND value = '1'
                AND userid {$inuserid}";

        if (!$userids = $DB->get_records_sql($sql, $inparams)) {
            return array();
        }

        return array_keys($userids);
    }

    /**
     * Assign-Teacher-Hack:
     * 
     * Called from \enrol\renderer.php function initialise_javascript() 
     * Add teacher flags for given users.
     * 
     * Note that additional hacks are made in rolemanager.js!
     * 
     * @param array $arguments containing the userids in $arguments['userIds']
     * 
     */
    public static function add_allowteacher_role(&$arguments) {
        global $DB;

        $allowteacherrole = array();

        if (!empty($arguments['userIds'])) {
            $allowteacherrole = self::get_allowteacher_userids($arguments['userIds']);
        }

        // Now retrieve the additional teacher role.
        $sql = "SELECT r.id, r.name, r.shortname, rn.name AS coursealias 
              FROM {role} r
              JOIN {role_context_levels} rcl ON (rcl.contextlevel = :contextlevel AND r.id = rcl.roleid)
         LEFT JOIN {role_names} rn ON (rn.contextid = :coursecontext AND rn.roleid = r.id)
              WHERE r.id = :teacherroleid
          ORDER BY r.sortorder ASC";

        $params = array();
        $params['contextlevel'] = CONTEXT_COURSE;
        $params['teacherroleid'] = self::get_roleid_by_shortname(self::$teacherroleshortname);

        $coursecontext = \context_course::instance($arguments['courseId']);
        $params['coursecontext'] = $coursecontext->id;

        $roles = $DB->get_records_sql($sql, $params);

        $rolenames = role_fix_names($roles, $coursecontext, ROLENAME_ALIAS, true);

        if (isset($rolenames[self::get_roleid_by_shortname(self::$teacherroleshortname)])) {
            $arguments['teacherrole'] = $rolenames;
        }

        $arguments['allowteacherrole'] = $allowteacherrole;
        $arguments['teacherroleid'] = self::get_roleid_by_shortname(self::$teacherroleshortname);
        return true;
    }

    /**
     * Assign-Teacher-Hack:
     * 
     * Check, whether it is allowed to assign given role for the user.
     * This is necessary to additionally allow the assignment of teacher role in
     * course context, when users preference 'mbs_allow_teacherrole' has value of 1.
     * 
     * Called from enrol/locallib.php course_enrolment_manager->assign_role_to_user($userid, $roleid)
     * and course_enrolment_manager->unassign_role_from_user($userid, $roleid)
     * 
     * @param int $roleid
     * @param int $userid
     * @return boolean true, if assignment is allowed
     */
    public static function role_assign_allowed($roleid, $userid) {

        if ($roleid != self::get_roleid_by_shortname(self::$teacherroleshortname)) {
            return false;
        }

        $allowroleassign = get_user_preferences('mbs_allow_teacherrole', 0, $userid);

        return ($allowroleassign == 1);
    }

    /**
     * Assign-Teacher-Hack:
     * 
     * Add all assignable roles. This is used to decide, whether a unassign button will
     * be displayed in enrol/users.php
     * 
     * Called form enrol/users.php
     * 
     * @param course_enrolment_manager $manager
     * @param array $users informations for users appearing in the table
     */
    public static function add_assignableroles($manager, &$users) {

        $allowteacherrole = self::get_allowteacher_userids(array_keys($users));

        foreach ($users as $id => $unused) {
            $users[$id]['assignableroles'] = $manager->get_assignable_roles();
            if (in_array($id, $allowteacherrole)) {
                $users[$id]['assignableroles'][self::get_roleid_by_shortname(self::$teacherroleshortname)] = 1;

                if (isset($users[$id]['roles'][self::get_roleid_by_shortname(self::$teacherroleshortname)])) {
                    $users[$id]['roles'][self::get_roleid_by_shortname('editingteacher')]['unchangeable'] = false;
                }
            }
        }
    }
    
    /**
     * Assign-Teacher-Hack:
     * 
     * Get the role id by shortname
     * 
     * @param string $shortname
     * @return int role id
     */
    public static function get_roleid_by_shortname($shortname) {
        global $DB;
        return $DB->get_field('role', 'id', array('shortname' => $shortname));
    }
}
