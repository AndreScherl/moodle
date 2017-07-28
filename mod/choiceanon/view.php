<?php

    require_once("../../config.php");
    require_once("lib.php");
    require_once($CFG->libdir . '/completionlib.php');

    $id         = required_param('id', PARAM_INT);                 // Course Module ID
    $action     = optional_param('action', '', PARAM_ALPHA);
    $attemptids = optional_param_array('attemptid', array(), PARAM_INT); // array of attempt ids for delete action

    $url = new moodle_url('/mod/choiceanon/view.php', array('id'=>$id));
    if ($action !== '') {
        $url->param('action', $action);
    }
    $PAGE->set_url($url);

    if (! $cm = get_coursemodule_from_id('choiceanon', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }

    require_course_login($course, false, $cm);

    if (!$choiceanon = choiceanon_get_choiceanon($cm->instance)) {
        print_error('invalidcoursemodule');
    }

    $strchoiceanon = get_string('modulename', 'choiceanon');
    $strchoiceanons = get_string('modulenameplural', 'choiceanon');

    $context = context_module::instance($cm->id);

    if ($action == 'delchoiceanon' and confirm_sesskey() and is_enrolled($context, NULL, 'mod/choiceanon:choose') and $choiceanon->allowupdate) {
        if ($answer = $DB->get_record('choiceanon_answers', array('choiceanonid' => $choiceanon->id, 'userid' => $USER->id))) {
            $DB->delete_records('choiceanon_answers', array('id' => $answer->id));

            // Update completion state
            $completion = new completion_info($course);
            if ($completion->is_enabled($cm) && $choiceanon->completionsubmit) {
                $completion->update_state($cm, COMPLETION_INCOMPLETE);
            }
        }
    }

    $PAGE->set_title($choiceanon->name);
    $PAGE->set_heading($course->fullname);

    // Mark viewed by user (if required)
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

/// Submit any new data if there is any
    if (data_submitted() && is_enrolled($context, NULL, 'mod/choiceanon:choose') && confirm_sesskey()) {
        $timenow = time();
        if (has_capability('mod/choiceanon:deleteresponses', $context)) {
            if ($action == 'delete') { //some responses need to be deleted
                choiceanon_delete_responses($attemptids, $choiceanon, $cm, $course); //delete responses.
                redirect("view.php?id=$cm->id");
            }
        }
        $answer = optional_param('answer', '', PARAM_INT);

        if (empty($answer)) {
            redirect("view.php?id=$cm->id", get_string('mustchooseone', 'choiceanon'));
        } else {
            choiceanon_user_submit_response($answer, $choiceanon, $USER->id, $course, $cm);
        }
        echo $OUTPUT->header();
        echo $OUTPUT->heading($choiceanon->name, 2, null);
        echo $OUTPUT->notification(get_string('choiceanonsaved', 'choiceanon'),'notifysuccess');
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading($choiceanon->name, 2, null);
    }


/// Display the choiceanon and possibly results
    $eventdata = array();
    $eventdata['objectid'] = $choiceanon->id;
    $eventdata['context'] = $context;

    $event = \mod_choiceanon\event\course_module_viewed::create($eventdata);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->trigger();

    /// Check to see if groups are being used in this choiceanon
    $groupmode = groups_get_activity_groupmode($cm);

    if ($groupmode) {
        groups_get_activity_group($cm, true);
        groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/choiceanon/view.php?id='.$id);
    }
    $allresponses = choiceanon_get_response_data($choiceanon, $cm, $groupmode);   // Big function, approx 6 SQL calls per user


    if (has_capability('mod/choiceanon:readresponses', $context)) {
        choiceanon_show_reportlink($allresponses, $cm);
    }

    echo '<div class="clearer"></div>';

    if ($choiceanon->intro) {
        echo $OUTPUT->box(format_module_intro('choiceanon', $choiceanon, $cm->id), 'generalbox', 'intro');
    }

    $timenow = time();
    $current = false;  // Initialise for later
    //if user has already made a selection, and they are not allowed to update it or if choiceanon is not open, show their selected answer.
    if (isloggedin() && ($current = $DB->get_record('choiceanon_answers', array('choiceanonid' => $choiceanon->id, 'userid' => $USER->id))) &&
        (empty($choiceanon->allowupdate) || ($timenow > $choiceanon->timeclose)) ) {
        //echo $OUTPUT->box(get_string("yourselection", "choiceanon", userdate($choiceanon->timeopen)).": ".format_string(choiceanon_get_option_text($choiceanon, $current->optionid)), 'generalbox', 'yourselection');
    }

/// Print the form
    $choiceanonopen = true;
    if ($choiceanon->timeclose !=0) {
        if ($choiceanon->timeopen > $timenow ) {
            echo $OUTPUT->box(get_string("notopenyet", "choiceanon", userdate($choiceanon->timeopen)), "generalbox notopenyet");
            echo $OUTPUT->footer();
            exit;
        } else if ($timenow > $choiceanon->timeclose) {
            echo $OUTPUT->box(get_string("expired", "choiceanon", userdate($choiceanon->timeclose)), "generalbox expired");
            $choiceanonopen = false;
        }
    }

    if ( (!$current or $choiceanon->allowupdate) and $choiceanonopen and is_enrolled($context, NULL, 'mod/choiceanon:choose')) {
    // They haven't made their choiceanon yet or updates allowed and choiceanon is open

        $options = choiceanon_prepare_options($choiceanon, $USER, $cm, $allresponses);
        $renderer = $PAGE->get_renderer('mod_choiceanon');
        echo $renderer->display_options($options, $cm->id, $choiceanon->display);
        $choiceanonformshown = true;
    } else {
        $choiceanonformshown = false;
    }

    if (!$choiceanonformshown) {
        $sitecontext = context_system::instance();

        if (isguestuser()) {
            // Guest account
            echo $OUTPUT->confirm(get_string('noguestchoose', 'choiceanon').'<br /><br />'.get_string('liketologin'),
                         get_login_url(), new moodle_url('/course/view.php', array('id'=>$course->id)));
        } else if (!is_enrolled($context)) {
            // Only people enrolled can make a choiceanon
            $SESSION->wantsurl = qualified_me();
            $SESSION->enrolcancel = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';

            $coursecontext = context_course::instance($course->id);
            $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));

            echo $OUTPUT->box_start('generalbox', 'notice');
            echo '<p align="center">'. get_string('notenrolledchoose', 'choiceanon') .'</p>';
            echo $OUTPUT->container_start('continuebutton');
            echo $OUTPUT->single_button(new moodle_url('/enrol/index.php?', array('id'=>$course->id)), get_string('enrolme', 'core_enrol', $courseshortname));
            echo $OUTPUT->container_end();
            echo $OUTPUT->box_end();

        }
    }

    // print the results at the bottom of the screen
    if ( $choiceanon->showresults == CHOICEANON_SHOWRESULTS_ALWAYS or
        ($choiceanon->showresults == CHOICEANON_SHOWRESULTS_AFTER_ANSWER and $current) or
        ($choiceanon->showresults == CHOICEANON_SHOWRESULTS_AFTER_CLOSE and !$choiceanonopen)) {

        if (!empty($choiceanon->showunanswered)) {
            $choiceanon->option[0] = get_string('notanswered', 'choiceanon');
            $choiceanon->maxanswers[0] = 0;
        }
        $results = prepare_choiceanon_show_results($choiceanon, $course, $cm, $allresponses);
        $renderer = $PAGE->get_renderer('mod_choiceanon');
        echo $renderer->display_result($results);

    } else if (!$choiceanonformshown) {
        echo $OUTPUT->box(get_string('noresultsviewable', 'choiceanon'));
    }

    echo $OUTPUT->footer();
