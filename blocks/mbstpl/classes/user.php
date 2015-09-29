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
 * @package block_mbstpl
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl;

defined('MOODLE_INTERNAL') || die();

/**
 * Class user
 * For user-related operations.
 * @package block_mbstpl
 */


class user {

    /**
     * Enrol a user with the reviewer role and notify them.
     * @param $courseid
     * @param $userid
     */
    public static function enrol_reviewer($courseid, $userid) {
        global $DB;

        if (!$roleid = get_config('block_mbstpl', 'reviewerrole')) {
            throw new \moodle_exception('errorreviewerrolenotset', 'block_mbstpl');
        }

        $course = $DB->get_record('course', array('id' => $courseid), 'id,fullname', MUST_EXIST);

        self::enrol_user_to_course($userid, $courseid, $roleid);

        // Now let them know about it.
        notifications::notify_assignedreviewer($course, $userid);
    }

    /**
     * Enrol a user with the author role and notify them.
     * @param $courseid
     * @param $userid
     */
    public static function enrol_author($courseid, $userid) {
        global $DB;

        if (!$roleid = get_config('block_mbstpl', 'authorrole')) {
            throw new \moodle_exception('errorauthorrolenotset', 'block_mbstpl');
        }

        $course = $DB->get_record('course', array('id' => $courseid), 'id,fullname', MUST_EXIST);

        self::enrol_user_to_course($userid, $courseid, $roleid);

        // Now let them know about it.
        notifications::notify_assignedauthor($course, $userid);
    }

    /**
     * Enrol a user with the teacher role
     *
     * @param $courseid
     * @param $userid
     */
    public static function enrol_teacher($courseid, $userid) {
        global $DB;

        if (!$roleid = get_config('block_mbstpl', 'teacherrole')) {
            throw new \moodle_exception('errorteacherrolenotset', 'block_mbstpl');
        }

        self::enrol_user_to_course($userid, $courseid, $roleid);
    }

    private static function enrol_user_to_course($userid, $courseid, $roleid) {
        global $CFG, $DB;

        $enrol = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual', 'status' => ENROL_INSTANCE_ENABLED));
        if (!$enrol) {
            throw new \moodle_exception('errormanualenrolnotset', 'block_mbstpl');
        }

        require_once($CFG->dirroot.'/enrol/manual/lib.php');
        $plugin = new \enrol_manual_plugin();
        $plugin->enrol_user($enrol, $userid, $roleid);
    }

    /**
     * Get all templates of the user (for any role).
     * @param null $userid use if not current user.
     * @return mixed array of template arrays or false if none found.
     */
    public static function get_templates($userid = null) {
        global $DB, $USER;

        if (empty($userid)) {
            $userid = $USER->id;
        }

        $toreturn = array(
            'assigned' => array(),
            'revision' => array(),
            'review' => array(),
            'published' => array(),
        );

        // Load all possibly relevant templates.
        $sql = "
        SELECT tpl.id, tpl.courseid, tpl.authorid, tpl.reviewerid, tpl.status, c.fullname AS coursename, tpl.timemodified
        FROM {block_mbstpl_template} tpl
        JOIN {course} c ON c.id = tpl.courseid
        WHERE tpl.authorid = :authid OR tpl.reviewerid = :revid
        ";
        $params = array('authid' => $USER->id, 'revid' => $USER->id);

        $templates = $DB->get_records_sql($sql, $params);
        foreach ($templates as $template) {
            $template->assigneeid = $template->reviewerid == $USER->id ? $template->authorid : $template->reviewerid;
            $status = $template->status;
            if ($template->reviewerid == $userid && $status == dataobj\template::STATUS_UNDER_REVIEW) {
                $template->type = 'assigned';
            } else if ($template->authorid == $userid && $status == dataobj\template::STATUS_UNDER_REVISION) {
                $template->type = 'assigned';
            } else if ($template->status == dataobj\template::STATUS_UNDER_REVISION) {
                $template->type = 'revision';
            } else if ($template->status == dataobj\template::STATUS_UNDER_REVIEW) {
                $template->type = 'review';
            } else if ($template->status == dataobj\template::STATUS_PUBLISHED) {
                $template->type = 'published';
            }

            $tplobj = new dataobj\template((array)$template, false);
            $coursecontext = \context_course::instance($template->courseid);
            $template->viewfeedback = perms::can_viewfeedback($tplobj, $coursecontext);
        }
        $presordeds = array();

        // Pre-sort only the ones that have a type.
        foreach ($templates as $template) {
            if (empty($template->type)) {
                continue;
            }
            $presordeds[] = $template;
        }
        if (empty($presordeds)) {
            return false;
        }

        // Load assignees.
        $assigneeids = array();
        foreach ($presordeds as $template) {
            $assigneeids[$template->assigneeid] = $template->assigneeid;
        }
        list($uidin, $params) = $DB->get_in_or_equal($assigneeids);
        $assignees = $DB->get_records_select('user', "id $uidin", $params);
        foreach ($presordeds as $template) {
            $template->assignee = empty($assignees[$template->assigneeid]) ? null : $assignees[$template->assigneeid];
        }

        // Sort by type.
        foreach ($presordeds as $template) {
            $toreturn[$template->type][] = $template;
        }
        return $toreturn;
    }

    public static function format_creator_name($user) {
        return s(fullname($user).' '.$user->email);
    }
}
