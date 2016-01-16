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
 * Versioninformation of mbsnews
 *
 * @package   block_mbsnews
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');

$pageurl = new moodle_url('/blocks/mbsnews/editjob.php', array());
$PAGE->set_url($pageurl);

require_login();

$newsid = optional_param('id', 0, PARAM_INT);

// Verify the job to be edited.
if (!empty($newsid)) {
    
   $news = \block_mbsnews\local\newshelper::load_job_instance($newsid);
}

$context = context_system::instance();
require_capability('block/mbsnews:sendnews', $context);

$PAGE->set_context($context);
$PAGE->set_heading(get_string('sendnews', 'block_mbsnews'));
$PAGE->set_pagelayout('admin');

//$news = file_prepare_standard_editor($news, 'message', array(), null, 'news', 'message', null);

$editjobform = new \block_mbsnews\local\editjob_form($pageurl, array('id' => $newsid));
if ($newsid > 0) {
    $editjobform->set_data($news);
}

if ($editjobform->is_cancelled()) {
    $url = new moodle_url('/blocks/mbsnews/listjobs.php');
    redirect($url);
}

if ($data = $editjobform->get_data()) {
    
    $result = \block_mbsnews\local\newshelper::save_notification_job($data);
    
    if ($result['error'] == 0) {
        $url = new moodle_url('/blocks/mbsnews/listjobs.php');
        redirect($url, $result['message']);
    }  
}
   
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('sendnews', 'block_mbsnews'));
$editjobform->display();
echo $OUTPUT->footer();