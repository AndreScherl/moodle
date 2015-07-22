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

    /** get several columns from users school cat
     * 
     * @global \database $DB
     * @global \record $USER
     * @return boolean
     */
    public static function get_users_schoolcat() {
        global $DB, $USER;

        if (isset($USER->mbs_schoolcat)) {
            return $USER->mbs_schoolcat;
        }

        if (empty($USER->institution)) {
            return false;
        }

        if (!$schoolcat = $DB->get_record('course_categories', array('idnumber' => $USER->institution), 'id, name, parent, depth, path')) {
            return false;
        }

        $USER->mbs_schoolcat = $schoolcat;
        return $USER->mbs_schoolcat;
    }

    /** get id of school category of the current user and store it in User sessiondata.
     * 
     * @global database $DB
     * @global record $USER
     * @return boolean|int the id if succeeded
     */
    public static function get_users_schoolcatid() {
        global $USER;

        if (isset($USER->mbs_schoolcat->id)) {
            return $USER->mbs_schoolcat->id;
        }

        if (!$schoolcat = self::get_users_schoolcat()) {
            return false;
        }

        return $schoolcat->id;
    }

    /** get the schoolcategory of the category (i. e. the parent category of given
     * category with the depth value of $schoolcatdepth.
     * 
     * @param type $categoryid
     * @return boolean|array false if failed, otherwise a record for the schoolcategory. 
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

    /** get the schoolcategories of the schoolcategory (i. e. the parent category of given
     *  category with the depth value of $schoolcatdepth. Similar to method above.
     * 
     * @param type $categoryid
     * @return array 
     */
    public static function get_schoolcategories($categoryids) {
        global $DB;

        if (empty($categoryids)) {
            return array();
        }

        $params = array(self::$schoolcatdepth);
        list($incatid, $inparams) = $DB->get_in_or_equal($categoryids);
        $params = array_merge($params, $inparams);

        // ...extract schoolid form context path, this is optimized for mysql!
        $ctxpath2schoolid = "REPLACE(SUBSTRING_INDEX(REPLACE(ca.path, SUBSTRING_INDEX(ca.path,'/',?),''), '/',2),'/','')";

        $sql = "SELECT ca.id as subcatid, sch.* FROM {course_categories} ca
                JOIN {course_categories} sch ON sch.id = $ctxpath2schoolid
                WHERE ca.id $incatid ";

        $schoolcategories = array();

        $rs = $DB->get_recordset_sql($sql, $params);

        foreach ($rs as $category) {
            $schoolcategories[$category->subcatid] = $category;
        }
        $rs->close();
        return $schoolcategories;
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
            'schoolcatpath' => $category->path . '/%',
        );

        $categories = $DB->get_records_select('course_categories', $select, $params, 'depth ASC', 'id, name, parent, visible');

        // Remove any categories the user cannot see.
        foreach ($categories as $id => $category) {

            $catcontext = \context_coursecat::instance($id);
            if (!$category->visible && !has_capability('moodle/category:viewhiddencategories', $catcontext)) {
                unset($categories[$id]);
            }
            $catnames[$id] = $catnames[$category->parent] . ' / ' . $category->name;
        }

        asort($catnames);

        return $catnames;
    }

    /** get the ids from all categories in the school category of the user */
    public static function get_category_childids($categoryid) {
        global $DB, $CFG;

        require_once($CFG->libdir . '/coursecatlib.php');

        $category = \coursecat::get($categoryid);

        $catids = array($category->id);

        // Get a list of all categories whose path puts them below the parent.
        $select = $DB->sql_like('path', ':schoolcatpath');
        $params = array(
            'schoolcatpath' => $category->path . '/%',
        );

        if (!$childids = $DB->get_fieldset_select('course_categories', 'id', $select, $params)) {
            return $catids;
        }

        $catids = array_merge($catids, $childids);

        return $catids;
    }

    private static function get_school_view_url($categoryid) {

        $url = new \moodle_url('/course/index.php', array('categoryid' => $categoryid));
        return $url->out();
    }

    /**
     * Get a list of all the schools within which the user is enroled in at least one course
     * Always lists the 'main' school and this is always the first on the list
     *
     * @return object[] containing: id, name, viewurl for each school
     */
    public static function get_users_schools() {
        global $USER, $DB;

        $schools = array();
        if ($mainschool = self::get_users_schoolcat()) {
            // Make sure the 'main school' is always the first one listed.
            $schools[] = (object) array(
                        'id' => $mainschool->id,
                        'name' => $mainschool->name,
                        'viewurl' => self::get_school_view_url($mainschool->id),
            );
        }

        // Get a list of all the categories within which the user is enroled in a course.
        $sql = "SELECT DISTINCT ca.id, ca.path, ca.depth
                   FROM {course_categories} ca
                   JOIN {course} c ON c.category = ca.id
                   JOIN {enrol} e ON e.courseid = c.id
                   JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = :userid)
                  WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1
                    AND (ue.timeend = 0 OR ue.timeend > :now2)
                    AND ca.depth >= :schooldepth";
        $params = array(
            'userid' => $USER->id,
            'active' => ENROL_USER_ACTIVE,
            'enabled' => ENROL_INSTANCE_ENABLED,
            'now1' => time(),
            'now2' => time(),
            'schooldepth' => self::$schoolcatdepth,
        );
        $categories = $DB->get_records_sql($sql, $params);

        // Convert the category list into a list of IDs for the 'school' categories they fall within.
        $schoolids = array();
        foreach ($categories as $category) {
            if ($category->depth == self::$schoolcatdepth) {
                $schoolid = $category->id;
            } else {
                $path = explode('/', $category->path);
                if (count($path) < (self::$schoolcatdepth + 1)) {
                    debugging("Found bad category information - id: {$category->id}; depth: {$category->depth}; path: {$category->path}");
                    continue;
                }
                $schoolid = $path[self::$schoolcatdepth];
            }
            if ($mainschool && $mainschool->id == $schoolid) {
                continue;
            }
            $schoolids[$schoolid] = $schoolid;
        }

        // Use the IDs to retrieve the names of the schools.
        if (!empty($schoolids)) {
            $categories = $DB->get_records_list('course_categories', 'id', $schoolids, 'name', 'id, name');
            foreach ($categories as $category) {
                $schools[] = (object) array(
                            'id' => $category->id,
                            'name' => $category->name,
                            'viewurl' => self::get_school_view_url($category->id),
                );
            }
        }

        return $schools;
    }

    /**
     * Try to get the navigation node for a school, when page in coursecat context
     * is called.
     * 
     * @param navigation_node $node (the root node 'courses')
     * @return boolean|navigation_node false, when we are not in coursecat context, 
     *                                 the active node, when schoolcatid is not valid
     *                                 the school-navigationnode otherwise.
     */
    public static function get_schoolnavigationnode($node) {
        
        if (!$activenode = $node->find_active_node()) {
            return false;
        }
        
        $schoolcatid = \local_mbs\local\schoolcategory::get_schoolcategoryid($activenode->key);

        if ($schoolcatid == 0) {
            return $activenode;
        }
        
        if ($schoolcatid != $activenode->key){
            
            if ($schoolcatnode = $node->find($schoolcatid, \navigation_node::TYPE_CATEGORY)) {
                return $schoolcatnode;
            }
            
        }
        return $activenode;
    }

}
