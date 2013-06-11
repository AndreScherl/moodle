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
 * Library functions for handling course creation requests
 *
 * @package   block_meineschulen
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

class meineschulen_course_request extends course_request {

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

        // Summary is a required field so copy the text over
        $data->summary       = $data->summary_editor['text'];
        $data->summaryformat = $data->summary_editor['format'];

        $data->id = $DB->insert_record('course_request', $data);

        // Create a new course_request object and return it
        $request = new course_request($data);

        // Notify the admin if required.
        // SYNERGY LEARNING notify users with the approvecourse capability at the category level.
        $context = context_coursecat::instance($data->category);
        if ($users = get_users_by_capability($context, 'moodle/site:approvecourse')) {
        // SYNERGY LEARNING notify users with the approvecourse capability at the category level.
            $a = new stdClass;
            // SYNERGY LEARNING change the link address.
            $a->link = new moodle_url('/blocks/meineschulen/viewrequests.php', array('id' => $data->category));
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

        $user = $DB->get_record('user', array('id' => $this->properties->requester, 'deleted'=>0), '*', MUST_EXIST);

        $courseconfig = get_config('moodlecourse');

        // Transfer appropriate settings
        $data = clone($this->properties);
        unset($data->id);
        unset($data->reason);
        unset($data->requester);

        // If the category is not set, if the current user does not have the rights to change the category, or if the
        // category does not exist, we set the default category to the course to be approved.
        // The system level is used because the capability moodle/site:approvecourse is based on a system level.

        // SYNERGY LEARNING - remove the check for 'changecategory'.
        if (empty($data->category) || (!$category = get_course_category($data->category))) {
            $category = get_course_category($CFG->defaultrequestcategory);
        }

        // Set category
        $data->category = $category->id;
        $data->sortorder = $category->sortorder; // place as the first in category

        // Set misc settings
        $data->requested = 1;

        // Apply course default settings
        $data->format             = $courseconfig->format;
        $data->newsitems          = $courseconfig->newsitems;
        $data->showgrades         = $courseconfig->showgrades;
        $data->showreports        = $courseconfig->showreports;
        $data->maxbytes           = $courseconfig->maxbytes;
        $data->groupmode          = $courseconfig->groupmode;
        $data->groupmodeforce     = $courseconfig->groupmodeforce;
        $data->visible            = $courseconfig->visible;
        $data->visibleold         = $data->visible;
        $data->lang               = $courseconfig->lang;

        $course = create_course($data);
        $context = context_course::instance($course->id, MUST_EXIST);

        // add enrol instances
        if (!$DB->record_exists('enrol', array('courseid'=>$course->id, 'enrol'=>'manual'))) {
            if ($manual = enrol_get_plugin('manual')) {
                $manual->add_default_instance($course);
            }
        }

        // enrol the requester as teacher if necessary
        if (!empty($CFG->creatornewroleid) and !is_viewing($context, $user, 'moodle/role:assign') and !is_enrolled($context, $user, 'moodle/role:assign')) {
            enrol_try_internal_enrol($course->id, $user->id, $CFG->creatornewroleid);
        }
        
        //awag: enrol the approver as teacher if necassary
        if (!empty($CFG->creatornewroleid) and !is_viewing($context, $USER, 'moodle/role:assign') and !is_enrolled($context, $USER, 'moodle/role:assign')) {
            enrol_try_internal_enrol($course->id, $USER->id, $CFG->creatornewroleid);
        }

        $this->delete();

        $a = new stdClass();
        $a->name = format_string($course->fullname, true, array('context' => context_course::instance($course->id)));
        $a->url = $CFG->wwwroot.'/course/view.php?id=' . $course->id;
        $this->notify($user, $USER, 'courserequestapproved', get_string('courseapprovedsubject'), get_string('courseapprovedemail2', 'moodle', $a));

        return $course->id;
    }

}
