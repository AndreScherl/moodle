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
 * Allows a user to request a course be created for them.
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package course
 */

require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB, $PAGE, $OUTPUT;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot.'/blocks/meineschulen/request_form.php');
require_once($CFG->dirroot.'/blocks/meineschulen/lib.php');

$categoryid = required_param('category', PARAM_INT);
$category = $DB->get_record('course_categories', array('id' => $categoryid), '*', MUST_EXIST);

$url = new moodle_url('/blocks/meineschulen/request.php', array('category' => $category->id));
$PAGE->set_url($url);

/// Where we came from. Used in a number of redirects.
$returnurl = new moodle_url('/blocks/meineschulen/viewschool.php', array('id' => $category->id));

/// Check permissions.
require_login();
if (isguestuser()) {
    print_error('guestsarenotallowed', '', $returnurl);
}
if (empty($CFG->enablecourserequests)) {
    print_error('courserequestdisabled', '', $returnurl);
}
$context = context_coursecat::instance($category->id);
$PAGE->set_context($context);
if (!meineschulen::can_request_course()) {
    print_error('nopermissions',  '', '', 'moodle/course:request');
}

/// Set up the form.
$data = course_request::prepare();
$data->category = $category->id;
$requestform = new meineschulen_course_request_form(null, compact('editoroptions'));
$requestform->set_data($data);

$strtitle = get_string('courserequest');
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);

/// Standard form processing if statement.
if ($requestform->is_cancelled()){
    redirect($returnurl);

} else if ($data = $requestform->get_data()) {
    $request = course_request::create($data);

    // and redirect back to the course listing.
    notice(get_string('courserequestsuccess'), $returnurl);
}

$PAGE->navbar->add($strtitle);
echo $OUTPUT->header();
echo $OUTPUT->heading($strtitle);
// Show the request form.
$requestform->display();
echo $OUTPUT->footer();
