<?php

// This page lists all the instances of slideshow in a particular course.
require_once("../../config.php");
global $CFG, $PAGE, $OUTPUT, $DB;
require_once($CFG->dirroot.'/mod/slideshow/lib.php');

$id = required_param('id', PARAM_INT); // Course.

$course = $DB->get_record("course", array("id" => $id), '*', MUST_EXIST);

require_login($course->id);

$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_slideshow\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

// Get all required strings.

$strslideshows = get_string("modulenameplural", "slideshow");
$strslideshow = get_string("modulename", "slideshow");

$PAGE->set_url('/mod/slideshow/index.php', array('id' => $id));
$PAGE->navbar->add($strslideshows);

// Print the header.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('slideshowsfound', 'slideshow', $course->shortname));

// Get all the appropriate data.
$slideshows = get_all_instances_in_course("slideshow", $course);

// Print the list of instances (your module will probably extend this).
$timenow = time();
$strname = get_string("name");
$strweek = get_string("week");
$strtopic = get_string("topic");
$table = new html_table();

if ($course->format == "weeks") {
    $table->head = array($strweek, $strname);
    $table->align = array("CENTER", "LEFT");
} else if ($course->format == "topics") {
    $table->head = array($strtopic, $strname);
    $table->align = array("CENTER", "LEFT", "LEFT", "LEFT");
} else {
    $table->head = array($strname);
    $table->align = array("LEFT", "LEFT", "LEFT");
}

foreach ($slideshows as $slideshow) {
    if (!$slideshow->visible) {
        // Show dimmed if the mod is hidden.
        $link = '<a class="dimmed" href="view.php?id='.$slideshow->coursemodule.'">'.$slideshow->name.'</a>';
    } else {
        // Show normal if the mod is visible.
        $link = '<a href="view.php?id='.$slideshow->coursemodule.'">'.$slideshow->name.'</a>';
    }

    if ($course->format == "weeks" or $course->format == "topics") {
        $table->data[] = array($slideshow->section, $link);
    } else {
        $table->data[] = array($link);
    }
}

echo "<br />";

echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer($course);
