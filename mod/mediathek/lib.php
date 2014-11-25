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
 * Mandatory public API of mediathek module
 *
 * @package    mod
 * @subpackage mediathek
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in mediathek module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function mediathek_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function mediathek_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function mediathek_reset_userdata($data) {
    return array();
}

/**
 * List of view style log actions
 * @return array
 */
function mediathek_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List of update style log actions
 * @return array
 */
function mediathek_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add url instance.
 * @param object $data
 * @param object $mform
 * @return int new mediathek instance id
 */
function mediathek_add_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/mediathek/locallib.php');

    $parameters = array();
    for ($i=0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printheading'] = (int)!empty($data->printheading);
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    $data->displayoptions = serialize($displayoptions);

    $data->externalurl = mediathek_fix_submitted_url($data->externalurl);

    $data->timemodified = time();
    $data->id = $DB->insert_record('mediathek', $data);

    return $data->id;
}

/**
 * Update url instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function mediathek_update_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/mediathek/locallib.php');

    $parameters = array();
    for ($i=0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printheading'] = (int)!empty($data->printheading);
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    $data->displayoptions = serialize($displayoptions);

    $data->externalurl = mediathek_fix_submitted_url($data->externalurl);

    $data->timemodified = time();
    $data->id           = $data->instance;

    $DB->update_record('mediathek', $data);

    return true;
}

/**
 * Delete url instance.
 * @param int $id
 * @return bool true
 */
function mediathek_delete_instance($id) {
    global $DB;

    if (!$url = $DB->get_record('mediathek', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    $DB->delete_records('mediathek', array('id'=>$url->id));

    return true;
}

/**
 * Return use outline
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $url
 * @return object|null
 */
function mediathek_user_outline($course, $user, $mod, $url) {
    global $DB;

    if ($logs = $DB->get_records('log', array('userid'=>$user->id, 'module'=>'url',
                                              'action'=>'view', 'info'=>$url->id), 'time ASC')) {

        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $result = new stdClass();
        $result->info = get_string('numviews', '', $numviews);
        $result->time = $lastlog->time;

        return $result;
    }
    return NULL;
}

/**
 * Return use complete
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $url
 */
function mediathek_user_complete($course, $user, $mod, $url) {
    global $CFG, $DB;

    if ($logs = $DB->get_records('log', array('userid'=>$user->id, 'module'=>'mediathek',
                                              'action'=>'view', 'info'=>$url->id), 'time ASC')) {
        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $strmostrecently = get_string('mostrecently');
        $strnumviews = get_string('numviews', '', $numviews);

        echo "$strnumviews - $strmostrecently ".userdate($lastlog->time);

    } else {
        print_string('neverseen', 'mediathek');
    }
}

/**
 * Returns the users with data in one url
 *
 * @todo: deprecated - to be deleted in 2.2
 *
 * @param int $urlid
 * @return bool false
 */
function mediathek_get_participants($urlid) {
    return false;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return object info
 */
function mediathek_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/mediathek/locallib.php");

    if (!$url = $DB->get_record('mediathek', array('id'=>$coursemodule->instance),
            'id, name, display, displayoptions, externalurl, parameters, intro, introformat')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $url->name;

    //note: there should be a way to differentiate links from normal resources
    $info->icon = mediathek_guess_icon($url->externalurl);

    $display = mediathek_get_final_display_type($url);

    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $fullurl = "$CFG->wwwroot/mod/mediathek/view.php?id=$coursemodule->id&amp;redirect=1";
        $options = empty($url->displayoptions) ? array() : unserialize($url->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $info->onclick = "window.open('$fullurl', '', '$wh'); return false;";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $fullurl = "$CFG->wwwroot/mod/mediathek/view.php?id=$coursemodule->id&amp;redirect=1";
        $info->onclick = "window.open('$fullmediathek'); return false;";

    }

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('mediathek', $url, $coursemodule->id, false);
    }

    return $info;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function mediathek_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-mediathek-*'=>get_string('page-mod-mediathek-x', 'mediathek'));
    return $module_pagetype;
}

/**
 * Export URL resource contents
 *
 * @return array of file content
 */
function mediathek_export_contents($cm, $baseurl) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/mediathek/locallib.php");
    $contents = array();
    $context = context_module::instance($cm->id);

    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $url = $DB->get_record('mediathek', array('id'=>$cm->instance), '*', MUST_EXIST);

    $fullurl = str_replace('&amp;', '&', mediathek_get_full_url($url, $cm, $course));
    $isurl = clean_param($fullurl, PARAM_URL);
    if (empty($isurl)) {
        return null;
    }

    $url = array();
    $url['type'] = 'mediathek';
    $url['filename']     = $url->name;
    $url['filepath']     = null;
    $url['filesize']     = 0;
    $url['fileurl']      = $fullurl;
    $url['timecreated']  = null;
    $url['timemodified'] = $url->timemodified;
    $url['sortorder']    = null;
    $url['userid']       = null;
    $url['author']       = null;
    $url['license']      = null;
    $contents[] = $url;

    return $contents;
}
