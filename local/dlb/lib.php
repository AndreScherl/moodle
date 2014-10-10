<?php

/**
 * This file may not be redistributed in whole or significant part.
 * Content of this file is Protected by International Copyright Laws.
 *
 * ~~~~~~~~~ This Plugin IS NOT FREE SOFTWARE ~~~~~~~~~~~~~~~~
 *
 * @package   local_dlb
 * @copyright 2013 Andreas Wagner. All Rights reserved.
 */
function local_dlb_extends_navigation($navigation) {
    global $CFG, $PAGE;

    if (!empty($CFG->local_dlb_mebis_sites)) {

        $node = $navigation->get('home');

        if ($node) {
            // Knoten umgestalten...
            $node->text = $CFG->local_dlb_home;
            $node->action = "";
            $node->mainnavonly = true;

            // Neue Links einfügen..
            $nodes = explode(';', trim($CFG->local_dlb_mebis_sites, ";"));

            foreach ($nodes as $nnode) {
                list($name, $url) = explode(',', $nnode);
                if (!empty($name) and !empty($url)) {
                    $node->add($name, $url);
                }
            }
        }
    }

    //+++atar: add node "Meine Schulen" to navigation
    $schoolnode = $PAGE->navigation->add(get_string('schoolnode', 'local_dlb'), navigation_node::TYPE_CONTAINER);
    require_once($CFG->dirroot . "/blocks/meineschulen/lib.php");
    $schoolarray = meineschulen:: get_my_schools();

    foreach ($schoolarray as $school) {

        $schoolnode->add($school->name, $school->viewurl);
    }
    //---
}

function local_dlb_extends_settings_navigation(settings_navigation $navigation) {

    // ...remove website-administration for non admins.
    if (!has_capability('moodle/site:config', context_system::instance())) {

        $node = $navigation->get('siteadministration');

        if ($node) {
            $node->remove();
        }
    }
}

/** called, when user is correcty loggedin */
function local_dlb_user_loggedin($events) {

    // set up the isTeacher - flag, we do this here for all auth types.
    local_dlb::setup_teacher_flag();
}

class local_dlb {
    
    /* check, whether a loggedin user is a teacher (i. e. has already isTeacher == true via auth)
     * or is enrolled in min. one course as a teacher.
     *
     * @global object $USER
     * @global type $SESSION
     * @global type $DB
     * @return boolean, true falls der User als Lehrer gilt.
     */

    public static function setup_teacher_flag() {
        global $USER, $DB;

        //nur echte User zulassen....
        if (!isloggedin() or isguestuser()) {
            return false;
        }

        if (isset($USER->isTeacher)) {
            return $USER->isTeacher;
        }

        // ...check if user has a role with cap enrol/self:config.

        $roles = get_roles_with_capability('enrol/self:config');
        list($rsql, $params) = $DB->get_in_or_equal(array_keys($roles), SQL_PARAMS_NAMED);
        $params['userid'] = $USER->id;

        $sql = "SELECT ra.id
               FROM {role_assignments} ra
               WHERE ra.roleid $rsql
               AND ra.userid = :userid";

        $USER->isTeacher = $DB->record_exists_sql($sql, $params);

        return $USER->isTeacher;
    }
}