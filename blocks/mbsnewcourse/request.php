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
 * block_mbsnewcourse course request page
 * 
 * This page is based on course/request.php page
 * Modifications are mainly made to use mbs_course_request class.
 * 
 * @package    block_mbsnewcourse
 * @copyright  2015 Andreas Wagner, ISB Bayern
 * @license    todo
 */

require_once(dirname(__FILE__) . '/../../config.php');

require_once($CFG->dirroot.'/blocks/mbsnewcourse/mbs_request_form.php');
require_once($CFG->dirroot.'/lib/coursecatlib.php');

// ...shortening the namespace of automatic loaded class.
use block_mbsnewcourse\local\mbs_course_request as mbs_course_request;

$categoryid = optional_param('category', 0, PARAM_INT);

if ($categoryid == 0) {

    if (!$categoryid = \local_mbs\local\schoolcategory::get_users_schoolcatid()) {
        print_error('missinginstitutionid', 'block_newcourse');
    }
}

$category = $DB->get_record('course_categories', array('id' => $categoryid), '*', MUST_EXIST);

$url = new moodle_url('/blocks/mbsnewcourse/request.php', array('category' => $category->id));
$PAGE->set_url($url);

// Where we came from. Used in a number of redirects.
$returnurl = new moodle_url('/course/index.php', array('categoryid' => $category->id));

// Check permissions.
require_login();

if (isguestuser()) {
    print_error('guestsarenotallowed', '', $returnurl);
}

if (empty($CFG->enablecourserequests)) {
    print_error('courserequestdisabled', '', $returnurl);
}

$context = context_coursecat::instance($category->id);
$PAGE->set_context($context);

if (!mbs_course_request::can_request_course($category->id)) {
    print_error('nopermissions',  '', '', 'moodle/course:request');
}

$strtitle = get_string('courserequest');
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);

$requestform = new mbs_course_request_form(null, array('requestcategory' => $category));

$data = mbs_course_request::prepare();

$requestform->set_data($data);

// Standard form processing if statement.
if ($requestform->is_cancelled()) {

    redirect($returnurl);

} else if ($data = $requestform->get_data()) {

    mbs_course_request::create($data);

    // ...and redirect back to the course listing.
    notice(get_string('courserequestsuccess'), $returnurl);
}

$PAGE->navbar->add($strtitle);

echo $OUTPUT->header();
echo $OUTPUT->heading($strtitle);
$requestform->display();
echo $OUTPUT->footer();