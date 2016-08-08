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

use core\task\adhoc_task;

defined('MOODLE_INTERNAL') || die();

/**
 * Class course
 * For course-related operations.
 * @package block_mbstpl
 */
class course {

    const TPLPREFIX = 'Austauschkurs';
    const BACKUP_LOCALPATH = 'mbstpl';

    /**
     * Extends the navigation, depending on capability.
     * @param \navigation_node $coursenode
     * @param \context_course $coursecontext
     */
    public static function mbstplnav(\custom_menu $menu, \context_course $coursecontext) {
        global $USER;

        $cid = $coursecontext->instanceid;

        /* @var $template dataobj\template */
        $template = dataobj\template::fetch(array('courseid' => $cid));

        if (!$template && has_capability('block/mbstpl:sendcoursetemplate', $coursecontext)) {
            $url = new \moodle_url('/blocks/mbstpl/sendtemplate.php', array('course' => $cid));
            $menu->add(get_string('sendcoursetemplate', 'block_mbstpl'), $url, get_string('sendcoursetemplate', 'block_mbstpl'));
        }

        if ($template) {

            $isauthor = $template->authorid == $USER->id;

            if (perms::can_assignauthor($template, $coursecontext)) {
                $url = new \moodle_url('/blocks/mbstpl/assign.php', array('course' => $cid, 'type' => 'author'));
                $menu->add(get_string('assignauthor', 'block_mbstpl'), $url, get_string('assignauthor', 'block_mbstpl'));
            }

            if (!$isauthor && (perms::can_assignreview($template, $coursecontext) || perms::can_returnreview($template, $coursecontext))) {
                $url = new \moodle_url('/blocks/mbstpl/assign.php', array('course' => $cid, 'type' => 'reviewer'));
                $menu->add(get_string('assignreviewer', 'block_mbstpl'), $url, get_string('assignreviewer', 'block_mbstpl'));
            }

            if (perms::can_viewfeedback($template, $coursecontext)) {
                $url = new \moodle_url('/blocks/mbstpl/viewfeedback.php', array('course' => $cid));
                $menu->add(get_string('templatefeedback', 'block_mbstpl'), $url, get_string('templatefeedback', 'block_mbstpl'));
            }

            if (perms::can_editmeta($template, $coursecontext)) {
                $url = new \moodle_url('/blocks/mbstpl/editmeta.php', array('course' => $cid));
                $menu->add(get_string('editmeta', 'block_mbstpl'), $url, get_string('editmeta', 'block_mbstpl'));
            }

            if (perms::can_viewabout($coursecontext)) {
                $url = new \moodle_url('/blocks/mbstpl/abouttemplate.php', array('course' => $cid));
                $menu->add(get_string('mbstpl:abouttemplate', 'block_mbstpl'), $url, get_string('mbstpl:abouttemplate', 'block_mbstpl'));
            }

            if (perms::can_leaverating($coursecontext)) {
                $url = new \moodle_url('/blocks/mbstpl/ratetemplate.php', array('course' => $cid));
                $menu->add(get_string('mbstpl:ratetemplate', 'block_mbstpl'), $url, get_string('mbstpl:ratetemplate', 'block_mbstpl'));
            }

            if (perms::can_coursefromtpl($template)) {
                $url = new \moodle_url('/blocks/mbstpl/dupcrs.php', array('course' => $cid));
                $menu->add(get_string('duplcourseforuse', 'block_mbstpl'), $url, get_string('duplcourseforuse', 'block_mbstpl'));
            }

            if (perms::can_sendrevision($template, $coursecontext)) {
                $url = new \moodle_url('/blocks/mbstpl/forrevision.php', array('course' => $cid));
                $menu->add(get_string('forrevision', 'block_mbstpl'), $url, get_string('forrevision', 'block_mbstpl'));
            }

            if (perms::can_createdpublishedbackup($template, $coursecontext)) {
                $url = new \moodle_url('/blocks/mbstpl/publishedbackup.php', array('course' => $cid));
                $tplnode->add(get_string('templatereset', 'block_mbstpl'), $url, get_string('templatereset', 'block_mbstpl'));
            }
        }

        if (perms::can_viewhistory($coursecontext)) {
            $url = new \moodle_url('/blocks/mbstpl/viewhistory.php', array('course' => $cid));
            $menu->add(get_string('viewhistory', 'block_mbstpl'), $url, get_string('viewhistory', 'block_mbstpl'));
        }
        
        return $menu;
    }

