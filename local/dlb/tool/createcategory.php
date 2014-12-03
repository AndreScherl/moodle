<?php

require_once('../../../config.php');
require_once($CFG->dirroot . '/lib/coursecatlib.php');

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



