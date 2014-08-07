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
 * @package   mod_choiceanon
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @global int $CHOICEANON_COLUMN_HEIGHT */
global $CHOICEANON_COLUMN_HEIGHT;
$CHOICEANON_COLUMN_HEIGHT = 300;

/** @global int $CHOICEANON_COLUMN_WIDTH */
global $CHOICEANON_COLUMN_WIDTH;
$CHOICEANON_COLUMN_WIDTH = 300;

define('CHOICEANON_PUBLISH_ANONYMOUS', '0');
define('CHOICEANON_PUBLISH_NAMES',     '1');

define('CHOICEANON_SHOWRESULTS_NOT',          '0');
define('CHOICEANON_SHOWRESULTS_AFTER_ANSWER', '1');
define('CHOICEANON_SHOWRESULTS_AFTER_CLOSE',  '2');
define('CHOICEANON_SHOWRESULTS_ALWAYS',       '3');

define('CHOICEANON_DISPLAY_HORIZONTAL',  '0');
define('CHOICEANON_DISPLAY_VERTICAL',    '1');

/** @global array $CHOICEANON_PUBLISH */
global $CHOICEANON_PUBLISH;
$CHOICEANON_PUBLISH = array (CHOICEANON_PUBLISH_ANONYMOUS  => get_string('publishanonymous', 'choiceanon'),
                         CHOICEANON_PUBLISH_NAMES      => get_string('publishnames', 'choiceanon'));

/** @global array $CHOICEANON_SHOWRESULTS */
global $CHOICEANON_SHOWRESULTS;
$CHOICEANON_SHOWRESULTS = array (CHOICEANON_SHOWRESULTS_NOT          => get_string('publishnot', 'choiceanon'),
                         CHOICEANON_SHOWRESULTS_AFTER_ANSWER => get_string('publishafteranswer', 'choiceanon'),
                         CHOICEANON_SHOWRESULTS_AFTER_CLOSE  => get_string('publishafterclose', 'choiceanon'),
                         CHOICEANON_SHOWRESULTS_ALWAYS       => get_string('publishalways', 'choiceanon'));

/** @global array $CHOICEANON_DISPLAY */
global $CHOICEANON_DISPLAY;
$CHOICEANON_DISPLAY = array (CHOICEANON_DISPLAY_HORIZONTAL   => get_string('displayhorizontal', 'choiceanon'),
                         CHOICEANON_DISPLAY_VERTICAL     => get_string('displayvertical','choiceanon'));

/// Standard functions /////////////////////////////////////////////////////////

/**
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $choiceanon
 * @return object|null
 */
function choiceanon_user_outline($course, $user, $mod, $choiceanon) {
    global $DB;
    if ($answer = $DB->get_record('choiceanon_answers', array('choiceanonid' => $choiceanon->id, 'userid' => $user->id))) {
        $result = new stdClass();
        $result->info = "'".format_string(choiceanon_get_option_text($choiceanon, $answer->optionid))."'";
        $result->time = $answer->timemodified;
        return $result;
    }
    return NULL;
}

/**
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $choiceanon
 * @return string|void
 */
