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
 * To store information and all code related to performance improvement of
 * handling course categories of moodle.
 *
 * @package   local_mbs
 * @copyright 2014 Andreas Wagner, mebis Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** we have many approaches to improve the speed of managing course categories, 
 * i. e. the performance of actions which are done mainly be management.php:
 * 
 * 1. Improving the coursecattree cache by modifying the get_tree() Method in lib/coursecatlib.php
 * 2. Improving the some capabilites checking for the admin in lib/coursecatlib.php
 * 3. Improving the view for non admins in renderers.php of theme (i. e. displaying only the course
 *    categories the manager can edit.
 * 4. Improving the speed of fix_course_sortorder in lib/datalib.php, current solution:
 *      
 *    - replacing multiple updates with one update-statement (specific to mysql! CASE THEN END)
 *      in fix_course_sortorder()
 *    - check if rebuild of context really needed in _fix_cat_tree
 *    - no recalculation of course sortorder in fix_course_sortorder(), do this in a cronjob every hour.
 * 
 * This class contains code for another approach, which is not active, but we want to 
 * keep it.
 * 
 */

namespace local_mbs\performance;

use cache_helper;

class fix_course_sortorder {

    public $starttime = array();

    private function __construct() {
        
    }

    /** create instance as a singleton */
    public static function instance() {
        static $fixsortorder;

        if (isset($fixsortorder)) {
            return $fixsortorder;
        }

        $fixsortorder = new fix_course_sortorder();
        return $fixsortorder;
    }

    /** do multiple updates in one statement to improve performance of fix_course_sortorder
     * 
     * @global object $DB
     * @param string $table name of table
     * @param string $id_column name of id column
     * @param string $update_column name of column to update
     * @param array $idstovals
     * @return boolean return true if succeded
     */
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

        return true;
    }

    public static function start_profiling($type) {

        $fixsortorder = self::instance();
        $fixsortorder->starttime[$type] = microtime(true);
    }

    public static function stop_profiling($type, $additionalinfo = '') {
        global $SESSION;

        if (empty($SESSION->profilefixsortorder)) {
            $SESSION->profilefixsortorder = '';
        }

        $fixsortorder = self::instance();

        if (isset($fixsortorder->starttime[$type])) {
            $add = (!empty($additionalinfo)) ? " ($additionalinfo)" : '';
            $SESSION->profilefixsortorder .= '<br/>' . $type . $add . ': ' . (microtime(true) - $fixsortorder->starttime[$type]);
        }
    }

    public static function next_profiling($next, $stoptype, $addstop = '') {
        self::stop_profiling($stoptype, $addstop);
        self::start_profiling($next);
    }

    public static function cron() {
        global $DB;
        
        $cacheevents = array();

        // categories having courses with sortorder duplicates or having gaps in sortorder
        $sql = "SELECT DISTINCT c1.category AS id , cc.sortorder
              FROM {course} c1
              JOIN {course} c2 ON c1.sortorder = c2.sortorder
              JOIN {course_categories} cc ON (c1.category = cc.id)
             WHERE c1.id <> c2.id";
        $fixcategories = $DB->get_records_sql($sql);

        $sql = "SELECT cc.id, cc.sortorder, cc.coursecount, MAX(c.sortorder) AS maxsort, MIN(c.sortorder) AS minsort
              FROM {course_categories} cc
              JOIN {course} c ON c.category = cc.id
          GROUP BY cc.id, cc.sortorder, cc.coursecount
            HAVING (MAX(c.sortorder) <>  cc.sortorder + cc.coursecount) OR (MIN(c.sortorder) <>  cc.sortorder + 1)";
        $gapcategories = $DB->get_records_sql($sql);

        foreach ($gapcategories as $cat) {
            if (isset($fixcategories[$cat->id])) {
                // duplicates detected already
            } else if ($cat->minsort == $cat->sortorder and $cat->maxsort == $cat->sortorder + $cat->coursecount - 1) {
                // easy - new course inserted with sortorder 0, the rest is ok
                $sql = "UPDATE {course}
                       SET sortorder = sortorder + 1
                     WHERE category = ?";
                $DB->execute($sql, array($cat->id));
            } else {
                // it needs full resorting
                $fixcategories[$cat->id] = $cat;
            }
            $cacheevents['changesincourse'] = true;
        }
        if (!empty($gapcategories)) {
            mtrace(count($gapcategories)." gapcategories fixed");   
        }
        unset($gapcategories);

        // fix course sortorders in problematic categories only
        $totalfixed = 0;
        foreach ($fixcategories as $cat) {
            $i = 1;
            $fixcoursesortorder = array();
            $courses = $DB->get_records('course', array('category'=>$cat->id), 'sortorder ASC, id DESC', 'id, sortorder');
            foreach ($courses as $course) {
                if ($course->sortorder != $cat->sortorder + $i) {
                    $fixcoursesortorder[$course->id] = $cat->sortorder + $i;
                }
                $i++;
            }
            self::bulk_update_mysql('{course}', 'id', 'sortorder', $fixcoursesortorder);
            if ($i > 1) {
               $totalfixed += ($i - 1);
            }
        }
        mtrace("sortorder of courses fixed: ".$totalfixed);   
        foreach (array_keys($cacheevents) as $event) {
            cache_helper::purge_by_event($event);
        }
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

    
    public static function add_output($text) {
        global $SESSION;

        if (empty($SESSION->profilefixsortorder)) {
            $SESSION->profilefixsortorder = '';
        }
        
        $SESSION->profilefixsortorder .= '<br/>' .$text;
    }
}