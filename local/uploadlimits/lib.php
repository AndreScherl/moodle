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
 * Library functions for the local upload limits plugin
 *
 * @package   local_uploadlimits
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

class local_uploadlimits {

    public static function get_limit() {
        global $SESSION, $DB, $USER, $CFG;

        if (!isset($SESSION->local_uploadlimits)) {
            $SESSION->local_uploadlimits = $CFG->userquota;

            // Get a list of roles that have the higher upload limit.
            $roles = get_roles_with_capability('local/uploadlimits:higherlimit', CAP_ALLOW);
            if ($roles) {
                // Check if the current user is assigned one of those roles anywhere on the site.
                list($rsql, $params) = $DB->get_in_or_equal(array_keys($roles), SQL_PARAMS_NAMED);
                $params['userid'] = $USER->id;
                $select = "userid = :userid AND roleid $rsql";
                $hashigherlimit = $DB->record_exists_select('role_assignments', $select, $params);
                if ($hashigherlimit) {
                    $SESSION->local_uploadlimits = 1073741824; // 1 GB
                }
            }
        }

        return $SESSION->local_uploadlimits;
    }
}