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
 * Main library functions for Prüfungsarchiv activity
 *
 * @package   mod_pmediathek
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

function pmediathek_supports($feature) {
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

function pmediathek_get_view_actions() {
    return array('view', 'view all');
}

function pmediathek_get_post_actions() {
    return array('update', 'add');
}

function pmediathek_add_instance($data, $mform) {
    global $DB;

    $data->timemodified = time();
    $data->id = $DB->insert_record('pmediathek', $data);

    return $data->id;
}

function pmediathek_update_instance($data, $mform) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    $DB->update_record('pmediathek', $data);

    return true;
}

function pmediathek_delete_instance($id) {
    global $DB;

    if (!$pmediathek = $DB->get_record('pmediathek', array('id' => $id))) {
        return false;
    }
    $DB->delete_records('pmediathek', array('id' => $pmediathek->id));
    return true;
}

function pmediathek_get_coursemodule_info($coursemodule) {
    global $DB, $CFG;

    if (!$pmediathek = $DB->get_record('pmediathek', array('id' => $coursemodule->instance))) {
        return null;
    }
    require_once($CFG->libdir.'/resourcelib.php');

    $info = new cached_cm_info();
    $info->name = $pmediathek->name;

    if ($pmediathek->display == RESOURCELIB_DISPLAY_POPUP) {
        $jsexturl = addslashes_js($pmediathek->externalurl);
        $width  = 620;
        $height = 450;
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $info->onclick = "window.open('$jsexturl', '', '$wh'); return false;";
    }

    return $info;
}