    /**
     * Get all the licenses to fill the course license dropdown.
     *
     * @return array list of available licenses
     */
    public static function get_course_licenses() {
        global $DB;
        $sql = 'SELECT *
                  FROM {block_mbstpl_clicense} AS cl
                  JOIN {license} AS l
                    ON cl.shortname = l.shortname';
        $recordsoutput = array();
        // get licenses by conditions
        if ($records = $DB->get_records_sql($sql)) {
            $recordsoutput = $records;
        }
        return $recordsoutput;
    }

    /**
     * Add course license
     *
     * @global $DB
     * @param string $shortname
     * @return bool|int true or insert id
     */
    public static function add_course_license($shortname) {
        global $DB;
        $data = new \stdClass();
        $data->shortname = $shortname;
        return $DB->insert_record('block_mbstpl_clicense', $data);
    }

    /**
     * Get single course license by shortname
     *
     * @global $DB
     * @param string $shortname
     * @return bool|object - database record or false
     */
    public static function get_course_license($shortname) {
        global $DB;
        return $DB->get_record('block_mbstpl_clicense', array('shortname' => $shortname));
    }

    /**
     * Remove course license
     *
     * @global $DB
     * @param string $shortname
     * @return bool true
     */
    public static function remove_course_license($shortname) {
        global $DB;
        return $DB->delete_records('block_mbstpl_clicense', array('shortname' => $shortname));
    }

    public static function get_statuses() {
        return array(
            dataobj\template::STATUS_CREATED => 'statuscreated',
            dataobj\template::STATUS_UNDER_REVIEW => 'statusunderreview',
            dataobj\template::STATUS_UNDER_REVISION => 'statusunderrevision',
            dataobj\template::STATUS_PUBLISHED => 'statuspublished',
            dataobj\template::STATUS_ARCHIVED => 'statusarchived',
            dataobj\template::STATUS_ASSIGNED_REVIEWER => 'statusassignedreviewer'
        );
    }

    public static function get_statuses_menu() {

        $menu = array();
        $statuses =self::get_statuses();

        foreach($statuses as $key => $shortname) {
            $menu[$key] = get_string($shortname, 'block_mbstpl');
        }

        asort($menu);
        return $menu;
    }

