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
 * search class for block mbssearch
 *
 * @package   block_search
 * @copyright 2015 Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbssearch\local;

class mbssearch {
        
    /** do a quick search (for lookup display) in courses
     * 
     * @global type $DB
     * @param type $searchtext
     * @param type $limitnum
     * @param int $onlymyschool
     * @return type
     */
    public static function lookup_search_course($searchtext = '',
                                                $limitnum = 10, $onlymyschool = 0) {
        global $DB;

        $params = array();
        $searchcriteria = '';

        if ($searchtext) {
            $params['searchtext1'] = "%$searchtext%";
            $params['searchtext2'] = "%$searchtext%";
            $searchcriteria = ' (' . $DB->sql_like('shortname', ':searchtext1', false, false);
            $searchcriteria .= ' OR ' . $DB->sql_like('fullname', ':searchtext2', false, false) . ') ';
        }
        
        // search only in my school
        if ($onlymyschool) {
            list($searchcriteria, $params) = self::searchonlyin_schoolcat($onlymyschool, $searchcriteria, $params);
        }
        
        if (!$courses = $DB->get_records_select('course', $searchcriteria, $params, 'fullname', 'id, fullname', 0, $limitnum)) {
            return array();
        }            
        
        return $courses;
    }

    /** do a quick search (for look up display) in schools (i. e. in course categories with fixed depth)
     * 
     * @global \block_mbssearch\local\type $DB
     * @param type $searchtext
     * @param type $limitnum
     * @param int $onlymyschool
     * @return type
     */
    public static function lookup_search_school($searchtext, $limitnum = 10, $onlymyschool = 0) {
        global $DB;
        
        // search only in my school
        if ($onlymyschool) {
            return array();
        }        
        
        $params = array('depth' => \local_mbs\local\schoolcategory::$schoolcatdepth);
        $searchcriteria = 'depth = :depth';
        
        if ($searchtext) {
            $params['searchtext'] = "%$searchtext%";
            $searchcriteria .= ' AND ' . $DB->sql_like('name', ':searchtext', false, false);
        }

        if (!$categories = $DB->get_records_select('course_categories', $searchcriteria, $params, 'name', 'id, name', 0, $limitnum)) {
            return array();
        }

        return $categories;
    }

    /** do a look up search, we intenntionally don't use a renderer regarding performance!
     * 
     * @param type $searchtext
     * @param type $limitnum
     * @param int $schoolcatid
     * @return type
     */
    public static function lookup_search($searchtext, $limitnum, $schoolcatid) {

        $result = array();

        // ...lookup courses.
        $courses = self::lookup_search_course($searchtext, $limitnum, $schoolcatid);

        if (!empty($courses)) {

            $result[] = \html_writer::tag('b', get_string('courses', 'block_mbssearch'));

            foreach ($courses as $course) {
                $url = new \moodle_url('/course/view.php', array('id' => $course->id));
                $result[] = \html_writer::link($url, $course->fullname);
            }
        }

        // ...lookup schools.
        $schools = self::lookup_search_school($searchtext, $limitnum, $schoolcatid);

        if (!empty($schools)) {

            $result[] = \html_writer::tag('b', get_string('schools', 'block_mbssearch'));

            foreach ($schools as $school) {
                $url = new \moodle_url('/course/index.php', array('categoryid' => $school->id));
                $result[] = \html_writer::link($url, $school->name);
            }
        }

        return $result;
    }

    /** add all the infomation for a course for displaying purposes
     * 
     * @global database $DB
     * @param array $groupedresults the result array splitet in part of courses and schools
     * @return array the result array with added course info 
     */
    private static function add_course_info($groupedresults) {
        global $DB;

        if (empty($groupedresults['course'])) {
            return $groupedresults;
        }

        list($inid, $paramin) = $DB->get_in_or_equal($groupedresults['course']);

        if (!$courses = $DB->get_records_select('course', "id {$inid}", $paramin)) {
            return $groupedresults;
        }

        foreach ($courses as $course) {
            $groupedresults['course'][$course->id] = $course;
        }
        return $groupedresults;
    }

    /** add all the infomation for a course categorie for displaying purposes
     * 
     * @global database $DB
     * @param array $groupedresults the result array splitet in part of courses and schools
     * @return array the result array with added category info 
     */
    private static function add_school_info($groupedresults) {
        global $DB;

        if (empty($groupedresults['school'])) {
            return $groupedresults;
        }

        list($inid, $paramin) = $DB->get_in_or_equal($groupedresults['school']);

        if (!$courses = $DB->get_records_select('course_categories', "id {$inid}", $paramin)) {
            return $groupedresults;
        }

        foreach ($courses as $course) {
            $groupedresults['school'][$course->id] = $course;
        }
        return $groupedresults;
    }

