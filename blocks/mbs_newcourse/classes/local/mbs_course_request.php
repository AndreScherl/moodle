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
 * class to manage a course request.
 *
 * @package    block_mbs_newcourse
 * @copyright  2014 <andre.scherl@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbs_newcourse\local;

require_once($CFG->dirroot . '/course/lib.php');

class mbs_course_request extends \course_request {

    /**
     * Static function to create a new course request when passed an array of properties
     * for it.
     *
     * This function also handles saving any files that may have been used in the editor
     *
     * @static
     * @param stdClass $data
     * @return course_request The newly created course request
     */
    public static function create($data) {
        global $USER, $DB, $CFG;

        $data->requester = $USER->id;

        // Setting the default category if none set.
        if (empty($data->category)) {
            $data->category = $CFG->defaultrequestcategory;
        }

        // Summary is a required field so copy the text over.
        $data->summary = $data->summary_editor['text'];
        $data->summaryformat = $data->summary_editor['format'];

        $data->id = $DB->insert_record('course_request', $data);

        // Create a new course_request object and return it.
        $request = new \course_request($data);

        // Notify the admin if required.
        // SYNERGY LEARNING notify users with the approvecourse capability at the category level.
        $context = \context_coursecat::instance($data->category);

        if ($users = get_users_by_capability($context, 'moodle/site:approvecourse')) {

            // SYNERGY LEARNING notify users with the approvecourse capability at the category level.
            $a = new \stdClass;
            // SYNERGY LEARNING change the link address.
            $a->link = new \moodle_url('/blocks/mbs_newcourse/viewrequests.php', array('id' => $data->category));
            $a->link = $a->link->out();
            // SYNERGY LEARNING change the link address.
            $a->user = fullname($USER);
            $subject = get_string('courserequest');
            $message = get_string('courserequestnotifyemail', 'admin', $a);
            foreach ($users as $user) {
                $request->notify($user, $USER, 'courserequested', $subject, $message);
            }
        }

        return $request;
    }

    /**
     * Can User request a course?
     *
     * @return bool true if user may request a course.
     */
    public static function can_request_course($categoryid) {
        global $DB, $USER;
        static $resp = null;

        if (is_null($resp)) {

            $resp = false;

            if (!empty($USER->isTeacher)) {

                $resp = true;
            } else {

                $context = \context_coursecat::instance($categoryid);
                if (has_capability('moodle/course:request', $context)) {

                    $resp = true;
                } else {

                    $roles = get_roles_with_capability('moodle/course:request', CAP_ALLOW);

                    if ($roles) {
                        list($rsql, $params) = $DB->get_in_or_equal(array_keys($roles), SQL_PARAMS_NAMED);
                        $params['userid'] = $USER->id;
                        $resp = $DB->record_exists_select('role_assignments', "userid = :userid AND roleid $rsql", $params);
                    }
                }
            }
        }

        return $resp;
    }

    public static function can_approve_course($categoryid) {

        $context = \context_coursecat::instance($categoryid);

        if (has_capability('moodle/site:approvecourse', $context)) {
            return true;
        }
    }

    /** get all the pending course requests for a school category (i. e. all the pending requests
     * within the category tree of school's category
     * 
     * @global object $DB
     * @param record $schoolcat the category of the school
     * @return boolean|array false if there ar no pending requests, list of requests otherwise.
     */
    public static function get_requests($schoolcat) {
        global $DB;

        // Restrict list to requests within the current school.
        $select = 'id = :schoolid OR ' . $DB->sql_like('path', ':path');
        $params = array(
            'schoolid' => $schoolcat->id,
            'path' => "{$schoolcat->path}/%"
        );

        $catids = $DB->get_fieldset_select('course_categories', 'id', $select, $params);
        return $DB->get_records_list('course_request', 'category', $catids);
    }

