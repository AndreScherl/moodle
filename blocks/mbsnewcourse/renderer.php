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
 * block_mbsnewcourse renderer
 *
 * @package    block_mbsnewcourse
 * @copyright  2015 Andreas Wagner, ISB Bayern
 * @license    todo
 */
defined('MOODLE_INTERNAL') || die;

class block_mbsnewcourse_renderer extends plugin_renderer_base {

    /** render the content of the block.
     *
     * @return string
     */
    public function render_block_content($categoryid) {

        $out = '';

        if (\block_mbsnewcourse\local\mbs_course_request::can_request_course($categoryid)) {

            $url = new moodle_url('/blocks/mbsnewcourse/request.php', array('category' => $categoryid));
            $requestlink = html_writer::link($url, get_string('requestcourse', 'block_mbsnewcourse'), array('id' => 'requestcourse'));
            $out .= html_writer::tag('li', $requestlink);
        }

        if (\block_mbsnewcourse\local\mbsnewcourse::can_create_course($categoryid)) {

            $url = new moodle_url('/course/edit.php', array('category' => $categoryid, 'returnto' => 'category'));
            $createlink = html_writer::link($url, get_string('createcourse', 'block_mbsnewcourse'), array('id' => 'createcourse'));
            $out .= html_writer::tag('li', $createlink);
        }

        $out = html_writer::tag('ul', $out);

        if (\block_mbsnewcourse\local\mbs_course_request::can_approve_course($categoryid)) {
            $out .= $this->render_request_list();
        }

        return $out;
    }

    /** render the list for users, which can approve the course request
     * 
     * @global object $OUTPUT
     * @global record $USER
     * @param int $categoryid the categoryid, where the requested course should be placed.
     * @return string the rendered approvers list.
     */
    public static function render_approvers_list($categoryid) {
        global $OUTPUT, $USER;

        $out = '';

        $context = context_coursecat::instance($categoryid);
        $coordinators = get_users_by_capability($context, 'moodle/site:approvecourse');

        foreach ($coordinators as $coordinator) {

            $messageurl = new moodle_url('/message/index.php', array('id' => $coordinator->id));
            $messageicon = $OUTPUT->pix_icon('t/email', get_string('sendmessage', 'block_mbsnewcourse'));
            $messagelink = html_writer::link($messageurl, $messageicon);

            if (has_capability('moodle/user:viewdetails', $context, $USER->id)) {

                $profileurl = new moodle_url('/user/profile.php', array('id' => $coordinator->id));
            } else {

                $profileurl = $messageurl;
            }
            $coordlink = $messagelink . ' ' . html_writer::link($profileurl, fullname($coordinator));
            $out .= html_writer::tag('li', $coordlink);
        }
        $out = html_writer::nonempty_tag('ul', $out);

        return html_writer::tag('div', $out, array('class' => 'block_mbsnewcourse_coordinators'));
    }


    /** render a short list of all requests, which can be approved from this user */
    public function render_request_list() {

        if (!$courserequests = \block_mbsnewcourse\local\mbs_course_request::get_course_requests()) {
            return '';
        }

        $l = '';

        foreach ($courserequests as $courserequest) {
            $link = \html_writer::link($courserequest->viewurl, $courserequest->name);
            $l .= html_writer::tag('li', $link);
        }

        $l = html_writer::tag('ul', $l);
       
        $o = html_writer::tag('div', get_string('coursespending', 'block_mbsnewcourse').':');
        $o .= html_writer::tag('div' ,'', array('class' => 'clearfix')); 
        $o .= $l;
        $o .= html_writer::tag('div' ,'', array('class' => 'clearfix')); 
        
        return $o;

    }

    /**
     * Render a list of the course requests for this school - heavily based on course/pending.php
     * @return string - html snippet with list of courses
     */
    public function render_requests($pending, $schoolcat) {
        global $OUTPUT, $CFG, $PAGE;

        require_once($CFG->dirroot . '/lib/coursecatlib.php');

        $out = '';

        // SYNERGY LEARNING - restrict list to requests within the current school.
        if (empty($pending)) {

            $out .= $OUTPUT->heading(get_string('nopendingcourses'));
        } else {

            $out .= $OUTPUT->heading(get_string('coursespending', 'block_mbsnewcourse'));

            // Build a table of all the requests.
            $table = new \html_table();
            $table->attributes['class'] = 'pendingcourserequests generaltable';
            $table->align = array('center', 'center', 'center', 'center', 'center', 'center');
            $table->head = array(get_string('shortnamecourse'), get_string('fullnamecourse'), get_string('requestedby'),
                get_string('summary'), get_string('category'), get_string('requestreason'), get_string('action'));

            $collision = false;
            foreach ($pending as $course) {

                $course = new \course_request($course);

                // Retreiving category name.
                // If the category was not set (can happen after upgrade) or if the user does not have the capability
                // to change the category, we fallback on the default one.
                // Else, the category proposed is fetched, but we fallback on the default one if we can't find it.
                // It is just a matter of displaying the right information because the logic when approving the category
                // proceeds the same way. The system context level is used as moodle/site:approvecourse uses it.
                // SYNERGY LEARNING - check for 'changecategory' capability at the category level, not site level.
                if (empty($course->category) || (!$category = \coursecat::get($course->category, IGNORE_MISSING))) {
                    $category = \coursecat::get($CFG->defaultrequestcategory);
                }

                $row = array();

                // Check here for shortname collisions and warn about them.
                if ($course->check_shortname_collision()) {
                    $collision = true;
                    $row[] = \html_writer::div(format_string($course->shortname), array('class' => 'warning'));
                } else {
                    $row[] = format_string($course->shortname);
                }

                $row[] = format_string($course->fullname);
                $row[] = fullname($course->get_requester());
                $row[] = $course->summary;
                $row[] = format_string($category->name);
                $row[] = format_string($course->reason);
                $row[] = $OUTPUT->single_button(new \moodle_url($PAGE->url, array('approve' => $course->id, 'sesskey' => sesskey())), get_string('approve'), 'get') .
                        $OUTPUT->single_button(new \moodle_url($PAGE->url, array('reject' => $course->id)), get_string('rejectdots'), 'get');

                // Add the row to the table.
                $table->data[] = $row;
            }

            // Display the table.
            $out .= \html_writer::table($table);

            // Message about name collisions, if necessary.
            if (!empty($collision)) {
                $out .= get_string('shortnamecollisionwarning');
            }
        }

        // Button to leave the page.
        $backurl = new \moodle_url('/course/index.php', array('categoryid' => $schoolcat->id));
        $out .= $OUTPUT->single_button($backurl, get_string('backschool', 'block_mbsnewcourse'));

        return $out;
    }

}