<?php

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id',PARAM_INT);   // course

    $PAGE->set_url('/mod/choiceanon/index.php', array('id'=>$id));

    if (!$course = $DB->get_record('course', array('id'=>$id))) {
        print_error('invalidcourseid');
    }

    require_course_login($course);
    $PAGE->set_pagelayout('incourse');

    $eventdata = array('context' => context_course::instance($id));
    $event = \mod_choiceanon\event\course_module_instance_list_viewed::create($eventdata);
    $event->add_record_snapshot('course', $course);
    $event->trigger();

    $strchoiceanon = get_string("modulename", "choiceanon");
    $strchoiceanons = get_string("modulenameplural", "choiceanon");
    $PAGE->set_title($strchoiceanons);
    $PAGE->set_heading($course->fullname);
    $PAGE->navbar->add($strchoiceanons);
    echo $OUTPUT->header();

    if (! $choiceanons = get_all_instances_in_course("choiceanon", $course)) {
        notice(get_string('thereareno', 'moodle', $strchoiceanons), "../../course/view.php?id=$course->id");
    }

    $usesections = course_format_uses_sections($course->format);

    $sql = "SELECT cha.*
              FROM {choiceanon} ch, {choiceanon_answers} cha
             WHERE cha.choiceanonid = ch.id AND
                   ch.course = ? AND cha.userid = ?";

    $answers = array () ;
    if (isloggedin() and !isguestuser() and $allanswers = $DB->get_records_sql($sql, array($course->id, $USER->id))) {
        foreach ($allanswers as $aa) {
            $answers[$aa->choiceanonid] = $aa;
        }
        unset($allanswers);
    }


    $timenow = time();

    $table = new html_table();

    if ($usesections) {
        $strsectionname = get_string('sectionname', 'format_'.$course->format);
        $table->head  = array ($strsectionname, get_string("question"), get_string("answer"));
        $table->align = array ("center", "left", "left");
    } else {
        $table->head  = array (get_string("question"), get_string("answer"));
        $table->align = array ("left", "left");
    }

    $currentsection = "";

    foreach ($choiceanons as $choiceanon) {
        if (!empty($answers[$choiceanon->id])) {
            $answer = $answers[$choiceanon->id];
        } else {
            $answer = "";
        }
        if (!empty($answer->optionid)) {
            $aa = format_string(choiceanon_get_option_text($choiceanon, $answer->optionid));
        } else {
            $aa = "";
        }
        if ($usesections) {
            $printsection = "";
            if ($choiceanon->section !== $currentsection) {
                if ($choiceanon->section) {
                    $printsection = get_section_name($course, $choiceanon->section);
                }
                if ($currentsection !== "") {
                    $table->data[] = 'hr';
                }
                $currentsection = $choiceanon->section;
            }
        }

        //Calculate the href
        if (!$choiceanon->visible) {
            //Show dimmed if the mod is hidden
            $tt_href = "<a class=\"dimmed\" href=\"view.php?id=$choiceanon->coursemodule\">".format_string($choiceanon->name,true)."</a>";
        } else {
            //Show normal if the mod is visible
            $tt_href = "<a href=\"view.php?id=$choiceanon->coursemodule\">".format_string($choiceanon->name,true)."</a>";
        }
        if ($usesections) {
            $table->data[] = array ($printsection, $tt_href, $aa);
        } else {
            $table->data[] = array ($tt_href, $aa);
        }
    }
    echo "<br />";
    echo html_writer::table($table);

    echo $OUTPUT->footer();