    /**
     * This function approves the request turning it into a course
     *
     * This function converts the course request into a course, at the same time
     * transferring any files used in the summary to the new course and then removing
     * the course request and the files associated with it.
     *
     * @return int The id of the course that was created from this request
     */
    public function approve() {
        global $CFG, $DB, $USER;

        require_once($CFG->libdir . '/coursecatlib.php');

        $user = $DB->get_record('user', array('id' => $this->properties->requester, 'deleted' => 0), '*', MUST_EXIST);

        $courseconfig = get_config('moodlecourse');

        // Transfer appropriate settings.
        $data = clone($this->properties);
        unset($data->id);
        unset($data->reason);
        unset($data->requester);

        // If the category is not set, if the current user does not have the rights to change the category, or if the
        // category does not exist, we set the default category to the course to be approved.
        // The system level is used because the capability moodle/site:approvecourse is based on a system level.
        // SYNERGY LEARNING - remove the check for 'changecategory'.
        if (empty($data->category) || (!$category = \coursecat::get($data->category, IGNORE_MISSING))) {
            $category = \coursecat::get($CFG->defaultrequestcategory);
        }

        // Set category.
        $data->category = $category->id;
        $data->sortorder = $category->sortorder; // Place as the first in category.
        // Set misc settings.
        $data->requested = 1;

        // Apply course default settings.
        $data->format = $courseconfig->format;
        $data->newsitems = $courseconfig->newsitems;
        $data->showgrades = $courseconfig->showgrades;
        $data->showreports = $courseconfig->showreports;
        $data->maxbytes = $courseconfig->maxbytes;
        $data->groupmode = $courseconfig->groupmode;
        $data->groupmodeforce = $courseconfig->groupmodeforce;
        $data->visible = $courseconfig->visible;
        $data->visibleold = $data->visible;
        $data->lang = $courseconfig->lang;

        $course = create_course($data);
        $context = \context_course::instance($course->id, MUST_EXIST);

        // ...add enrol instances.
        if (!$DB->record_exists('enrol', array('courseid' => $course->id, 'enrol' => 'manual'))) {
            if ($manual = enrol_get_plugin('manual')) {
                $manual->add_default_instance($course);
            }
        }

        // ...enrol the requester as teacher if necessary.
        if (!empty($CFG->creatornewroleid) and !is_viewing($context, $user, 'moodle/role:assign')
                and !is_enrolled($context, $user, 'moodle/role:assign')) {

            enrol_try_internal_enrol($course->id, $user->id, $CFG->creatornewroleid);
        }

        // ...enrol the approver as teacher if necassary.
        if (!empty($CFG->creatornewroleid) and !is_viewing($context, $USER, 'moodle/role:assign')
                and !is_enrolled($context, $USER, 'moodle/role:assign')) {

            enrol_try_internal_enrol($course->id, $USER->id, $CFG->creatornewroleid);
        }

        $this->delete();

        $a = new \stdClass();
        $a->name = format_string($course->fullname, true, array('context' => \context_course::instance($course->id)));
        $a->url = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
        $this->notify($user, $USER, 'courserequestapproved', get_string('courseapprovedsubject'),
                get_string('courseapprovedemail2', 'moodle', $a));

        return $course->id;
    }

    /** get all data for all with pending course requests this user can approve to
     *  generate link list etc.
     * 
     * @global object $DB
     * @global type $USER
     * @return type
     */
    public static function get_course_requests() {
        global $DB, $USER;

        $schoolcatdepth = \local_mbs\local\schoolcategory::$schoolcatdepth;

        // Find all the roles that can approve courses.
        if (!$roles = get_roles_with_capability('moodle/site:approvecourse', CAP_ALLOW)) {
            return array();
        }
        $roleids = array_keys($roles);

        // Find all the categories where the user has been assigned one of these roles.
        list($rsql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);
        $params['contextcoursecat'] = CONTEXT_COURSECAT;
        $params['userid'] = $USER->id;
        $sql = "SELECT cx.instanceid
                  FROM {role_assignments} ra
                  JOIN {context} cx ON cx.id = ra.contextid AND cx.contextlevel = :contextcoursecat
                 WHERE roleid $rsql AND ra.userid = :userid";
        $catids = $DB->get_fieldset_sql($sql, $params);
        if (!$catids) {
            return array();
        }

        // Find all the course requests that are within one of these categories.
        list($csql, $params) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED);
        $matchpath = $DB->sql_concat('c2.path', "'/%'");
        $sql = "SELECT cr.id, c.name, c.path
                  FROM {course_request} cr
                  JOIN {course_categories} c ON c.id = cr.category
                  JOIN {course_categories} c2 ON c2.id {$csql} AND (c2.id = c.id OR c.path LIKE {$matchpath})
                  ";
        $requests = $DB->get_records_sql($sql, $params);

        $ret = array();
        foreach ($requests as $request) {
            $path = explode('/', $request->path);
            if ((count($path) - 1) < $schoolcatdepth) {
                continue; // Request not within a school.
            }
            $schoolid = $path[$schoolcatdepth];
            if (!isset($ret[$schoolid])) {
                $ret[$schoolid] = (object) array(
                            'id' => $schoolid,
                            'name' => null,
                            'count' => 0,
                            'viewurl' => new \moodle_url('/blocks/mbs_newcourse/viewrequests.php', array('id' => $schoolid))
                );
            }
            if ((count($path) - 1) == $schoolcatdepth) {
                $ret[$schoolid]->name = $request->name; // This category is the top-level school category => store the name.
            }
            $ret[$schoolid]->count++;
        }

        // Look up the names for any schools that we haven't already retrieved the names for.
        $neednames = array();
        foreach ($ret as $school) {
            if (!$school->name) {
                $neednames[$school->id] = $school->id;
            }
        }
        if (!empty($neednames)) {
            $names = $DB->get_records_list('course_categories', 'id', $neednames, '', 'id, name');
            foreach ($names as $name) {
                $ret[$name->id]->name = $name->name;
            }
        }

        return $ret;
    }

}