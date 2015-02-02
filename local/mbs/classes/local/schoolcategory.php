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
 * main class schoolcategory
 * 
 * a school is a category of moodle with a specific depth
 *
 * @package    local_mbs
 * @copyright  Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mbs\local;

class schoolcategory {

    public static $schoolcatdepth = 3;

    
    /** get id of school category of the current user and store it in User sessiondata.
     * 
     * @global database $DB
     * @global record $USER
     * @return boolean|int the id if succeeded
     */
    public static function get_users_schoolcatid() {
        global $DB, $USER;
        
        if (isset($USER->mbs_schoolcatid)) {
            return $USER->mbs_schoolcatid;
        }
        
        if (empty($USER->institution)) {
            return false;
        }
        
        if (!$schoolcat = $DB->get_record('course_categories', array('idnumber' => $USER->institution))) {
            return false;
        }
        
        $USER->mbs_schoolcatid = $schoolcat->id;
        
        return $USER->mbs_schoolcatid;
    }

    /** get the category of the schoolcategory (i. e. the parent category of given
     * category with the depth value of $schoolcatdepth.
     * 
     * @param type $categoryid
     */
    public static function get_schoolcategory($categoryid) {
        global $DB;

        // ...extract schoolid form context path, this is optimized for mysql!
        $ctxpath2schoolid = "REPLACE(SUBSTRING_INDEX(REPLACE(ca.path, SUBSTRING_INDEX(ca.path,'/',:depth),''), '/',2),'/','')";
        
        $sql = "SELECT sch.* FROM {course_categories} ca
                JOIN {course_categories} sch ON sch.id = $ctxpath2schoolid
                WHERE ca.id = :catid ";

        return $DB->get_record_sql($sql, array('depth' => self::$schoolcatdepth, 'catid' => $categoryid));
    }
    
    /** get the id category of the schoolcategory (i. e. the parent category of given
     * category with the depth value of $schoolcatdepth.
     * 
     * @param int $categoryid
     * @return int id of schoolcategory , 0 if not exists.
     */
    public static function get_schoolcategoryid($categoryid) {
        
        if (!$schoolcat = self::get_schoolcategory($categoryid)) {
            return 0;
        }
        
        return $schoolcat->id;
    }
    
    
    /** generate a list of categories below given category to display in a select box
     * 
     * @global \local_mbs\local\database $DB
     * @param record $category
     * @return array list of categories similar to make_categories_list indexed by category id.
     */
    public static function make_schoolcategories_list($category) {
        global $DB;
        
        $catnames = array($category->id => $category->name);
        
        // Get a list of all categories whose path puts them below the parent.
        $select = $DB->sql_like('path', ':schoolcatpath');
        $params = array(
            'schoolcatpath' => $category->path.'/%',
        );
        
        $categories = $DB->get_records_select('course_categories', $select, $params, 'depth ASC', 'id, name, parent, visible');

        // Remove any categories the user cannot see.
        foreach ($categories as $id => $category) {
            
            $catcontext = \context_coursecat::instance($id);
            if (!$category->visible && !has_capability('moodle/category:viewhiddencategories', $catcontext)) {
                unset($categories[$id]);
            }
            $catnames[$id] = $catnames[$category->parent].' / '.$category->name;
        }
        
        asort($catnames);
        
        return $catnames;
    }

}