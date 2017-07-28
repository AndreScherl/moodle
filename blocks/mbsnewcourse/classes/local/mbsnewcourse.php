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
 * class for mbsnewcourse block
 *
 * @package    block_mbsnewcourse
 * @copyright  2014 Andreas Wagner, ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbsnewcourse\local;


class mbsnewcourse {

    /** if this user has role "lehrer" in LDAP assign a course-creator role in
     *  the context of his "Home School" (Heimatschule)
     *
     * @return boolean, true if role has been assigned and the user has the capability to create a course.
     */
    protected static function check_teacher_role_assign($categoryid) {
        global $USER;

        // Check if User is Teacher in LDAP.
        if (!isset($USER->isTeacher) or ($USER->isTeacher != 1)) {
            return false;
        }

        // Get the schoolcategory of the new courses category.
        if (!$targetschoolcategory = \local_mbs\local\schoolcategory::get_schoolcategory($categoryid)) {
            return false;
        }

        $config = get_config('block_mbsnewcourse');

        // Check institution and role.
        if (empty($USER->institution) or (empty($config->coursecreatorrole))) {
            return false;
        }

        // Is User in tree of his school?
        if ($targetschoolcategory->idnumber != $USER->institution) {
            return false;
        }

        // ...check whether role to assign is ok.
        $roles = get_roles_with_capability('moodle/course:create');
        if (!in_array($config->coursecreatorrole, array_keys($roles))) {
            return false;
        }

        //  User is LDAP-Teacher and in his "Heimatschule", so do the role-assignment.
        $context = \context_coursecat::instance($targetschoolcategory->id);
        role_assign($config->coursecreatorrole, $USER->id, $context);

        return true;
    }

    /**
     * Can the user see the 'create course' link, if not check role assignment.
     * 
     * @param int $categoryid the id of the category, where users cap to check.
     * @return boolean true if user have the capability.
     */

    public static function can_create_course($categoryid) {

        $context = \context_coursecat::instance($categoryid);

        if (has_capability('moodle/course:create', $context)) {
            return true;
        }

        return self::check_teacher_role_assign($categoryid);
    }
}