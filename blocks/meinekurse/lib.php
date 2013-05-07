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
 * Library code used by the meinkurse block
 *
 * @package   block_meinkurse
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

define('MEINEKURSE_SCHOOL_CAT_DEPTH', 3);

function meinekurse_get_main_school($user) {
    global $DB;

    $schoolid = $user->institution;
    if (!$schoolid) {
        return false;
    }
    return $DB->get_record('course_categories', array('idnumber' => $schoolid, 'depth' => MEINEKURSE_SCHOOL_CAT_DEPTH), 'id, name');
}

function meinekurse_get_prefs() {
    $prefs = get_user_preferences('block_meinekurse_prefs', false);
    if ($prefs) {
        $prefs = unserialize($prefs);
    }
    if (!$prefs || !is_object($prefs)) {
        $prefs = (object) array(
            'sortby' => 'name',
            'numcourses' => 5,
            'school' => null,
            'sortdir' => 'asc',
        );
    }
    return $prefs;
}

function meinekurse_set_prefs($prefs) {
    set_user_preference('block_meinekurse_prefs', serialize($prefs));
}