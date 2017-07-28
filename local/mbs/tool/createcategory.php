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
 * Test-tools to generate data.
 *
 * @package   local_mbs
 * @copyright 2015 Andreas Wagner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot . '/lib/coursecatlib.php');


require_login();
require_capability('moodle/site:config', context_system::instance());

set_time_limit(0);


//purge_all_caches();

/* $coursecattreecache = cache::make('core', 'coursecattree');
  $rv = $coursecattreecache->get(0);
  print_r($rv);

  die;

  $start = microtime(true);
  $cats = coursecat::get(0)->get_children();
  print_r(microtime(true));
  echo("<p>".(microtime(true) - $start)."</p>");

  print_r(array_keys($cats)); */
//fix_course_sortorder();



$createdids = array_keys($DB->get_records('course_categories', array('parent' => 0)));

for ($i = 1; $i < 1000; $i++) {
    $data = new stdClass();
    $data->name = 'category-childs-' . $i;
    shuffle($createdids);
    $data->parent = $createdids[0];
    //$data->parent = 13;
    $category = coursecat::create($data);
    $createdids[] = $category->id;
}



