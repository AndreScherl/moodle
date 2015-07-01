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
 * @package block
 * @subpackage mbstemplating
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstemplating;

defined('MOODLE_INTERNAL') || die();

/**
 * Class notifications
 * For emailing etc.
 * @package block_mbstemplating
 */
class notifications {

    /**
     * The user which emails are sent from.
     */
    private static function get_fromuser() {
        global $CFG;

        $user = get_admin();
        if (!empty($CFG->supportemail) && validate_email($CFG->supportemail)) {
            $user->email = $CFG->supportemail;
        }
        $site = get_site();
        $user->firstname = $site->fullname;
        $user->lastname = '';
        return $user;
    }

    /** Notify the relevant users that the restore is successful.
     * @param object $template
     * @param mixed $course object or id
     */
    public static function email_deployed($template, $course) {
        global $DB;

        if (!is_object($course)) {
            $course = $DB->get_record('course', array('id' => $course));
            $course->url = (string)new \moodle_url('/course/view.php?id=' . $course->id);
        }
        $from = self::get_fromuser();

        // Email to managers.
        $managers = get_users_by_capability(\context_system::instance(), 'block/mbstemplating:coursetemplatemanager');
        $subject = get_string('emailreadyforreview_subj', 'block_mbstemplating');
        $body = get_string('emailreadyforreview_body', 'block_mbstemplating', $course);
        foreach($managers as $manager) {
            email_to_user($manager, $from, $subject, $body);
        }

        // Email to course author.
        $author = $DB->get_record('user', array('id' => $template->creatorid), '*', MUST_EXIST);
        $subject = get_string('emailtempldeployed_subj', 'block_mbstemplating');
        $body = get_string('emailtempldeployed_body', 'block_mbstemplating', $course);
        email_to_user($author, $from, $subject, $body);
    }

    /**
     * Notify administrator upon an error.
     * @param string $identifier
     * @param \Exception $e
     */
    public static function notify_error($identifier, \Exception $e = null) {
        $from = self::get_fromuser();
        $to = get_admin();
        $message = empty($e) ? '' : $e->getMessage();
        $errorstr = get_string($identifier, 'block_mbstemplating', $identifier);
        $subject = get_string('erroremailsubj', 'block_mbstemplating');
        $a = (object)array('message' => $message, 'errorstr' => $errorstr);
        $body = get_string('erroremailbody', 'block_mbstemplating', $a);
        email_to_user($to, $from, $subject, $body);
    }
}