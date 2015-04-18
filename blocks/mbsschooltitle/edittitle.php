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
 * Versioninformation of mbsschooltitle
 *
 * @package   block_mbsschooltitle
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/mbsschooltitle/edittitle_form.php');

$categoryid = required_param('categoryid', PARAM_INT);
$redirecturl = optional_param('redirecturl', '', PARAM_RAW);

if (!$category = $DB->get_record('course_categories', array('id' => $categoryid))) {
    print_error('invalidcategoryid');
}

require_login();

$catcontext = context_coursecat::instance($categoryid);
require_capability('block/mbsschooltitle:edittitle', $catcontext);

$pageurl = new moodle_url('/blocks/mbsschooltitle/edittitle.php', array('id' => $categoryid, 'redirecturl' => $redirecturl));
$PAGE->set_url($pageurl);

$PAGE->set_context($catcontext);
$PAGE->set_heading($category->name . ": " . get_string('edittitle', 'block_mbsschooltitle'));
$PAGE->set_pagelayout('admin');

$titledata = $DB->get_record('block_mbsschooltitle', array('categoryid' => $categoryid));

if (empty($redirecturl)) {
    
    $url = new moodle_url('/course/index.php', array('categoryid' => $categoryid));
    $redirecturl = $url->out();
    
} else {
    $redirecturl = clean_param(base64_decode($redirecturl), PARAM_URL);
}

$edittitleform = new edittitle_form($pageurl, array('categoryid' => $categoryid, 'titledata' => $titledata, 'redirecturl' => $redirecturl));

if ($data = $edittitleform->get_data()) {
    
    if ($titledata) {
        
        $titledata->headline = $data->headline;
        $titledata->timemodified = time();
        $DB->update_record('block_mbsschooltitle', $titledata);
        
    } else {
        $data->timemodified = time();
        $DB->insert_record('block_mbsschooltitle', $data);
    }
    
    \block_mbsschooltitle\local\imagehelper::update_picture($edittitleform, $data, $categoryid);
    redirect($redirecturl);
}

if ($edittitleform->is_cancelled()) {

    redirect($redirecturl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('edittitle', 'block_mbsschooltitle'));

$edittitleform->display();
echo $OUTPUT->footer();