    /**
     * Returns the shortname of the status.
     * @param $status
     */
    public static function get_statusshortname($status) {
        $statuses =self::get_statuses();
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
            foreach ($templates as $template) {
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
        if (!perms::can_publish($template)) {
            return false;
        }

        // Set course visible.
        $cid = $template->courseid;
        course_change_visibility($cid, true);

        // Unenrol everybody
        $userenrolments = self::get_all_enrolled_users($cid);
        if (!empty($userenrolments)) {
            self::unenrol($cid, $userenrolments);
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
     * Assign author to a course. Assumes can_assignauthor() has already been called.
     * @param dataobj\template $template
     * @param int $userid
     */
    public static function assign_author(dataobj\template $template, $userid, $feedback = null, $feedbackformat = null) {
        // Mark author in the template record.
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

        // Enrol author.
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
        $template->status = dataobj\template::STATUS_ASSIGNED_REVIEWER;

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

        // Get additional information for the case user is already deleted.
        $deletedusername = $DB->sql_fullname("COALESCE(ud.firstname, ' ')", "COALESCE(ud.lastname, ' ')");
        $udsql = " $deletedusername AS deletedusername, ud.userid as deleteduserid ";

        $sql = "
        SELECT rh.id, rh.status, rh.timecreated, u.firstname, u.lastname, rh.feedback, rh.feedbackformat, $udsql
        FROM {block_mbstpl_revhist} rh
        JOIN {user} u ON u.id = rh.assignedid
        LEFT JOIN {block_mbstpl_userdeleted} ud ON ud.userid = rh.assignedid
        WHERE rh.templateid = ?
        ORDER BY rh.id DESC
        ";

        return $DB->get_records_sql($sql, array($templateid));
    }

    /**
     * Get all files associated with an array of rev history objects
     *
     * @param \block_mbstpl\dataobj\revhist[] $revhist
     * @return array associative array mapping revhist id to an array of files
     */
    public static function get_revhist_files($revhists, $template) {

        $context = \context_course::instance($template->courseid);
        $files = array();
        foreach ($revhists as $hist) {
            if (!($hist instanceof dataobj\revhist)) {
                $hist = new dataobj\revhist((array) $hist, false);
            }
            $files[$hist->id] = $hist->get_files($context);
        }

        return $files;
    }

    /**
     * Get template's current/last assignee out of revision history.
     * @param int $templateid
     * @return array object
     */
    public static function get_lastassignee($templateid) {
        global $DB;
        $sql = "
        SELECT u.firstname, u.lastname, u.id
        FROM {block_mbstpl_revhist} rh
        JOIN {user} u ON u.id = rh.assignedid
        WHERE rh.templateid = ?
        ORDER BY rh.id DESC
        LIMIT 1
        ";
        return $DB->get_record_sql($sql, array($templateid));
    }

    /**
     * Gets a list of everyone who created the course template.
     * @param $templateid
     */
    public static function get_creators($templateid, $includereviehistory = false) {
        global $DB;

        $fields = get_all_user_name_fields(true, 'u');

        $deletedusername = $DB->sql_fullname("COALESCE(ud.firstname, ' ')", "COALESCE(ud.lastname, ' ')");
        $udsql = " $deletedusername AS deletedusername, ud.userid as deleteduserid ";

        if ($includereviehistory) {
            $sql = "
            SELECT u.id, $fields, $udsql
            FROM {block_mbstpl_revhist} rh
            JOIN {user} u ON u.id = rh.assignedid
            LEFT JOIN {block_mbstpl_userdeleted} ud ON ud.userid = rh.assignedid
            WHERE rh.templateid = ?
            GROUP BY u.id
            ";
        } else {

            $sql = "
            SELECT u.id, $fields, $udsql
            FROM {block_mbstpl_template} tpl
            JOIN {user} u ON u.id = tpl.authorid
            LEFT JOIN {block_mbstpl_userdeleted} ud ON ud.userid = tpl.authorid
            WHERE tpl.id = ?
            ";
        }

        $results = $DB->get_records_sql($sql, array($templateid));
        $creators = array();
        foreach ($results as $result) {

            if (!empty($result->deleteduserid)) {
                $creators[] = $result->deletedusername;
            } else {
                $creators[] = fullname($result);
            }
        }
        return implode(', ', $creators);
    }

    public static function get_courses_with_creators($templateid) {
        global $DB;

        $fields = get_all_user_name_fields(true, 'u');

        $sql = "
            SELECT c.id course_id, c.fullname course_fullname, c.shortname course_shortname,
            cft.createdon course_createdon, $fields, u.id user_id
            FROM {course} c
            JOIN {block_mbstpl_coursefromtpl} cft ON cft.courseid = c.id
            JOIN {block_mbstpl_template} t ON cft.templateid = t.id
            LEFT JOIN {user} u ON cft.createdby = u.id
            WHERE t.id = ?";

        $results = $DB->get_records_sql($sql, array($templateid));

        return array_map(function($result) {
            $result->course_creator_name = $result->user_id ? fullname($result) : "";
            return $result;
        }, $results);
    }

    /**
     * Convenience function for IDEs.
     * @return \block_mbstpl_renderer
     */
    public static function get_renderer() {
        global $PAGE;
        return $PAGE->get_renderer('block_mbstpl');
    }

    /**
     * Get the complaint url for this course, if its a template or template-based course
     *
     * @return \moodle_url complaint url for the current course/template
     *                     or null if not on a template or course template
     */
    public static function get_complaint_url($courseid = null) {

        static $complainturl = null;
        if ($complainturl === null) {
            $complainturl = get_config('block_mbstpl', 'complainturl');
        }

        if (!$complainturl) {
            return null;
        }

        if (!$courseid) {
            global $PAGE;
            $courseid = $PAGE->context->instanceid;
        }

        $template = dataobj\template::get_from_course($courseid);
        if (!$template) {
            return null;
        }

        $params = array('templateid' => $template->id);
        if ($template->id != $courseid) {
            $params['courseid'] = $courseid;
        }

        $complainturl = new \moodle_url($complainturl, $params);
        return $complainturl;
    }

    /**
     * Archive the course.
     * @param dataobj\template $template
     * @return bool success
     */
    public static function archive(dataobj\template $template) {
        if (!perms::can_archive($template)) {
            return false;
        }

        $cid = $template->courseid;

        // Delete all enrolements
        self::delete_enrol_instances($cid);
        // Unenrol everybody who was enroled manual
        $userenrolments = course::get_all_enrolled_users($cid);
        if (!empty($userenrolments)) {
            course::unenrol($cid, $userenrolments);
        }

        // Make course invisible
        course_change_visibility($template->courseid, false);

        // Update status.
        $template->status = $template::STATUS_ARCHIVED;
        $template->update();
        return true;
    }

    /**
     * Convenience function to unenrol given userids from all plugins of course.
     * @param int $cid
     * @param array $userids
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function unenrol_users($cid, $userids) {
        global $DB;

        if (empty($userids)) {
            return;
        }

        list($useridin, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
        $params['courseid'] = $cid;
        $params['courselevel'] = CONTEXT_COURSE;
        $sql = "SELECT DISTINCT ue.*
                FROM {user_enrolments} ue
                JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = :courseid)
                WHERE ue.userid $useridin
                ";
        $enrolments = $DB->get_records_sql($sql, $params);

        self::unenrol($cid, $enrolments);
    }

    /**
     * Function to get all enrolled users of a course.
     * @param int $courseid
     * @return array $enrolments
     */
    public static function get_all_enrolled_users($courseid) {
        global $DB;
        $enrolments = $DB->get_records('enrol', array('courseid' => $courseid));
        if (!empty($enrolments)) {
            list($searchcriteria, $params) = $DB->get_in_or_equal(array_keys($enrolments), SQL_PARAMS_NAMED);
            $searchcriteria = 'enrolid ' . $searchcriteria;
            $userenrolments = $DB->get_records_select('user_enrolments', $searchcriteria, $params);

            return $userenrolments;
        }
        return array();
    }

    /**
     * Function to unenrol all users for given enrolements of a course.
     * @param int $courseid
     * @param array $enrolments
     * @return void
     */
    public static function unenrol($courseid, $enrolments) {
        $plugins = enrol_get_plugins(true);
        $instances = enrol_get_instances($courseid, true);
        foreach ($instances as $key => $instance) {
            if (!isset($plugins[$instance->enrol])) {
                unset($instances[$key]);
                continue;
            }
        }

        foreach ($enrolments as $ue) {
            if (!isset($instances[$ue->enrolid])) {
                continue;
            }
            $instance = $instances[$ue->enrolid];
            $plugin = $plugins[$instance->enrol];
            if (!$plugin->allow_unenrol($instance) and ! $plugin->allow_unenrol_user($instance, $ue)) {
                continue;
            }
            $plugin->unenrol_user($instance, $ue->userid);
        }
    }

    /**
     * Delete all course enrol plugin instances except 'manual', unenrol all users.
     * @param int $courseid
     * @param array $exclude_enrolments
     * @return void
     */
    public static function delete_enrol_instances($courseid, $exclude_enrolments = array()) {
        $exclude_enrolments[] = 'manual';
        $plugins = enrol_get_plugins(true);
        $instances = enrol_get_instances($courseid, false);
        foreach ($instances as $instance) {
            if (!in_array($instance->enrol, $exclude_enrolments)) {
                $plugin = $plugins[$instance->enrol];
                $plugin->delete_instance($instance);
            }
        }
    }

    /**
     * Returns whether a module has changed since last reset time by executing
     * callback MODULE_print_recent_activity()
     *
     * @param object $course, the course to check
     * @param int $lastresettime the time stamp since when changes should be detected
     * @param array $modsunchecked list of mods in the course that are not yet checked.
     * @return boolean true, when there was changes detected.
     */
    protected static function get_changed_mods_by_recent_activity($course, $lastresettime, &$modsunchecked) {

        $hascontent = false;
        $modinfo = get_fast_modinfo($course);
        $usedmodules = $modinfo->get_used_module_names();

        $checkedmodules = array();
        $changedmodules = array();
        foreach ($usedmodules as $modname => $modfullname) {
            // Each module gets it's own logs and prints them.
            ob_start();
            $hascontent = component_callback('mod_' . $modname, 'print_recent_activity', array($course, false, $lastresettime), 0);

            // Function does exist.
            if ($hascontent !== 0) {
                $checkedmodules[$modname] = $modfullname;
            }

            // If has_content is true, we unfortunately must check whether there is an
            // non empty string in output as there are modules which return true
            // but doesn't have news (like the gallery).
            if ($hascontent) {
                $output = ob_get_contents();
                if (!empty($output)) {
                    $changedmodules[$modname] = $modfullname;
                }
            }
            ob_end_clean();
        }

        $modsunchecked = array_diff_key($usedmodules, $checkedmodules);

        if (empty($changedmodules)) {
            return false;
        }

        return $changedmodules;
    }

    /**
     * Check, whether there was changes in the choice module content.
     *
     * @param object $course
     * @param int $lastresettime
     * @return boolean
     */
    public static function has_changed_choice($course, $lastresettime) {
        global $DB;

        $sql = "SELECT count(*)
                FROM {choice} ch
                JOIN {choice_answers} ca ON ca.choiceid = ch.id
                WHERE ch.course = :course AND ca.timemodified >= :lastresettime";

        $params = array(
            'course' => $course->id,
            'lastresettime' => $lastresettime
        );

        $countmodified = $DB->count_records_sql($sql, $params);

        return ($countmodified > 0);
    }

    /**
     * Check, whether there was changes in the anonymous choice module content.
     *
     * @param object $course
     * @param int $lastresettime
     * @return boolean
     */
    public static function has_changed_choiceanon($course, $lastresettime) {
        global $DB;

        $sql = "SELECT count(*)
                FROM {choiceanon} ch
                JOIN {choiceanon_answers} ca ON ca.choiceid = ch.id
                WHERE ch.course = :course AND ca.timemodified >= :lastresettime";

        $params = array(
            'course' => $course->id,
            'lastresettime' => $lastresettime
        );

        $countmodified = $DB->count_records_sql($sql, $params);

        return ($countmodified > 0);
    }

    /**
     * Check, whether there was changes in the data module content.
     *
     * @param object $course
     * @param int $lastresettime
     * @return boolean
     */
    public static function has_changed_data($course, $lastresettime) {
        global $DB;

        $sql = "SELECT count(*)
                FROM {data} d
                JOIN {data_records} dr ON dr.dataid = d.id
                WHERE d.course = :course AND dr.timemodified >= :lastresettime";

        $params = array(
            'course' => $course->id,
            'lastresettime' => $lastresettime
        );

        $countmodified = $DB->count_records_sql($sql, $params);

        return ($countmodified > 0);
    }

    /**
     * Check, whether there was changes in the folder module content.
     *
     * @param object $course
     * @param int $lastresettime
     * @return boolean
     */
    public static function has_changed_folder($course, $lastresettime) {
        global $DB;

       $sql = "SELECT count(*)
                FROM {quiz} q
                JOIN {quiz_attempts} qa ON qa.quiz = q.id
                WHERE q.course = :course AND qa.timemodified >= :lastresettime";

        $params = array(
            'course' => $course->id,
            'lastresettime' => $lastresettime
        );

        $countmodified = $DB->count_records_sql($sql, $params);

        return ($countmodified > 0);
   }

    /**
     * Check, whether there was changes in the quiz module content.
     *
     * @param object $course
     * @param int $lastresettime
     * @return boolean
     */
    public static function has_changed_quiz($course, $lastresettime) {
        global $DB;

        $sql = "SELECT cm.id, i.timemodified, MAX(f.timemodified) AS filemodified
                      FROM {folder} i
                      JOIN {course_modules} cm ON cm.instance = i.id
                      JOIN {modules} m ON m.id = cm.module AND m.name = 'folder'
                      JOIN {context} cx ON cx.instanceid = cm.id AND cx.contextlevel = :contextmodule
                      JOIN {files} f ON f.contextid = cx.id AND f.component = 'mod_folder' AND f.filearea = 'content'
                                     AND f.filename <> '.' AND f.itemid = 0
                     WHERE i.course = :course
                     GROUP BY i.id, i.timemodified
                     HAVING (filemodified >= :lastresettime1) OR (i.timemodified > :lastresettime2)";

        $params = array(
            'contextmodule' => CONTEXT_MODULE,
            'course' => $course->id,
            'lastresettime1' => $lastresettime,
            'lastresettime2' => $lastresettime
        );

        $modifiedmodules = $DB->get_records_sql($sql, $params);

        return (count($modifiedmodules) > 0);
    }

    /**
     * Try to detect, whether the course content has changed during the last reset
     * time.
     *
     * @param object $course
     * @param int $lastresettime
     * @param array $modsunchecked
     * @return boolean true if there are change detected
     */
    public static function has_course_content_changed($course, $lastresettime, &$modsunchecked) {

        // Check the standard functions.
        $changedmodules = self::get_changed_mods_by_recent_activity($course, $lastresettime, $modsunchecked);
        if ($changedmodules) {
            return $changedmodules;
        }
        // No changes in checked modules and no more modules to check.
        if (empty($modsunchecked)) {
            return false;
        }

        // If there are more modules to check, try to call methodes of this class.
        // If no method is available count as unchanged.
        $params = array($course, $lastresettime);
        $checkedmodules = array();

        foreach ($modsunchecked as $modname => $modfullname) {

            $function = '\block_mbstpl\course::has_changed_' . $modname;
            if (method_exists('\block_mbstpl\course', 'has_changed_' . $modname)) {

                $changed = call_user_func_array($function, $params);
                $checkedmodules[$modname] = $modfullname;

                if ($changed) {
                    $changedmodules[$modname] = $modfullname;
                }
            }
        }

        $modsunchecked = array_diff_key($modsunchecked, $checkedmodules);

        if (empty($changedmodules)) {
            return false;
        }

        return $changedmodules;
    }

    public static function get_implemented_has_changed_modules() {

        $modulesnames = get_module_types_names();

        $implementedmodnames = array();
        foreach ($modulesnames as $modname => $fullmodname) {

            $function = '\block_mbstpl\course::has_changed_' . $modname;
            if (method_exists('\block_mbstpl\course', 'has_changed_' . $modname)) {
                $implementedmodnames[$modname] = $modname;
            }
        }


        return $implementedmodnames;
    }

}
