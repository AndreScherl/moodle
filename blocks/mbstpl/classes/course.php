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
 * Class course
 * For course-related operations.
 * @package block_mbstpl
 */
class course {

    const TPLPREFIX = 'Musterkurs';
    const BACKUP_LOCALPATH = 'mbstpl';

    /**
     * Extends the navigation, depending on capability.
     * @param \navigation_node $coursenode
     * @param \context_course $coursecontext
     */
    public static function extend_coursenav(\navigation_node &$coursenode, \context_course $coursecontext) {
        $tplnode = $coursenode->create(get_string('pluginname', 'block_mbstpl'), null, \navigation_node::COURSE_CURRENT);
        $cid = $coursecontext->instanceid;

        /** @var dataobj\template $template */
        $template = dataobj\template::fetch(array('courseid' => $cid));

        if (!$template && has_capability('block/mbstpl:sendcoursetemplate', $coursecontext)) {
            $url = new \moodle_url('/blocks/mbstpl/sendtemplate.php', array('course' => $cid));
            $tplnode->add(get_string('sendcoursetemplate', 'block_mbstpl'), $url);
        }

        if ($template) {
            if (perms::can_assignauthor($template, $coursecontext)) {
                $url = new \moodle_url('/blocks/mbstpl/assign.php', array('course' => $cid, 'type' => 'author'));
                $tplnode->add(get_string('assignauthor', 'block_mbstpl'), $url);
            }

            if (perms::can_assignreview($template, $coursecontext) || perms::can_returnreview($template, $coursecontext)) {
                $url = new \moodle_url('/blocks/mbstpl/assign.php', array('course' => $cid, 'type' => 'reviewer'));
                $tplnode->add(get_string('assignreviewer', 'block_mbstpl'), $url);
            }

            if (perms::can_viewfeedback($template, $coursecontext)) {
                $url = new \moodle_url('/blocks/mbstpl/viewfeedback.php', array('course' => $cid));
                $tplnode->add(get_string('templatefeedback', 'block_mbstpl'), $url);
            }

            if (perms::can_editmeta($template, $coursecontext)) {
                $url = new \moodle_url('/blocks/mbstpl/editmeta.php', array('course' => $cid));
                $tplnode->add(get_string('editmeta', 'block_mbstpl'), $url);
            }

            if (perms::can_coursefromtpl($template, $coursecontext)) {
                $url = new \moodle_url('/blocks/mbstpl/dupcrs.php', array('course' => $cid));
                $tplnode->add(get_string('duplcourseforuse', 'block_mbstpl'), $url);
            }

        if ($template && perms::can_coursefromtpl($template, $coursecontext)) {
            $url = new \moodle_url('/blocks/mbstpl/dupcrs.php', array('course' => $cid));
            $tplnode->add(get_string('duplcourseforuse', 'block_mbstpl'), $url);
        }

        if ($template && perms::can_coursefromtpl($template, $coursecontext)) {
            $url = new \moodle_url('/blocks/mbstpl/dupcrs.php', array('course' => $cid));
            $tplnode->add(get_string('duplcourseforuse', 'block_mbstpl'), $url);
        }

            if (perms::can_leaverating($coursecontext)) {
                $url = new \moodle_url('/blocks/mbstpl/ratetemplate.php', array('course' => $cid));
                $tplnode->add(get_string('mbstpl:ratetemplate', 'block_mbstpl'), $url);
            }
        }

        if (perms::can_viewrating($coursecontext)) {
            $url = new \moodle_url('/blocks/mbstpl/viewrating.php', array('course' => $cid));
            $tplnode->add(get_string('viewrating', 'block_mbstpl'), $url);
        }

        if ($tplnode->has_children()) {
            $coursenode->add_node($tplnode);
        }
    }


    /**
     * Returns the shortname of the status.
     * @param $status
     */
    public static function get_statusshortname($status) {
        $statuses = array(
            dataobj\template::STATUS_CREATED => 'statuscreated',
            dataobj\template::STATUS_UNDER_REVIEW => 'statusunderreview',
            dataobj\template::STATUS_UNDER_REVISION => 'statusunderrevision',
            dataobj\template::STATUS_PUBLISHED => 'statuspublished',
            dataobj\template::STATUS_ARCHIVED => 'statusarchived',
        );
        return $statuses[$status];
    }

