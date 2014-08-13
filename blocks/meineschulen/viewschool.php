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
 * View info about a particular school
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

//awag: need to set $PAGE->category for displaying the customized header
$PAGE->set_category_by_id($schoolcat->id);

$url = new moodle_url('/blocks/meineschulen/viewschool.php', array('id' => $schoolcat->id));
$PAGE->set_url($url);
require_login();

$context = context_coursecat::instance($schoolcat->id);

$strtitle = format_string($schoolcat->name);
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);
$PAGE->set_pagelayout('coursecategory');
$searchurl = new moodle_url('/blocks/meineschulen/search.php');
$PAGE->navbar->add(get_string('search'), $searchurl);
$PAGE->navbar->add($strtitle, $PAGE->url);

$meineschulen = new meineschulen($schoolcat);

echo $OUTPUT->header();
echo $meineschulen->output_info();
echo $OUTPUT->footer();
