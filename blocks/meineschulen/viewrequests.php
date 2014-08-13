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
 * Veiw the course requests for a given school.
 *
 * @package   block_meineschulen
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require_once(dirname(__FILE__).'/../../config.php');
global $DB, $CFG, $PAGE, $OUTPUT;
require_once($CFG->dirroot.'/blocks/meineschulen/lib.php');

$schoolid = required_param('id', PARAM_INT);
$schoolcat = $DB->get_record('course_categories', array('id' => $schoolid, 'depth' => MEINEKURSE_SCHOOL_CAT_DEPTH),
                             '*', MUST_EXIST);

$url = new moodle_url('/blocks/meineschulen/viewrequests.php', array('id' => $schoolcat->id));
$PAGE->set_url($url);
require_login();

$context = context_coursecat::instance($schoolcat->id);
$PAGE->set_context($context);

require_capability('moodle/site:approvecourse', $context);

$strtitle = format_string($schoolcat->name);
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);
$PAGE->set_pagelayout('coursecategory');
$searchurl = new moodle_url('/blocks/meineschulen/search.php');
$PAGE->navbar->add(get_string('search'), $searchurl);
$schoolurl = new moodle_url('/blocks/meineschulen/viewschool.php', array('id' => $schoolcat->id));
$PAGE->navbar->add($strtitle, $schoolurl);
$PAGE->navbar->add(get_string('courserequests', 'block_meineschulen'), $PAGE->url);

$meineschulen = new meineschulen($schoolcat);

$meineschulen->process_requests();

echo $OUTPUT->header();
echo $meineschulen->output_requests();
echo $OUTPUT->footer();
