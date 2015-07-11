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
 * @package block_mbstemplating
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstemplating;

defined('MOODLE_INTERNAL') || die();

/**
 * Class user
 * For user-related operations.
 * @package block_mbstemplating
 */


class user {

    /**
     * Enrol a user with the reviewer role and notify them.
     * @param $courseid
     * @param $userid
     */
    public static function enrol_reviewer($courseid, $userid) {
        global $CFG, $DB;

        // First, enrol.
        require_once($CFG->dirroot . '/enrol/manual/lib.php');

        if (!$roleid = get_config('block_mbstemplating', 'reviewerrole')) {
            throw new \moodle_exception('errorreviewerrolenotset', 'block_mbstemplating');
        }

        $course = $DB->get_record('course', array('id' => $courseid), 'id,fullname', MUST_EXIST);
        $enrol = $DB->get_record('enrol', array('courseid'=>$courseid, 'enrol'=>'manual', 'status'=>ENROL_INSTANCE_ENABLED));
        if (!$enrol) {
            throw new \moodle_exception('errormanualenrolnotset', 'block_mbstemplating');
        }

        $plugin = new \enrol_manual_plugin();
        $plugin->enrol_user($enrol, $userid, $roleid);

        // Now let them know about it.
        notifications::notify_assignedreviewer($course, $userid);
    }
}