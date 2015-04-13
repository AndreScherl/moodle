<?php

// This page prints a form to edit captions and titles for the images in the slideshow folder.
global $DB, $PAGE, $OUTPUT;
require_once("../../config.php");
require_once("lib.php");

$id = optional_param('id', 0, PARAM_INT);
$a = optional_param('a', 0, PARAM_INT);
$imgnum = optional_param('img_num', 0, PARAM_INT);

if ($a) { // Two ways to specify the module.
    $slideshow = $DB->get_record('slideshow', array('id' => $a), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('slideshow', $slideshow->id, $slideshow->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('slideshow', $id, 0, false, MUST_EXIST);
    $slideshow = $DB->get_record('slideshow', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);

$form = data_submitted();
if ($form) {
    if (isset($form->cancel)) {
        redirect("view.php?id=$id");
        die;
    }
    slideshow_write_captions($form, $slideshow);

    $params = array(
        'context' => $context,
        'objectid' => $slideshow->id
    );
    $event = \mod_slideshow\event\captions_updated::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('slideshow', $slideshow);
    $event->trigger();

    redirect("view.php?id=$id");
}

// Print header.
$PAGE->set_url('/mod/slideshow/captions.php', array('id' => $cm->id));
$PAGE->navbar->add($slideshow->name);
$PAGE->set_heading(format_string($slideshow->name));
$PAGE->set_title(format_string($slideshow->name));

echo $OUTPUT->header();
$coursecontext = context_course::instance($course->id);
$context = context_module::instance($cm->id);
// Print the main part of the page.
$imgcount = 0;
if (has_capability('moodle/course:update', $coursecontext)) {
    $conditions = array('contextid' => $context->id, 'component' => 'mod_slideshow', 'filearea' => 'content', 'itemid' => 0);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_slideshow', 'content', 0, 'itemid, filepath, filename', false);
    $captions = array();
    foreach ($files as $file) {
        $filename = $file->get_filename();
        if (preg_match("|\.jpe?g$|", $filename) || preg_match("|\.gif$|", $filename) || preg_match("|\.png$|", $filename)) {
            if (preg_match("|^thumb_|", $filename)) {
                continue;
            }
            if (preg_match("|^resized_|", $filename)) {
                if ($slideshow->keeporiginals) {
                    continue;
                } else {
                    $filename = str_replace('|resized_|', '', $filename);
                }
            }
            $image = slideshow_filetidy($filename);
            $captions[$image] = slideshow_caption_array($slideshow->id, $image);
        }
    }
    sort($captions);
    require_once('edit_form.php');
    echo $OUTPUT->heading(get_string('edit_captions', 'slideshow', ''));
    echo get_string('captiontext', 'slideshow', '');
    $htmledit = isset($slideshow->htmlcaptions) ? $slideshow->htmlcaptions : 0;
    $mform = new mod_slideshow_edit_form('captions.php', array(
        'captions' => $captions, 'htmledit' => $htmledit, 'context' => $context
    ));
    $mform->display();
} else {
    echo get_string('noauth', 'slideshow', '');
}
// Finish the page.
echo $OUTPUT->footer($course);