    /**
     * Clean up after a course has been deleted.
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        $data = $event->get_data();
        $cid = $data['courseid'];

        // Clean up template.
        $templates = dataobj\template::fetch_all(array('courseid' => $cid));
        if (!empty($templates)) {
            foreach($templates as $template) {
                $template->delete();
            }
        }

        // Clean up course from template.
        $coursefromtpl = new dataobj\coursefromtpl(array('courseid' => $cid));
        if ($coursefromtpl->fetched) {
            $coursefromtpl->delete();
        }
    }

    /**
     * Publish the course.
     * @param dataobj\template $template
     * @return bool success
     */
    public static function publish(dataobj\template $template) {
        global $DB;

        if (!perms::can_publish($template)) {
            return false;
        }

        // Set course visible.
        $cid = $template->courseid;
        $DB->update_record('course', (object)array('id' => $cid, 'visible' => 1));

        // Unenrol reviewer and author.
        $userids = array($template->reviewerid, $template->authorid);
        $plugins = enrol_get_plugins(true);
        $instances = enrol_get_instances($cid, true);
        foreach ($instances as $key => $instance) {
            if (!isset($plugins[$instance->enrol])) {
                unset($instances[$key]);
                continue;
            }
        }
        list($useridin, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
        $params['courseid'] = $cid;
        $params['courselevel'] = CONTEXT_COURSE;
        $sql = "SELECT ue.*
                FROM {user_enrolments} ue
                JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = :courseid)
                JOIN {context} c ON (c.contextlevel = :courselevel AND c.instanceid = e.courseid)
                JOIN {role_assignments} ra ON (ra.contextid = c.id AND ra.userid $useridin)";
        $enrolments = $DB->get_records_sql($sql, $params);
        foreach ($enrolments as $ue) {
            if (!isset($instances[$ue->enrolid])) {
                continue;
            }
            $instance = $instances[$ue->enrolid];
            $plugin = $plugins[$instance->enrol];
            if (!$plugin->allow_unenrol($instance) and !$plugin->allow_unenrol_user($instance, $ue)) {
                continue;
            }
            $plugin->unenrol_user($instance, $ue->userid);
        }

        // Notify user.
        notifications::notify_published($template);

        // Update status.
        $template->status = $template::STATUS_PUBLISHED;
        $template->update();
        return true;
    }

    /**
     * Set a new feedback to the template and send to author.
     * @param dataobj\template $template
     * @param array $feedback
     * @param int $newstatus
     */
    public static function set_feedback(dataobj\template $template, $feedback, $newstatus = null) {
        $template->feedback = $feedback['text'];
        $template->feedbackformat = $feedback['format'];
        if ($newstatus !== null) {
            $template->status = $newstatus;
        }
        $template->update();
        notifications::send_feedback($template);
    }

    /**
     * Assign reviewer to a course. Assumes can_assignreview() has already been called.
     * @param dataobj\template $template
     * @param int $userid
     */
    public static function assign_author(dataobj\template $template, $userid, $feedback = null, $feedbackformat = null) {
        // Mark reviewer in the template record.
        if ($userid) {
            $template->authorid = $userid;
        } else if (!$template->authorid) {
            throw new \coding_exception('Must specify authorid if none already set');
        }
        $template->status = dataobj\template::STATUS_UNDER_REVISION;

        if ($feedback !== null) {
            // Save the feedback as well.
            if ($feedbackformat === null) {
                throw new \coding_exception('Must specify feedbackformat when setting feedback');
            }
            $template->feedback = $feedback;
            $template->feedbackformat = $feedbackformat;
        }
        $template->update();

        // Enrol reviewer.
        user::enrol_author($template->courseid, $userid);
    }

    /**
     * Assign reviewer to a course. Assumes can_assignreview() has already been called.
     * @param dataobj\template $template
     * @param int $userid
     */
    public static function assign_reviewer(dataobj\template $template, $userid, $feedback = null, $feedbackformat = null) {
        // Mark reviewer in the template record.
        if ($userid) {
            $template->reviewerid = $userid;
        } else {
            if (!$template->reviewerid) {
                throw new \coding_exception('Must specify reviewerid if none already set');
            }
            $userid = $template->reviewerid;
        }
        $template->status = dataobj\template::STATUS_UNDER_REVIEW;

        if ($feedback !== null) {
            // Save the feedback as well.
            if ($feedbackformat === null) {
                throw new \coding_exception('Must specify feedbackformat when setting feedback');
            }
            $template->feedback = $feedback;
            $template->feedbackformat = $feedbackformat;
        }

        $template->update();

        // Enrol reviewer.
        user::enrol_reviewer($template->courseid, $userid);
    }

    /**
     * Get template's revision history.
     * @param int $templateid
     * @return array
     */
	public static function get_revhist($templateid) {
		global $DB;
		$sql = "
		SELECT rh.id, rh.status, rh.timecreated, u.firstname, u.lastname, rh.feedback <> ? AS hasfeedback
		FROM {block_mbstpl_revhist} rh
		JOIN {user} u ON u.id = rh.assignedid
		WHERE rh.templateid = ?
		ORDER BY rh.id DESC
		";
		return $DB->get_records_sql($sql, array('', $templateid));
	}

    /**
     * Gets a list of everyone who created the course template.
     * @param $templateid
     */
    public static function get_creators($templateid) {
        global $DB;

        $fields = get_all_user_name_fields(true, 'u');
        $sql = "
        SELECT u.id, $fields
        FROM {block_mbstpl_revhist} rh
        JOIN {user} u ON u.id = rh.assignedid
        WHERE rh.templateid = ?
        GROUP BY u.id
        ";
        $results = $DB->get_records_sql($sql, array($templateid));
        $creators = array();
        foreach($results as $result) {
            $creators[] = fullname($result);
        }
        return implode(', ', $creators);
    }

    /**
     * Convenience function for IDEs.
     * @return \block_mbstpl_renderer
     */
    public static function get_renderer() {
        global $PAGE;
        return $PAGE->get_renderer('block_mbstpl');
    }
}
