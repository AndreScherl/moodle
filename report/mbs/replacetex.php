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
 * report texed tables
 *
 * @package    report
 * @subpackage mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagern@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');

// Check access.
require_login();

// Check capability.
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$baseurl = new moodle_url('/report/mbs/replacetex.php');

$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);

$tablenames = optional_param_array('table', '', PARAM_TEXT);

$result = array();
if (!empty($tablenames)) {
    $result = \report_mbs\local\reporttex::replace_tex(array_keys($tablenames));
}

echo $OUTPUT->header();

echo $OUTPUT->footer();
