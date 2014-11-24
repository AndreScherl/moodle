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
 * Code to allow searching for a particular school
 *
 * @package   block_meineschulen
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
global $DB, $CFG, $PAGE, $OUTPUT;
require_once($CFG->dirroot.'/blocks/meineschulen/lib.php');

$url = new moodle_url('/blocks/meineschulen/search.php');
$PAGE->set_url($url);
require_login();

$context = context_system::instance();
$PAGE->set_context($context);

$strtitle = get_string('search');
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add($strtitle, $PAGE->url);

echo $OUTPUT->header();
echo meineschulen::output_school_search();
echo $OUTPUT->footer();
