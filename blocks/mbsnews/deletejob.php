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
 * Delete a job
 *
 * @package   block_mbsnews
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');

$jobid = optional_param('id', 0, PARAM_INT);

// Verify the job to be edited.
if (!empty($jobid)) {
    
   $job = \block_mbsnews\local\newshelper::load_job_instance($jobid);
}

$pageurl = new moodle_url('/blocks/mbsnews/deletejob.php', array('id' => $jobid));
$PAGE->set_url($pageurl);

require_login();

$context = context_system::instance();
require_capability('block/mbsnews:sendnews', $context);

$redirecturl = new moodle_url('/block/mbsnews/listjobs.php');

if ($DB->delete_records('block_mbsnews_job', array('id' => $job->id))) {
    redirect($redirecturl, get_string('jobdeleted', 'block_mbsnews'));
} else {
    redirect($redirecturl, get_string('errorjobdeleted', 'block_mbsnews'));
}