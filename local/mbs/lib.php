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
 *
 * @package   local_mbs
 * @copyright 2014 Andreas Wagner, mebis Bayern, 2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function local_mbs_extends_navigation($navigation) {
    global $PAGE;
    //remove the node "Home" from the navigation menu
    $node = $navigation->get('home');
    if ($node) {
        $node->remove();
    }
    //remove the node "Website" from the navigation menu
    $nodeSite = $navigation->find('site',navigation_node::COURSE_OTHER);
    if ($nodeSite) {
        $nodeSite->remove();
    }
    //add node "My schools" to navigation menu
    $schoolnode = $PAGE->navigation->add(get_string('schoolnode', 'local_mbs'), navigation_node::TYPE_CONTAINER);
    $schoolarray = \local_mbs\local\schoolcategory::get_users_schools();
    foreach ($schoolarray as $school) {
        $schoolnode->add($school->name, $school->viewurl);
    }
}

function local_mbs_extends_settings_navigation(settings_navigation $navigation) {

    // ...remove website-administration for non admins.
    if (!has_capability('moodle/site:config', context_system::instance())) {

        $node = $navigation->get('siteadministration');

        if ($node) {
            $node->remove();
        }
    }
}

/** called, when user is correcty loggedin */
function local_mbs_user_loggedin($event) {

    // set up the isTeacher - flag, we do this here for all auth types.
    local_mbs::setup_teacher_flag();
}

/** called, when user created a course */
function local_mbs_course_created($events) {
    global $DB, $USER, $COURSE;
    // assign course owner role to course creator, to manage the right of course deletion
    if ($role = $DB->get_record('role', array('shortname' => 'kursbesitzer'))) {
        role_assign($role->id, $USER->id, context_course::instance($COURSE->id)->id);
    }
}

/** fix the sortorder of new coursecategory.
 *  regarding performance we:
 *  1. drop the unique sortorder or categories in fix_course_sortorder in a hack.
 *  2. ensure that within a categorie all childs have a appropriate sortorder, which means that:
 *      a. the new categorie gets sortorder = max(sortorder of childs) + MAX_COURSES_IN_CATEGORY
 *      b. if new sortorder exceeds parentsortorder + MAX_COURSES_IN_CATEGORY, we resort all childs.  
 * 
 * @param type $eventdata
 */
function local_mbs_course_category_created($event) {

    $eventdata = $event->get_data();
    /** dieser Ansatz war ein Versuch (siehe Dokumentation Lösungsansatz 2)
     *  er verbleibt zu Dokumentationszwecken oder wird neu diskutiert, 
     *  wenn der derzeit aktive Lösungsansatz bei 
     *  zunehmender Kursbereichsanzahl verworfen werden muss.
     */
    //local_mbs\performance\fix_course_sortorder::fix_catgeorie_sortorder($eventdata['objectid']);
}

/** fix the sortorder of moved coursecategory.
 *  regarding performance we:
 *  1. drop the unique sortorder or categories in fix_course_sortorder in a hack
 *  2. ensure that within a categorie all childs have a appropriate sortorder, which means that:
 *        a. the nmoved categorie gets sortorder = max(sortorder of childs) + MAX_COURSES_IN_CATEGORY
 * 
 * @param type $eventdata
 */
function local_mbs_course_category_updated($event) {

    $eventdata = $event->get_data();
     /** dieser Ansatz war ein Versuch (siehe Dokumentation Lösungsansatz 2)
     *  er verbleibt zu Dokumentationszwecken oder wird neu diskutiert, 
      * wenn der derzeit aktive Lösungsansatz bei 
     *  zunehmender Kursbereichsanzahl verworfen werden muss.
     */
    //local_mbs\performance\fix_course_sortorder::fix_catgeorie_sortorder($eventdata['objectid']);
}

function local_mbs_course_deleted($event) {

    $coursecatcache = cache::make('core', 'coursecat');
    $coursecatcache->purge();
}




class local_mbs {
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
