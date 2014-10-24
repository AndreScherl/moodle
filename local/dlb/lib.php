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
 * @package   local_dlb
 * @copyright 2014 Andreas Wagner, mebis Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
function local_dlb_user_loggedin($event) {

    // set up the isTeacher - flag, we do this here for all auth types.
    local_dlb::setup_teacher_flag();
}

/** called, when user created a course */
function local_dlb_course_created($events) {
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
function local_dlb_course_category_created($event) {

    $eventdata = $event->get_data();
    //local_dlb::fix_catgeorie_sortorder($eventdata['objectid']);
}

/** fix the sortorder of moved coursecategory.
 *  regarding performance we:
 *  1. drop the unique sortorder or categories in fix_course_sortorder in a hack
 *  2. ensure that within a categorie all childs have a appropriate sortorder, which means that:
 *        a. the nmoved categorie gets sortorder = max(sortorder of childs) + MAX_COURSES_IN_CATEGORY
 * 
 * @param type $eventdata
 */
function local_dlb_course_category_updated($event) {

    $eventdata = $event->get_data();
    //local_dlb::fix_catgeorie_sortorder($eventdata['objectid']);
}

class local_dlb {

    public static function bulk_update_mysql($table, $id_column, $update_column, array &$idstovals) {
        global $DB;

        if (empty($idstovals)) {
            return false;
        }

        $sql = "UPDATE $table SET $update_column = CASE $id_column ";
        
        foreach ($idstovals as $id => $val) {
            $sql .= " WHEN '$id' THEN '$val' \n";
        }
        $sql .= " ELSE $update_column END";
        
        $DB->execute($sql);
    }

    /** get all the categories, which may be childs of given parent (depth > parent->depth)
      then build a cattree starting with the $parentcatid as a root.
      we don't use coursecattree cache here, because it is invalid after category_updated!
     * 
     * @global object $DB
     * @param int $mindepth read only cats with depth > mindepth.
     * @return array
     */
    private static function build_cattree($mindepth) {
        global $DB;

        $sql = "SELECT id, parent FROM {course_categories} where depth >= ? ORDER BY sortorder, id";

        $depthcats = $DB->get_records_sql($sql, array($mindepth));

        $allchilds = array();
        foreach ($depthcats as $cat) {

            if (!isset($allchilds[$cat->parent])) {
                $allchilds[$cat->parent] = array();
            }

            $allchilds[$cat->parent][$cat->id] = $cat->id;
        }
        return $allchilds;
    }

    /** recursively calculate appropriate sortorders
     * 
     * @param array $allcat tree informations
     * @param int $parentid current parentid
     * @param int $sortorder current sortorder
     * @param array $newsortorders containing sortorder results
     * @return boolean
     */
    private static function calc_subtree_sortorder(&$allcat, $parentid, $sortorder, &$newsortorders) {

        $children = array();
        if (isset($allcat[$parentid])) {
            $children = $allcat[$parentid];
        }

        foreach ($children as $catid) {

            $sortorder = $sortorder + MAX_COURSES_IN_CATEGORY;
            $newsortorders[$catid] = $sortorder;

            if (isset($allcat[$catid])) {
                self::calc_subtree_sortorder($allcat, $catid, $sortorder, $newsortorders);
            }
        }
        return true;
    }

    /** traverse the subtree with $newcategory as root and set the new sortorder values
     * 
     * @global object $DB
     * @param type $newcategory
     */
    private static function fix_subcategories_sortorder($newcategory) {
        global $DB;

        // Build the cattree, parentid => array(childids).
        $allchilds = self::build_cattree($newcategory->depth);

        // Calculate the sortorder.
        $newsortorders = array();
        self::calc_subtree_sortorder($allchilds, $newcategory->id, $newcategory->sortorder, $newsortorders);

        unset($allchilds);

        // ... set sortorder in one Statement.
        if (!empty($newsortorders)) {

            $sql = "UPDATE {course_categories} SET sortorder = CASE id ";

            foreach ($newsortorders as $id => $val) {
                $sql .= " WHEN '$id' THEN '$val' \n";
            }

            $sql .= " ELSE sortorder END";
            $DB->execute($sql);
        }
    }

    /** if the new/updated categorie has a sortorder lower than its parent, fix
     *  the sortorder of the categorie and its subcategories.
     * 
     *  @global object $DB
     *  @param int $categorieid
     *  @return boolean true, when a fix ist done.
     */
    public static function fix_catgeorie_sortorder($categorieid) {
        global $DB;

        if (!$newcategory = $DB->get_record('course_categories', array('id' => $categorieid))) {
            return false;
        }

        if ($newcategory->parent == 0) {
            return false;
        }

        $parentcat = $DB->get_record('course_categories', array('id' => $newcategory->parent));

        // ...sortorder for subcategory is greater, so nothing to do...
        if ($parentcat->sortorder < $newcategory->sortorder) {
            return false;
        }

        // ... get other childs.
        $sql = "SELECT max(cc.sortorder) as maxsortorder
                FROM {course_categories} cc
                WHERE cc.parent = ? AND cc.id <> ?";

        if (!$maxsortorder = $DB->get_field_sql($sql, array($newcategory->parent, $newcategory->id))) {

            $newcategory->sortorder = $parentcat->sortorder + MAX_COURSE_CATEGORIES;
        } else {
            // ...we have other childs, so queue at the end.
            $newcategory->sortorder = $maxsortorder + MAX_COURSE_CATEGORIES;
        }

        $DB->update_record('course_categories', $newcategory);

        // Now fix all subcategories to make sure, that subcat->sortorder > cat->sortorder to avoid problems in get_tree!
        self::fix_subcategories_sortorder($newcategory);

        return true;
    }

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