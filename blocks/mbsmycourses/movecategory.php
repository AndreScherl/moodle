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
 * mebis my courses block (based on course overview block)
 *
 * @package    block_mbsmycourses
 * @copyright  2015 Andreas Wagner <andreas.wagener@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

require_sesskey();
require_login();

$categorytomove = required_param('categoryid', PARAM_INT);
$moveto = required_param('moveto', PARAM_INT);

$sortedcourses = mbsmycourses::get_sorted_courses_group_by_school('', 0);

$sortedcategorys = array_keys($sortedcourses->groupedcourses);

$currentcategoryindex = array_search($categorytomove, $sortedcategorys);

// If categorytomove is not found or moveto < 0 or > count($sortedcategorys) then throw error.
if ($currentcategoryindex === false) {
    print_error("invalidcategoryid", null, null, $categorytomove);
} else if (($moveto < 0) || ($moveto >= count($sortedcategorys))) {
    print_error("invalidaction");
}

// If current category index is same as destination index then don't do anything.
if ($currentcategoryindex === $moveto) {
    redirect(new moodle_url('/my/index.php'));
}

// Create neworder list for categorys.
$neworder = array();

unset($sortedcategorys[$currentcategoryindex]);
$neworder = array_slice($sortedcategorys, 0, $moveto, true);
$neworder[] = $categorytomove;
$remaningcategorys = array_slice($sortedcategorys, $moveto);
foreach ($remaningcategorys as $categoryid) {
    $neworder[] = $categoryid;
}
mbsmycourses::update_mycategoryorder(array_values($neworder));
redirect(new moodle_url('/my/index.php'));