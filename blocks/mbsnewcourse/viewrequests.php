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
 * main class of block_mbsnewcourse
 *
 * @package   block_mbsnewcourse
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $CFG, $PAGE, $OUTPUT;

$schoolid = required_param('id', PARAM_INT);
$schoolcat = $DB->get_record('course_categories', array('id' => $schoolid));

$pageurl = new moodle_url('/blocks/mbsnewcourse/viewrequests.php', array('id' => $schoolcat->id));
$PAGE->set_url($pageurl);

require_login();

$context = context_coursecat::instance($schoolcat->id);
$PAGE->set_context($context);

require_capability('moodle/site:approvecourse', $context);

$strtitle = format_string($schoolcat->name);
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);
$PAGE->set_pagelayout('admin');

// ... add to breadcrumb.
$schoolurl = new moodle_url('/course/index.php', array('id' => $schoolcat->id));
$PAGE->navbar->add($strtitle, $schoolurl);

$PAGE->navbar->add(get_string('courserequests', 'block_mbsnewcourse'), $PAGE->url);

// ...check possible actions for requests.
$approve = optional_param('approve', 0, PARAM_INT);

// Process approval of a course.
if (!empty($approve) and confirm_sesskey()) {

    // Load the request.
    $course = new \block_mbsnewcourse\local\mbs_course_request($approve);

    if ($course->category != $schoolcat->id) {
        $select = 'id = :id AND ' . $DB->sql_like('path', ':path');
        $params = array(
            'id' => $course->category,
            'path' => "{$schoolcat->path}/%"
        );
        if (!$DB->record_exists_select('course_categories', $select, $params, '*', MUST_EXIST)) {
            print_error('categorynotinschool', 'block_mbsnewcourse');
        }
    }
    $courseid = $course->approve();

    if ($courseid !== false) {

        // ...redirect to edit_form, if $USER has the capability to update course.
        if (has_capability('moodle/course:update', context_course::instance($courseid))) {

            $redir = new moodle_url('/course/edit.php', array("id" => $courseid));
            redirect($redir);
        } else {

            $redir = new moodle_url('/blocks/mbsnewcourse/viewrequests.php', array('id' => $schoolcat->id));
            redirect($redir, get_string('courseapproved', 'block_mbsnewcourse'), 5);
        }
    } else {
        print_error('courseapprovedfailed');
    }
}

$reject = optional_param('reject', 0, PARAM_INT);

// Process rejection of a course.
if (!empty($reject)) {

    require_once($CFG->dirroot . '/course/request_form.php');
    require_once($CFG->dirroot . '/course/lib.php');

    // Load the request.
    $course = new course_request($reject);

    // Prepare the form.
    $rejectform = new reject_request_form($PAGE->url);
    $default = new stdClass();
    $default->reject = $course->id;
    $rejectform->set_data($default);

    // Standard form processing if statement.
    if ($rejectform->is_cancelled()) {
        redirect($PAGE->url);
    } else if ($data = $rejectform->get_data()) {

        // Reject the request.
        $course->reject($data->rejectnotice);

        // Redirect back to the course listing.
        redirect($PAGE->url, get_string('courserejected'));
    }

    // Display the form for giving a reason for rejecting the request.
    echo $OUTPUT->header();
    $rejectform->display();
    echo $OUTPUT->footer();
    die();
}

$pending = \block_mbsnewcourse\local\mbs_course_request::get_requests($schoolcat);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('block_mbsnewcourse');
echo $renderer->render_requests($pending, $schoolcat);
echo $OUTPUT->footer();
