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
        $user = \core_user::get_user(\core_user::NOREPLY_USER);
        $user->firstname = 'mebis teachSHARE';
        $user->lastname = 'Systemnachricht';
        
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
        $managers = self::get_managers();
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
            $course->url = (string)new \moodle_url('/course/view.php', array('id' => $course->id));
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
            'url' => (string) new \moodle_url('/course/view.php', array('id' => $template->courseid)),
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
            'url' => (string)$courseurl,
            'feedback' => $template->feedback
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

    /**
     * Send a statistics notification to all
     * @param string $pathname the path to the stats csv file
     */
    public static function notify_stats($pathname) {

        $file = self::get_stored_file($pathname, 'stats');

        $managers = self::get_managers();
        $subject = get_string('emailstatsrep_subj', 'block_mbstpl');
        $messagetext = get_string('emailstatsrep_body', 'block_mbstpl');
        foreach ($managers as $user) {
            self::send_message('stats', $user, $subject, $messagetext, $file);
        }
    }

    public static function notify_reminder($templates) {

        $lines = array();
        $url = new \moodle_url('/course/view.php');
        foreach ($templates as $template) {
            $url->param('id', $template->cid);
            $lines[] = $url . ' ' . $template->cname;
        }
        $a = implode("\n", $lines);

        $subject = get_string('noactiontpls_subj', 'block_mbstpl');
        $messagetext = get_string('noactiontpls_body', 'block_mbstpl', $a);
        $managers = self::get_managers();
        foreach ($managers as $user) {
            self::send_message('reminder', $user, $subject, $messagetext);
        }
    }

    private static function get_stored_file($pathname, $filearea, $itemid = 0, $contextid = null) {

        if ($contextid === null) {
            $contextid = \context_system::instance()->id;
        }

        $fr = (object) array(
            'contextid' => $contextid,
            'component' => 'block_mbstpl',
            'filearea' => $filearea,
            'itemid' => $itemid,
            'filepath' => '/',
            'filename' => basename($pathname),
        );
        $fs = get_file_storage();

        $file = $fs->get_file($fr->contextid, $fr->component, $fr->filearea, $fr->itemid, $fr->filepath, $fr->filename);
        if ($file) {
            return $file;
        }

        return $fs->create_file_from_pathname($fr, $pathname);
    }

    /**
     * Get all managers (ie. Master Reviewers)
     */
    private static function get_managers() {
        $catid = get_config('block_mbstpl', 'deploycat');
        return get_users_by_capability(\context_coursecat::instance($catid), 'block/mbstpl:coursetemplatemanager');
    }

    private static function send_message($messagetype, $touser, $subject, $body, $attachment = null, $fromuser = null) {
        $message = new \stdClass();
        $message->component         = 'block_mbstpl';
        $message->name              = $messagetype;
        $message->userfrom          = isset($fromuser) ? $fromuser : self::get_fromuser();
        $message->userto            = $touser;
        $message->subject           = $subject;
        $message->fullmessage       = $body;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml   = '';
        $message->smallmessage      = '';
        $message->notification      = 1;

        if ($attachment instanceof \stored_file) {
            $message->attachment = $attachment;
            $message->attachname = $attachment->get_filename();
        } else if ($attachment !== null) {
            debugging("Attachments must be instances of stored_file");
        }

        message_send($message);
    }
    
    /**
     * Send complain to support.
     * @param $errordata
     */
    public static function send_complaint($errordata) {
        global $DB;
        $user = \core_user::get_user($errordata->userid);
        $user->email = $errordata->email;
        $user->maildisplay = true;
        $support = \core_user::get_support_user();
        $support->email = get_config('block_mbstpl', 'complaintemail');
        $from = $user;
        $to = $support;
        $coursename = $DB->get_field('course', 'fullname', array('id' => $errordata->courseid), MUST_EXIST);
        $courseurl = new \moodle_url('/course/view.php', array('id' => $errordata->courseid));
        $forrevision = new \moodle_url('/blocks/mbstpl/forrevision.php', array('course' => $errordata->courseid));
        $a = (object)array(
            'coursename' => $coursename,
            'details' => $errordata->details,
            'error' => $errordata->errortype,
            'from' => $from->email,
            'url' => (string) $courseurl,
            'revision'  => (string) $forrevision
        );
        $subject = get_string('emailcomplaint_subj', 'block_mbstpl');
        $body = get_string('emailcomplaint_body', 'block_mbstpl', $a);
        self::send_message('complaint', $to, $subject, $body, $attachment = null, $from);
    }
    
    /**
     * Notify the user that the complaint arrived.
     * @param $errordata
     */
    public static function notify_complaint_sent($userid, $email) {
        $user = \core_user::get_user($userid);
        $user->email = $email;
        $support = \core_user::get_support_user();
        $support->email = get_config('block_mbstpl', 'complaintemail');
        $support->maildisplay = true;
        $from = $support;
        $to = $user;
        $subject = get_string('emailcomplaintsend_subj', 'block_mbstpl');
        $body = get_string('emailcomplaintsend_body', 'block_mbstpl');
        self::send_message('complaint', $to, $subject, $body, $attachment = null, $from);
    }
}