function choiceanon_user_complete($course, $user, $mod, $choiceanon) {
    global $DB;
    if ($answer = $DB->get_record('choiceanon_answers', array("choiceanonid" => $choiceanon->id, "userid" => $user->id))) {
        $result = new stdClass();
        $result->info = "'".format_string(choiceanon_get_option_text($choiceanon, $answer->optionid))."'";
        $result->time = $answer->timemodified;
        echo get_string("answered", "choiceanon").": $result->info. ".get_string("updated", '', userdate($result->time));
    } else {
        print_string("notanswered", "choiceanon");
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $choiceanon
 * @return int
 */
function choiceanon_add_instance($choiceanon) {
    global $DB;

    $choiceanon->timemodified = time();

    if (empty($choiceanon->timerestrict)) {
        $choiceanon->timeopen = 0;
        $choiceanon->timeclose = 0;
    }

    //insert answers
    $choiceanon->id = $DB->insert_record("choiceanon", $choiceanon);
    foreach ($choiceanon->option as $key => $value) {
        $value = trim($value);
        if (isset($value) && $value <> '') {
            $option = new stdClass();
            $option->text = $value;
            $option->choiceanonid = $choiceanon->id;
            if (isset($choiceanon->limit[$key])) {
                $option->maxanswers = $choiceanon->limit[$key];
            }
            $option->timemodified = time();
            $DB->insert_record("choiceanon_options", $option);
        }
    }

    return $choiceanon->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $choiceanon
 * @return bool
 */
function choiceanon_update_instance($choiceanon) {
    global $DB;

    $choiceanon->id = $choiceanon->instance;
    $choiceanon->timemodified = time();


    if (empty($choiceanon->timerestrict)) {
        $choiceanon->timeopen = 0;
        $choiceanon->timeclose = 0;
    }

    //update, delete or insert answers
    foreach ($choiceanon->option as $key => $value) {
        $value = trim($value);
        $option = new stdClass();
        $option->text = $value;
        $option->choiceanonid = $choiceanon->id;
        if (isset($choiceanon->limit[$key])) {
            $option->maxanswers = $choiceanon->limit[$key];
        }
        $option->timemodified = time();
        if (isset($choiceanon->optionid[$key]) && !empty($choiceanon->optionid[$key])){//existing choiceanon record
            $option->id=$choiceanon->optionid[$key];
            if (isset($value) && $value <> '') {
                $DB->update_record("choiceanon_options", $option);
            } else { //empty old option - needs to be deleted.
                $DB->delete_records("choiceanon_options", array("id"=>$option->id));
            }
        } else {
            if (isset($value) && $value <> '') {
                $DB->insert_record("choiceanon_options", $option);
            }
        }
    }

    return $DB->update_record('choiceanon', $choiceanon);

}

/**
 * @global object
 * @param object $choiceanon
 * @param object $user
 * @param object $coursemodule
 * @param array $allresponses
 * @return array
 */
function choiceanon_prepare_options($choiceanon, $user, $coursemodule, $allresponses) {
    global $DB;

    $cdisplay = array('options'=>array());

    $cdisplay['limitanswers'] = true;
    $context = context_module::instance($coursemodule->id);

    foreach ($choiceanon->option as $optionid => $text) {
        if (isset($text)) { //make sure there are no dud entries in the db with blank text values.
            $option = new stdClass;
            $option->attributes = new stdClass;
            $option->attributes->value = $optionid;
            $option->text = $text;
            $option->maxanswers = $choiceanon->maxanswers[$optionid];
            $option->displaylayout = $choiceanon->display;

            if (isset($allresponses[$optionid])) {
                $option->countanswers = count($allresponses[$optionid]);
            } else {
                $option->countanswers = 0;
            }
            if ($DB->record_exists('choiceanon_answers', array('choiceanonid' => $choiceanon->id, 'userid' => $user->id, 'optionid' => $optionid))) {
                $option->attributes->checked = true;
            }
            if ( $choiceanon->limitanswers && ($option->countanswers >= $option->maxanswers) && empty($option->attributes->checked)) {
                $option->attributes->disabled = true;
            }
            $cdisplay['options'][] = $option;
        }
    }

    $cdisplay['hascapability'] = is_enrolled($context, NULL, 'mod/choiceanon:choose'); //only enrolled users are allowed to make a choice

    if ($choiceanon->allowupdate && $DB->record_exists('choiceanon_answers', array('choiceanonid'=> $choiceanon->id, 'userid'=> $user->id))) {
        $cdisplay['allowupdate'] = true;
    }

    return $cdisplay;
}

/**
 * @global object
 * @param int $formanswer
 * @param object $choiceanon
 * @param int $userid
 * @param object $course Course object
 * @param object $cm
 */
function choiceanon_user_submit_response($formanswer, $choiceanon, $userid, $course, $cm) {
    global $DB, $CFG;
    require_once($CFG->libdir.'/completionlib.php');

    $current = $DB->get_record('choiceanon_answers', array('choiceanonid' => $choiceanon->id, 'userid' => $userid));
    $context = context_module::instance($cm->id);

    $countanswers=0;
    if($choiceanon->limitanswers) {
        // Find out whether groups are being used and enabled
        if (groups_get_activity_groupmode($cm) > 0) {
            $currentgroup = groups_get_activity_group($cm);
        } else {
            $currentgroup = 0;
        }
        if($currentgroup) {
            // If groups are being used, retrieve responses only for users in
            // current group
            global $CFG;
            $answers = $DB->get_records_sql("
SELECT
    ca.*
FROM
    {choiceanon_answers} ca
    INNER JOIN {groups_members} gm ON ca.userid=gm.userid
WHERE
    optionid=?
    AND gm.groupid=?", array($formanswer, $currentgroup));
        } else {
            // Groups are not used, retrieve all answers for this option ID
            $answers = $DB->get_records("choiceanon_answers", array("optionid" => $formanswer));
        }

        if ($answers) {
            foreach ($answers as $a) { //only return enrolled users.
                if (is_enrolled($context, $a->userid, 'mod/choiceanon:choose')) {
                    $countanswers++;
                }
            }
        }
        $maxans = $choiceanon->maxanswers[$formanswer];
    }

    if (!($choiceanon->limitanswers && ($countanswers >= $maxans) )) {
        if ($current) {

            $newanswer = $current;
            $newanswer->optionid = $formanswer;
            $newanswer->timemodified = time();
            $DB->update_record("choiceanon_answers", $newanswer);

            $eventdata = array();
            $eventdata['context'] = $context;
            $eventdata['objectid'] = $newanswer->id;
            $eventdata['userid'] = $userid;
            $eventdata['courseid'] = $course->id;
            $eventdata['other'] = array();
            $eventdata['other']['choiceanonid'] = $choiceanon->id;
            $eventdata['other']['optionid'] = $formanswer;

            $event = \mod_choiceanon\event\answer_updated::create($eventdata);
            $event->add_record_snapshot('choice_answers', $newanswer);
            $event->add_record_snapshot('course', $course);
            $event->add_record_snapshot('course_modules', $cm);
            $event->trigger();
        } else {
            $newanswer = new stdClass();
            $newanswer->choiceanonid = $choiceanon->id;
            $newanswer->userid = $userid;
            $newanswer->optionid = $formanswer;
            $newanswer->timemodified = time();
            $newanswer->id = $DB->insert_record("choiceanon_answers", $newanswer);

            // Update completion state
            $completion = new completion_info($course);
            if ($completion->is_enabled($cm) && $choiceanon->completionsubmit) {
                $completion->update_state($cm, COMPLETION_COMPLETE);
            }

            $eventdata = array();
            $eventdata['context'] = $context;
            $eventdata['objectid'] = $newanswer->id;
            $eventdata['userid'] = $userid;
            $eventdata['courseid'] = $course->id;
            $eventdata['other'] = array();
            $eventdata['other']['choiceanonid'] = $choiceanon->id;
            $eventdata['other']['optionid'] = $formanswer;

            $event = \mod_choiceanon\event\answer_submitted::create($eventdata);
            $event->add_record_snapshot('choice_answers', $newanswer);
            $event->add_record_snapshot('course', $course);
            $event->add_record_snapshot('course_modules', $cm);
            $event->trigger();
        }
    } else {
        if (!($current->optionid==$formanswer)) { //check to see if current choice already selected - if not display error
            print_error('choiceanonfull', 'choiceanon');
        }
    }
}

/**
 * @param array $user
 * @param object $cm
 * @return void Output is echo'd
 */
function choiceanon_show_reportlink($user, $cm) {
    $responsecount =0;
    foreach($user as $optionid => $userlist) {
        if ($optionid) {
            $responsecount += count($userlist);
        }
    }

    echo '<div class="reportlink">';
    echo "<a href=\"report.php?id=$cm->id\">".get_string("viewallresponses", "choiceanon", $responsecount)."</a>";
    echo '</div>';
}

/**
 * @global object
 * @param object $choiceanon
 * @param object $course
 * @param object $coursemodule
 * @param array $allresponses

 *  * @param bool $allresponses
 * @return object
 */
function prepare_choiceanon_show_results($choiceanon, $course, $cm, $allresponses, $forcepublish=false) {
    global $CFG, $CHOICEANON_COLUMN_HEIGHT, $FULLSCRIPT, $PAGE, $OUTPUT, $DB;

    $display = clone($choiceanon);
    $display->coursemoduleid = $cm->id;
    $display->courseid = $course->id;

    //overwrite options value;
    $display->options = array();
    $totaluser = 0;
    foreach ($choiceanon->option as $optionid => $optiontext) {
        $display->options[$optionid] = new stdClass;
        $display->options[$optionid]->text = $optiontext;
        $display->options[$optionid]->maxanswer = $choiceanon->maxanswers[$optionid];

        if (array_key_exists($optionid, $allresponses)) {
            $display->options[$optionid]->user = $allresponses[$optionid];
            $totaluser += count($allresponses[$optionid]);
        }
    }
    unset($display->option);
    unset($display->maxanswers);

    $display->numberofuser = $totaluser;
    $context = context_module::instance($cm->id);
    $display->viewresponsecapability = has_capability('mod/choiceanon:readresponses', $context);
    $display->deleterepsonsecapability = has_capability('mod/choiceanon:deleteresponses',$context);
    $display->fullnamecapability = has_capability('moodle/site:viewfullnames', $context);

    if (empty($allresponses)) {
        echo $OUTPUT->heading(get_string("nousersyet"), 3, null);
        return false;
    }


    $totalresponsecount = 0;
    foreach ($allresponses as $optionid => $userlist) {
        if ($choiceanon->showunanswered || $optionid) {
            $totalresponsecount += count($userlist);
        }
    }

    $hascapfullnames = has_capability('moodle/site:viewfullnames', $context);

    $viewresponses = has_capability('mod/choiceanon:readresponses', $context);
    switch ($forcepublish) {
        case CHOICEANON_PUBLISH_NAMES:
            echo '<div id="tablecontainer">';
            if ($viewresponses) {
                echo '<form id="attemptsform" method="post" action="'.$FULLSCRIPT.'" onsubmit="var menu = document.getElementById(\'menuaction\'); return (menu.options[menu.selectedIndex].value == \'delete\' ? \''.addslashes_js(get_string('deleteattemptcheck','quiz')).'\' : true);">';
                echo '<div>';
                echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
                echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
                echo '<input type="hidden" name="mode" value="overview" />';
            }

            echo "<table cellpadding=\"5\" cellspacing=\"10\" class=\"results names\">";
            echo "<tr>";

            $columncount = array(); // number of votes in each column
            if ($choiceanon->showunanswered) {
                $columncount[0] = 0;
                echo "<th class=\"col0 header\" scope=\"col\">";
                print_string('notanswered', 'choiceanon');
                echo "</th>";
            }
            $count = 1;
            foreach ($choiceanon->option as $optionid => $optiontext) {
                $columncount[$optionid] = 0; // init counters
                echo "<th class=\"col$count header\" scope=\"col\">";
                echo format_string($optiontext);
                echo "</th>";
                $count++;
            }
            echo "</tr><tr>";

            if ($choiceanon->showunanswered) {
                echo "<td class=\"col$count data\" >";
                // added empty row so that when the next iteration is empty,
                // we do not get <table></table> error from w3c validator
                // MDL-7861
                echo "<table class=\"choiceanonresponse\"><tr><td></td></tr>";
                if (!empty($allresponses[0])) {
                    foreach ($allresponses[0] as $user) {
                        echo "<tr>";
                        echo "<td class=\"picture\">";
                        echo $OUTPUT->user_picture($user, array('courseid'=>$course->id));
                        echo "</td><td class=\"fullname\">";
                        echo "<a href=\"$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$course->id\">";
                        echo fullname($user, $hascapfullnames);
                        echo "</a>";
                        echo "</td></tr>";
                    }
                }
                echo "</table></td>";
            }
            $count = 1;
            foreach ($choiceanon->option as $optionid => $optiontext) {
                    echo '<td class="col'.$count.' data" >';

                    // added empty row so that when the next iteration is empty,
                    // we do not get <table></table> error from w3c validator
                    // MDL-7861
                    echo '<table class="choiceanonresponse"><tr><td></td></tr>';
                    if (isset($allresponses[$optionid])) {
                        foreach ($allresponses[$optionid] as $user) {
                            $columncount[$optionid] += 1;
                            echo '<tr><td class="attemptcell">';
                            if ($viewresponses and has_capability('mod/choiceanon:deleteresponses',$context)) {
                                echo '<input type="checkbox" name="attemptid[]" value="'. $user->id. '" />';
                            }
                            echo '</td><td class="picture">';
                            echo $OUTPUT->user_picture($user, array('courseid'=>$course->id));
                            echo '</td><td class="fullname">';
                            echo "<a href=\"$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$course->id\">";
                            echo fullname($user, $hascapfullnames);
                            echo '</a>';
                            echo '</td></tr>';
                       }
                    }
                    $count++;
                    echo '</table></td>';
            }
            echo "</tr><tr>";
            $count = 1;

            if ($choiceanon->showunanswered) {
                echo "<td></td>";
            }

            foreach ($choiceanon->option as $optionid => $optiontext) {
                echo "<td align=\"center\" class=\"col$count count\">";
                if ($choiceanon->limitanswers) {
                    echo get_string("taken", "choiceanon").":";
                    echo $columncount[$optionid];
                    echo "<br/>";
                    echo get_string("limit", "choiceanon").":";
                    echo $choiceanon->maxanswers[$optionid];
                } else {
                    if (isset($columncount[$optionid])) {
                        echo $columncount[$optionid];
                    }
                }
                echo "</td>";
                $count++;
            }
            echo "</tr>";

            /// Print "Select all" etc.
            if ($viewresponses and has_capability('mod/choiceanon:deleteresponses',$context)) {
                echo '<tr><td></td><td>';
                echo '<a href="javascript:select_all_in(\'DIV\',null,\'tablecontainer\');">'.get_string('selectall').'</a> / ';
                echo '<a href="javascript:deselect_all_in(\'DIV\',null,\'tablecontainer\');">'.get_string('deselectall').'</a> ';
                echo '&nbsp;&nbsp;';
                echo html_writer::label(get_string('withselected', 'choiceanon'), 'menuaction');
                echo html_writer::select(array('delete' => get_string('delete')), 'action', '', array(''=>get_string('withselectedusers')), array('id'=>'menuaction', 'class' => 'autosubmit'));
                $PAGE->requires->yui_module('moodle-core-formautosubmit',
                    'M.core.init_formautosubmit',
                    array(array('selectid' => 'menuaction'))
                );
                echo '<noscript id="noscriptmenuaction" style="display:inline">';
                echo '<div>';
                echo '<input type="submit" value="'.get_string('go').'" /></div></noscript>';
                echo '</td><td></td></tr>';
            }

            echo "</table></div>";
            if ($viewresponses) {
                echo "</form></div>";
            }
            break;
    }
    return $display;
}

/**
 * @global object
 * @param array $attemptids
 * @param object $choiceanon Choice main table row
 * @param object $cm Course-module object
 * @param object $course Course object
 * @return bool
 */
function choiceanon_delete_responses($attemptids, $choiceanon, $cm, $course) {
    global $DB, $CFG;
    require_once($CFG->libdir.'/completionlib.php');

    if(!is_array($attemptids) || empty($attemptids)) {
        return false;
    }

    foreach($attemptids as $num => $attemptid) {
        if(empty($attemptid)) {
            unset($attemptids[$num]);
        }
    }

    $completion = new completion_info($course);
    foreach($attemptids as $attemptid) {
        if ($todelete = $DB->get_record('choiceanon_answers', array('choiceanonid' => $choiceanon->id, 'userid' => $attemptid))) {
            $DB->delete_records('choiceanon_answers', array('choiceanonid' => $choiceanon->id, 'userid' => $attemptid));
            // Update completion state
            if ($completion->is_enabled($cm) && $choiceanon->completionsubmit) {
                $completion->update_state($cm, COMPLETION_INCOMPLETE, $attemptid);
            }
        }
    }
    return true;
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function choiceanon_delete_instance($id) {
    global $DB;

    if (! $choiceanon = $DB->get_record("choiceanon", array("id"=>"$id"))) {
        return false;
    }

    $result = true;

    if (! $DB->delete_records("choiceanon_answers", array("choiceanonid"=>"$choiceanon->id"))) {
        $result = false;
    }

    if (! $DB->delete_records("choiceanon_options", array("choiceanonid"=>"$choiceanon->id"))) {
        $result = false;
    }

    if (! $DB->delete_records("choiceanon", array("id"=>"$choiceanon->id"))) {
        $result = false;
    }

    return $result;
}

/**
 * Returns text string which is the answer that matches the id
 *
 * @global object
 * @param object $choiceanon
 * @param int $id
 * @return string
 */
function choiceanon_get_option_text($choiceanon, $id) {
    global $DB;

    if ($result = $DB->get_record("choiceanon_options", array("id" => $id))) {
        return $result->text;
    } else {
        return get_string("notanswered", "choiceanon");
    }
}

/**
 * Gets a full choiceanon record
 *
 * @global object
 * @param int $choiceanonid
 * @return object|bool The choice or false
 */
function choiceanon_get_choiceanon($choiceanonid) {
    global $DB;

    if ($choiceanon = $DB->get_record("choiceanon", array("id" => $choiceanonid))) {
        if ($options = $DB->get_records("choiceanon_options", array("choiceanonid" => $choiceanonid), "id")) {
            foreach ($options as $option) {
                $choiceanon->option[$option->id] = $option->text;
                $choiceanon->maxanswers[$option->id] = $option->maxanswers;
            }
            return $choiceanon;
        }
    }
    return false;
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function choiceanon_get_view_actions() {
    return array('view','view all','report');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function choiceanon_get_post_actions() {
    return array('choose','choose again');
}


/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the choice.
 *
 * @param object $mform form passed by reference
 */
function choiceanon_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'choiceanonheader', get_string('modulenameplural', 'choiceanon'));
    $mform->addElement('advcheckbox', 'reset_choiceanon', get_string('removeresponses','choiceanon'));
}

/**
 * Course reset form defaults.
 *
 * @return array
 */
function choiceanon_reset_course_form_defaults($course) {
    return array('reset_choiceanon'=>1);
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * choiceanon responses for course $data->courseid.
 *
 * @global object
 * @global object
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function choiceanon_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('modulenameplural', 'choiceanon');
    $status = array();

    if (!empty($data->reset_choiceanon)) {
        $choiceanonssql = "SELECT ch.id
                       FROM {choiceanon} ch
                       WHERE ch.course=?";

        $DB->delete_records_select('choiceanon_answers', "choiceanonid IN ($choiceanonssql)", array($data->courseid));
        $status[] = array('component'=>$componentstr, 'item'=>get_string('removeresponses', 'choiceanon'), 'error'=>false);
    }

    /// updating dates - shift may be negative too
    if ($data->timeshift) {
        shift_course_mod_dates('choiceanon', array('timeopen', 'timeclose'), $data->timeshift, $data->courseid);
        $status[] = array('component'=>$componentstr, 'item'=>get_string('datechanged'), 'error'=>false);
    }

    return $status;
}

/**
 * @global object
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @param object $choiceanon
 * @param object $cm
 * @param int $groupmode
 * @return array
 */
function choiceanon_get_response_data($choiceanon, $cm, $groupmode) {
    global $CFG, $USER, $DB;

    $context = context_module::instance($cm->id);

/// Get the current group
    if ($groupmode > 0) {
        $currentgroup = groups_get_activity_group($cm);
    } else {
        $currentgroup = 0;
    }

/// Initialise the returned array, which is a matrix:  $allresponses[responseid][userid] = responseobject
    $allresponses = array();

/// First get all the users who have access here
/// To start with we assume they are all "unanswered" then move them later
    $allresponses[0] = get_enrolled_users($context, 'mod/choiceanon:choose', $currentgroup, user_picture::fields('u', array('idnumber')));

/// Get all the recorded responses for this choice
    $rawresponses = $DB->get_records('choiceanon_answers', array('choiceanonid' => $choiceanon->id));

/// Use the responses to move users into the correct column

    if ($rawresponses) {
        foreach ($rawresponses as $response) {
            if (isset($allresponses[0][$response->userid])) {   // This person is enrolled and in correct group
                $allresponses[0][$response->userid]->timemodified = $response->timemodified;
                $allresponses[$response->optionid][$response->userid] = clone($allresponses[0][$response->userid]);
                unset($allresponses[0][$response->userid]);   // Remove from unanswered column
            }
        }
    }
    return $allresponses;
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function choiceanon_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function choiceanon_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $choiceanonnode The node to add module settings to
 */
function choiceanon_extend_settings_navigation(settings_navigation $settings, navigation_node $choiceanonnode) {
    global $PAGE;

    if (has_capability('mod/choiceanon:readresponses', $PAGE->cm->context)) {

        $groupmode = groups_get_activity_groupmode($PAGE->cm);
        if ($groupmode) {
            groups_get_activity_group($PAGE->cm, true);
        }
        // We only actually need the choiceanon id here
        $choiceanon = new stdClass;
        $choiceanon->id = $PAGE->cm->instance;
        $allresponses = choiceanon_get_response_data($choiceanon, $PAGE->cm, $groupmode);   // Big function, approx 6 SQL calls per user

        $responsecount =0;
        foreach($allresponses as $optionid => $userlist) {
            if ($optionid) {
                $responsecount += count($userlist);
            }
        }
        $choiceanonnode->add(get_string("viewallresponses", "choiceanon", $responsecount), new moodle_url('/mod/choiceanon/report.php', array('id'=>$PAGE->cm->id)));
    }
}

/**
 * Obtains the automatic completion state for this choice based on any conditions
 * in forum settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function choiceanon_get_completion_state($course, $cm, $userid, $type) {
    global $CFG,$DB;

    // Get choiceanon details
    $choiceanon = $DB->get_record('choiceanon', array('id'=>$cm->instance), '*',
            MUST_EXIST);

    // If completion option is enabled, evaluate it and return true/false
    if($choiceanon->completionsubmit) {
        return $DB->record_exists('choiceanon_answers', array(
                'choiceanonid'=>$choiceanon->id, 'userid'=>$userid));
    } else {
        // Completion option is not enabled so just return $type
        return $type;
    }
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function choiceanon_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-choiceanon-*'=>get_string('page-mod-choiceanon-x', 'choiceanon'));
    return $module_pagetype;
}
