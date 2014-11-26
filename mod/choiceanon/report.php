<?php

    require_once("../../config.php");
    require_once("lib.php");

    $id         = required_param('id', PARAM_INT);   //moduleid
    $format     = optional_param('format', CHOICEANON_PUBLISH_NAMES, PARAM_INT);
    $download   = optional_param('download', '', PARAM_ALPHA);
    $action     = optional_param('action', '', PARAM_ALPHA);
    $attemptids = optional_param_array('attemptid', array(), PARAM_INT); //get array of responses to delete.

    $url = new moodle_url('/mod/choiceanon/report.php', array('id'=>$id));
    if ($format !== CHOICEANON_PUBLISH_NAMES) {
        $url->param('format', $format);
    }
    if ($download !== '') {
        $url->param('download', $download);
    }
    if ($action !== '') {
        $url->param('action', $action);
    }
    $PAGE->set_url($url);

    if (! $cm = get_coursemodule_from_id('choiceanon', $id)) {
        print_error("invalidcoursemodule");
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error("coursemisconf");
    }

    require_login($course, false, $cm);

    $context = context_module::instance($cm->id);

    require_capability('mod/choiceanon:readresponses', $context);

    if (!$choiceanon = choiceanon_get_choiceanon($cm->instance)) {
        print_error('invalidcoursemodule');
    }

    $strchoiceanon = get_string("modulename", "choiceanon");
    $strchoiceanons = get_string("modulenameplural", "choiceanon");
    $strresponses = get_string("responses", "choiceanon");

    $eventdata = array();
    $eventdata['objectid'] = $choiceanon->id;
    $eventdata['context'] = $context;
    $eventdata['courseid'] = $course->id;
    $eventdata['other']['content'] = 'choicereportcontentviewed';

    $event = \mod_choiceanon\event\report_viewed::create($eventdata);
    $event->trigger();

    if (data_submitted() && $action == 'delete' && has_capability('mod/choiceanon:deleteresponses',$context) && confirm_sesskey()) {
        choiceanon_delete_responses($attemptids, $choiceanon, $cm, $course); //delete responses.
        redirect("report.php?id=$cm->id");
    }

    if (!$download) {
        $PAGE->navbar->add($strresponses);
        $PAGE->set_title(format_string($choiceanon->name).": $strresponses");
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->heading($choiceanon->name, 2, null);
        /// Check to see if groups are being used in this choice
        $groupmode = groups_get_activity_groupmode($cm);
        if ($groupmode) {
            groups_get_activity_group($cm, true);
            groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/choiceanon/report.php?id='.$id);
        }
    } else {
        $groupmode = groups_get_activity_groupmode($cm);
    }
    $users = choiceanon_get_response_data($choiceanon, $cm, $groupmode);

    $results = prepare_choiceanon_show_results($choiceanon, $course, $cm, $users);
    $renderer = $PAGE->get_renderer('mod_choiceanon');
    echo $renderer->display_result($results, has_capability('mod/choiceanon:readresponses', $context));

    echo $OUTPUT->footer();