    /** build the sql statement for searching a school
     * 
     * @param string $searchtext
     * @param int $onlymyschool
     * @return array
     */
    private static function build_course_sql($searchtext, $onlymyschool) {
        global $DB;
        $params1 = array();
        $searchcriteria = '';

        // ...build sql for courses.
        if ($searchtext) {
            $params1['searchtext1'] = "%$searchtext%";
            $params1['searchtext2'] = "%$searchtext%";
            $searchcriteria = 'WHERE (visible = 1) AND (' . $DB->sql_like('shortname', ':searchtext1', false, false);
            $searchcriteria .= ' OR ' . $DB->sql_like('fullname', ':searchtext2', false, false) . ') ';
        }
        
        if ($onlymyschool) {
            list($searchcriteria, $params1) = self::searchonlyin_schoolcat($onlymyschool, $searchcriteria, $params1);
        }
        
        $sqlcourse = "SELECT id, 'course' as type, fullname as name
                      FROM {course} {$searchcriteria}";

        $sqlcoursecount = "SELECT count(*), 'course' as type FROM {course} {$searchcriteria}";

        return array($sqlcourse, $sqlcoursecount, $params1);
    }

    /** build the sql statement for searching a course
     * 
     * @param string $searchtext
     * @return array
     */
    private static function build_school_sql($searchtext) {
        global $DB;

        // ...build sql for schools.
        $params2 = array();       
        $params2['depth'] = \local_mbs\local\schoolcategory::$schoolcatdepth;
        $searchcriteria = 'depth = :depth AND visible = 1';

        if ($searchtext) {
            $params2['searchtext'] = "%$searchtext%";
            $searchcriteria .= ' AND ' . $DB->sql_like('name', ':searchtext', false, false);
        }

        $sqlschool = "SELECT id, 'school' as type, name
                      FROM {course_categories} WHERE {$searchcriteria}";


        $sqlschoolcount = "SELECT count(*), 'school' as type FROM {course_categories} WHERE {$searchcriteria}";

        return array($sqlschool, $sqlschoolcount, $params2);
    }

    /**  do a full search in schools and courses: 
     *   
     *   - first retrieve only the name and the id of the objects, which can be
     *     a school (i. e. category) or a course.
     *   - second complete imformations about this searchresults for the limited
     *     result set.
     * 
     *  this is necessary to be able to limit the results according to the given limit.
     *  
     *  option of the search may be: 
     *  limit, ordered by name
     *  
     */
    public static function search($searchtext, $limitfrom, $limitnum, $filterby, $schoolcatid = 0) {
        global $DB;

        //search only in one school
        if ($schoolcatid) {
            $filterby = 'course';
        }
        
        // ...search in schools and courses.
        if ($filterby == 'nofilter') {

            list($sqlcourse, $sqlcoursecount, $params1) = self::build_course_sql($searchtext, $schoolcatid);
            $countcourses = $DB->count_records_sql($sqlcoursecount, $params1);
            
            list($sqlschool, $sqlschoolcount, $params2) = self::build_school_sql($searchtext);
            $countschools = $DB->count_records_sql($sqlschoolcount, $params2);

            $sql = "({$sqlcourse}) UNION ({$sqlschool})";
            $params = array_merge($params1, $params2);

            $total = $countcourses + $countschools;
            
        } else {

            if ($filterby == 'course') {
                list($sql, $sqlcount, $params) = self::build_course_sql($searchtext, $schoolcatid);
                $total = $DB->count_records_sql($sqlcount, $params);                
            } else {
                list($sql, $sqlcount, $params) = self::build_school_sql($searchtext);
                $total = $DB->count_records_sql($sqlcount, $params);
            }
        }

        if (!$rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum)) {
            return array();
        }

        // ... group result by type (course or school).
        $groupedresults = array('course' => array(), 'school' => array());
        $records = array();

        foreach ($rs as $record) {
            $records[] = $record;
            $groupedresults[$record->type][$record->id] = $record->id;
        }
        $rs->close();

        // ...fetch additional information for displaying the data.
        $groupedresults = self::add_course_info($groupedresults);
        $groupedresults = self::add_school_info($groupedresults);

        // ...add that information to results.
        $results = new \stdClass();
        $results->items = array();
        $results->limitfrom = $limitfrom;
        $results->limitnum = $limitnum;
        $results->schoolcatid = $schoolcatid;

        foreach ($records as $record) {

            $item = new \stdClass();
            $item->type = $record->type;
            $item->data = $groupedresults[$record->type][$record->id];
            $results->items[] = $item;
        }

        $results->total = $total;

        return $results;
    }
    
    /** Constructs 'IN()' or '=' sql fragment for searching only in one school
     * 
     * @param int $schoolcatid
     * @param string $searchcriteria
     * @param array $params
     * @return array A list containing the constructed sql fragment and an array of parameters.
     */
    private static function searchonlyin_schoolcat ($schoolcatid, $searchcriteria, $params) {
        global $DB;
        if ($catids = \local_mbs\local\schoolcategory::get_category_childids($schoolcatid)) {

            list($incatids, $inparams) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED);
            $params = array_merge($params, $inparams);  
            $searchcriteria .= " AND (category {$incatids})";
           
        }
        return array($searchcriteria, $params);
    }

}