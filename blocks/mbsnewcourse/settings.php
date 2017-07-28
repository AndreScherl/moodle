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
 * Versioninformation of block_mbsnewcourse
 *
 * @package   block_mbsnewcourse
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    global $DB;

    $choices = array();

    $roles = get_roles_with_capability('moodle/course:create');
    $assignroles = get_roles_with_capability('moodle/role:assign');
    $managerroles = get_roles_with_capability('moodle/category:manage');

    // Securitycheck only lower roles should be configurable.
    $roles = array_diff_key($roles, $assignroles, $managerroles);

    $choices[0] = get_string('assignnorole', 'block_mbsnewcourse');

    foreach ($roles as $role) {
        $choices[$role->id] = role_get_name($role);
    }

    $settings->add(new admin_setting_configselect('block_mbsnewcourse/coursecreatorrole',
                    new lang_string('coursecreatorrole', 'block_mbsnewcourse'),
                    new lang_string('coursecreatorrole_expl', 'block_mbsnewcourse'), 0, $choices));
}
