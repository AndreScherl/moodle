<?php
/*
 #########################################################################
 #                       DLB-Bayern
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 #
 # Copyright 2012 Andreas Wagner. All Rights Reserved.
 # This file may not be redistributed in whole or significant part.
 # Content of this file is Protected By International Copyright Laws.
 #
 # ~~~~~~~~~~~~~~~~~~ THIS CODE IS NOT FREE SOFTWARE ~~~~~~~~~~~~~~~~~~~~
 #
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 # @author Andreas Wagner, DLB	andreas.wagner@alp.dillingen.de
 #########################################################################
*/

require_once("../../config.php");
require_once($CFG->dirroot."/course/lib.php");

$delete   = optional_param('delete',0,PARAM_INT);

if ($delete == 0) {
    print_error('invalidcategoryid');
}

$context = context_coursecat::instance($delete);
require_capability('moodle/category:manage', $context);

$PAGE->set_url('/local/course/deletcategory.php');
$PAGE->set_context($context);

/// Delete a category.
if (!empty($delete) and confirm_sesskey()) {
    if (!$deletecat = $DB->get_record('course_categories', array('id'=>$delete))) {
        print_error('invalidcategoryid');
    }
    $context = get_context_instance(CONTEXT_COURSECAT, $delete);
    require_capability('moodle/category:manage', $context);
    require_capability('moodle/category:manage', get_category_or_system_context($deletecat->parent));

    $returnto = $CFG->defaultrequestcategory;
    if ($deletecat->parent != 0) $returnto = $deletecat->parent;

    $heading = get_string('deletecategory', 'moodle', format_string($deletecat->name, true, array('context' => $context)));

    require_once($CFG->dirroot."/course/delete_category_form.php");

    $mform = new delete_category_form(null, $deletecat);
    $mform->set_data(array('delete'=>$delete));

    if ($mform->is_cancelled()) {
        
        redirect($CFG->wwwroot."/course/category.php?id={$returnto}");

    } else if (!$data= $mform->get_data()) {
        require_once($CFG->libdir . '/questionlib.php');
        echo $OUTPUT->header();
        echo $OUTPUT->heading($heading);
        $mform->display();
        echo $OUTPUT->footer();
        exit();
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading($heading);

    if ($data->fulldelete) {
        $deletedcourses = category_delete_full($deletecat, true);

        foreach($deletedcourses as $course) {
            echo $OUTPUT->notification(get_string('coursedeleted', '', $course->shortname), 'notifysuccess');
        }
        echo $OUTPUT->notification(get_string('coursecategorydeleted', '', format_string($deletecat->name, true, array('context' => $context))), 'notifysuccess');

    } else {
        category_delete_move($deletecat, $data->newparent, true);
    }

    // If we deleted $CFG->defaultrequestcategory, make it point somewhere else.
    if ($delete == $CFG->defaultrequestcategory) {
        set_config('defaultrequestcategory', $DB->get_field('course_categories', 'MIN(id)', array('parent'=>0)));
    }

    echo $OUTPUT->continue_button($CFG->wwwroot."/course/category.php?id={$returnto}");

    echo $OUTPUT->footer();
    die;
}
?>
