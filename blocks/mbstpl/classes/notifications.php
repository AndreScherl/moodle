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
 * Class notifications
 * For emailing etc.
 * @package block_mbstpl
 */
class notifications {

    /**
     * The user which emails are sent from.
     */
    public static function get_fromuser() {
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

    /** Notify the relevant users that the primary restore is successful.
     * @param object $backup
     * @param mixed $course object or id
     */
    public static function email_deployed($backup, $course) {
        global $DB;

        if (!is_object($course)) {
            $course = $DB->get_record('course', array('id' => $course));
            $course->url = (string)new \moodle_url('/course/view.php?id=' . $course->id);
        }

        // Email to managers.
        $managers = get_users_by_capability(\context_system::instance(), 'block/mbstpl:coursetemplatemanager');
        $subject = get_string('emailreadyforreview_subj', 'block_mbstpl');
        $body = get_string('emailreadyforreview_body', 'block_mbstpl', $course);
        foreach ($managers as $manager) {
            self::send_message('deployed', $manager, $subject, $body);
        }

        // Email to course author.
        $author = $DB->get_record('user', array('id' => $backup->creatorid), '*', MUST_EXIST);
        $subject = get_string('emailtempldeployed_subj', 'block_mbstpl');
        $body = get_string('emailtempldeployed_body', 'block_mbstpl', $course);
        self::send_message('deployed', $author, $subject, $body);
    }

    /** Notify the relevant users that the secondary restore is successful.
     * @param object $backup
     * @param mixed $course object or id
     */
    public static function email_duplicated($requesterid, $course) {
        global $DB;

        if (!is_object($course)) {
            $course = $DB->get_record('course', array('id' => $course));
            $course->url = (string)new \moodle_url('/course/view.php?id=' . $course->id);
        }

        // Email to managers.
        $managers = get_users_by_capability(\context_system::instance(), 'block/mbstpl:coursetemplatemanager');
        $subject = get_string('emailreadyforreview_subj', 'block_mbstpl');
        $body = get_string('emailreadyforreview_body', 'block_mbstpl', $course);
        foreach ($managers as $manager) {
            self::send_message('duplicated', $manager, $subject, $body);
        }

        // Email to course author.
        $requester = $DB->get_record('user', array('id' => $requesterid), '*', MUST_EXIST);
        $subject = get_string('emaildupldeployed_subj', 'block_mbstpl');
        $body = get_string('emaildupldeployed_body', 'block_mbstpl', $course);
        self::send_message('duplicated', $requester, $subject, $body);
    }

    /**
     * Tell the user that they have been assigned reviewer.
     * @param object $course must include id and fullname.
     * @param int $userid
     */
    public static function notify_assignedreviewer($course, $userid) {
        global $DB;

        $touser = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

        $url = new \moodle_url('/course/view.php', array('id' => $course->id));
        $a = (object)array('url' => $url->out(false), 'fullname' => $course->fullname);
        $subject = get_string('emailassignedreviewer_subj', 'block_mbstpl');
        $body = get_string('emailassignedreviewer_body', 'block_mbstpl', $a);
        self::send_message('assignedreviewer', $touser, $subject, $body);
    }

    /**
     * Tell the user that they have been assigned author.
     * @param object $course must include id and fullname.
     * @param int $userid
     */
    public static function notify_assignedauthor($course, $userid) {
        global $DB;

        $touser = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

        $url = new \moodle_url('/course/view.php', array('id' => $course->id));
        $a = (object)array('url' => $url->out(false), 'fullname' => $course->fullname);
        $subject = get_string('emailassignedauthor_subj', 'block_mbstpl');
        $body = get_string('emailassignedauthor_body', 'block_mbstpl', $a);
        self::send_message('assignedauthor', $touser, $subject, $body);
    }

    /**
     * Tell the author that the template has been published.
     * @param dataobj\template $template
     */
    public static function notify_published(dataobj\template $template) {
        global $DB;

        $coursename = $DB->get_field('course', 'fullname', array('id' => $template->courseid), MUST_EXIST);
        $touser = $DB->get_record('user', array('id' => $template->authorid));

        $a = (object)array(
            'url' => new \moodle_url('/course/view.php', array('id' => $template->courseid)),
            'coursename' => $coursename,
        );
        $subject = get_string('emailcoursepublished_subj', 'block_mbstpl');
        $body = get_string('emailcoursepublished_body', 'block_mbstpl', $a);

        self::send_message('published', $touser, $subject, $body);
    }

    /**
     * Send reviewer feedback to the author, or author to reviewer.
     * @param dataobj\template $template course template.
     */
    public static function send_feedback(dataobj\template $template) {
        global $DB, $USER;
        if (empty($template->authorid)) {
            return;
        }
        if (empty($template->reviewerid)) {
            return;
        }
        if (empty($template->feedback)) {
            return;
        }
        if ($USER->id == $template->reviewerid) {
            $isreviewer = true;
        } else if ($USER->id == $template->authorid) {
            $isreviewer = false;
        } else {
            throw new \moodle_exception('errornotallwoedtosendfeedback', 'block_mbstpl');
        }
        if ($isreviewer) {
            $toid = $template->authorid;
            $fromid = $template->reviewerid;
        } else {
            $toid = $template->reviewerid;
            $fromid = $template->authorid;
        }
        $touser = $DB->get_record('user', array('id' => $toid), '*', MUST_EXIST);

        $sender = $DB->get_record('user', array('id' => $fromid), '*', MUST_EXIST);
        $coursename = $DB->get_field('course', 'fullname', array('id' => $template->courseid), MUST_EXIST);
        $courseurl = new \moodle_url('/course/view.php', array('id' => $template->courseid));
        $a = (object)array(
            'reviewer' => fullname($sender),
            'fullname' => $coursename,
            'courseurl' => (string)$courseurl,
        );
        if ($isreviewer) {
            $subject = get_string('emailfeedbackrev_subj', 'block_mbstpl');
            $body = get_string('emailfeedbackrev_body', 'block_mbstpl', $a);
        } else {
            $subject = get_string('emailfeedbackauth_subj', 'block_mbstpl');
            $body = get_string('emailfeedbackauth_body', 'block_mbstpl', $a);
        }

        self::send_message('feedback', $touser, $subject, $body);
    }

    /**
     * Notify the local review about the template created for revision.
     * @param dataobj\template $template
     * @param int $reviewerid
     */
    public static function notify_forrevision(dataobj\template $template, $reviewerid) {
        global $DB;
        if (empty($template->reviewerid)) {
            return;
        }
        $toid = $reviewerid;
        $coursename = $DB->get_field('course', 'fullname', array('id' => $template->courseid), MUST_EXIST);
        $courseurl = new \moodle_url('/course/view.php', array('id' => $template->courseid));
        $a = (object)array(
            'fullname' => $coursename,
            'reason' => $template->feedback,
            'url' => (string)$courseurl,
        );
        $subject = get_string('emailrevision_subj', 'block_mbstpl');
        $body = get_string('emailrevision_body', 'block_mbstpl', $a);
        $touser = $DB->get_record('user', array('id' => $toid), '*', MUST_EXIST);
        self::send_message('forrevision', $touser, $subject, $body);
    }

    /**
     * Notify administrator upon an error.
     * @param string $identifier
     * @param \Exception $e
     */
    public static function notify_error($identifier, \Exception $e = null) {
        $to = get_admin();
        $message = empty($e) ? '' : $e->getMessage();
        $errorstr = get_string($identifier, 'block_mbstpl', $identifier);
        $subject = get_string('erroremailsubj', 'block_mbstpl');
        $a = (object)array('message' => $message, 'errorstr' => $errorstr);
        $body = get_string('erroremailbody', 'block_mbstpl', $a);
        self::send_message('error', $to, $subject, $body);
    }

    private static function send_message($messagetype, $touser, $subject, $body) {
        $message = new \stdClass();
        $message->component         = 'block_mbstpl';
        $message->name              = $messagetype;
        $message->userfrom          = self::get_fromuser();
        $message->userto            = $touser;
        $message->subject           = $subject;
        $message->fullmessage       = $body;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml   = '';
        $message->smallmessage      = '';
        $message->notification      = 1;
        message_send($message);
    }
}
