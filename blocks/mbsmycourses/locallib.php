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
 * Helper functions for mbsmycourses block
 * Put into a mbsmycourses class by Andre Scherl
 *
 * @package    block_mbsmycourses
 * @copyright  2015 Andreas Wagner <andreas.wagner@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mbsmycourses {

    /** Get the menu for select element to sortorder courses.
     * 
     * @return array of sortorder types.
     */
    public static function get_coursesortorder_menu() {

        $sortmenu = array('manual' => get_string('manual', 'block_mbsmycourses'),
            'fullname' => get_string('fullname', 'block_mbsmycourses'),
            'lastaccess' => get_string('lastaccess', 'block_mbsmycourses'),
            'startdate' => get_string('startdate', 'block_mbsmycourses'),
            'sortorder'  => get_string('sortorder', 'block_mbsmycourses'));

        return $sortmenu;
    }

    /**
     * get overviews for courses, get all the "news" which should be displayed for
     * a course from plugins supportin this feature.
     *
     * @param array $courses courses for which overview needs to be shown
     * @return array html overview
     */
    public static function get_overviews($courses) {
        $htmlarray = array();
        if ($modules = get_plugin_list_with_function('mod', 'print_overview')) {
            // Split courses list into batches with no more than MAX_MODINFO_CACHE_SIZE courses in one batch.
            // Otherwise we exceed the cache limit in get_fast_modinfo() and rebuild it too often.
            if (defined('MAX_MODINFO_CACHE_SIZE') && MAX_MODINFO_CACHE_SIZE > 0 && count($courses) > MAX_MODINFO_CACHE_SIZE) {
                $batches = array_chunk($courses, MAX_MODINFO_CACHE_SIZE, true);
            } else {
                $batches = array($courses);
            }
            foreach ($batches as $courses) {
                foreach ($modules as $fname) {
                    $fname($courses, $htmlarray);
                }
            }
        }
        return $htmlarray;
    }

    /**
     * Sets user preference for maximum courses to be displayed in mbsmycourses block
     *
     * @param int $number maximum courses which should be visible
     */
    public static function update_mynumber($number) {
        set_user_preference('mbsmycourses_number_of_courses', $number);
    }

    /**
     * Sets user course sorting preference in mbsmycourses block
     *
     * @param array $sortorder sort order of course
     */
    public static function update_myorder($sortorder) {
        set_user_preference('mbsmycourses_course_order', serialize($sortorder));
    }

    /**
     * Sets user category sorting preference (for list view) in mbsmycourses block
     *
     * @param array $sortorder sort order of course
     */
    public static function update_mycategoryorder($sortorder) {

        set_user_preference('mbsmycourses_category_order', serialize($sortorder));
    }

    /**
     * Returns shortname of activities in course
     *
     * @param int $courseid id of course for which activity shortname is needed
     * @return string|bool list of child shortname
     */
    public static function get_child_shortnames($courseid) {
        global $DB;
        $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
        $sql = "SELECT c.id, c.shortname, $ctxselect
                FROM {enrol} e
                JOIN {course} c ON (c.id = e.customint1)
                JOIN {context} ctx ON (ctx.instanceid = e.customint1)
                WHERE e.courseid = :courseid AND e.enrol = :method AND ctx.contextlevel = :contextlevel ORDER BY e.sortorder";
        $params = array('method' => 'meta', 'courseid' => $courseid, 'contextlevel' => CONTEXT_COURSE);

        if ($results = $DB->get_records_sql($sql, $params)) {
            $shortnames = array();
            // Preload the context we will need it to format the category name shortly.
            foreach ($results as $res) {
                context_helper::preload_from_record($res);
                $context = context_course::instance($res->id);
                $shortnames[] = format_string($res->shortname, true, $context);
            }
            $total = count($shortnames);
            $suffix = '';
            if ($total > 10) {
                $shortnames = array_slice($shortnames, 0, 10);
                $diff = $total - count($shortnames);
                if ($diff > 1) {
                    $suffix = get_string('shortnamesufixprural', 'block_mbsmycourses', $diff);
                } else {
                    $suffix = get_string('shortnamesufixsingular', 'block_mbsmycourses', $diff);
                }
            }
            $shortnames = get_string('shortnameprefix', 'block_mbsmycourses', implode('; ', $shortnames));
            $shortnames .= $suffix;
        }

        return isset($shortnames) ? $shortnames : false;
    }

    /**
     * Returns maximum number of courses which will be displayed in mbsmycourses block
     *
     * @param bool $showallcourses if set true all courses will be visible.
     * @return int maximum number of courses
     */
    public static function get_max_user_courses($showallcourses = false) {
        // Get block configuration.
        $config = get_config('block_mbsmycourses');
        $limit = $config->defaultmaxcourses;

        // If max course is not set then try get user preference.
        if (empty($config->forcedefaultmaxcourses)) {
            if ($showallcourses) {
                $limit = 0;
            } else {
                $limit = get_user_preferences('mbsmycourses_number_of_courses', $limit);
            }
        }
        return $limit;
    }

    /** helper to uasort by users sortorder of course (type manual).
     * 
     * @param record $a course1 
     * @param record $b course2
     * @return int
     */
    protected static function order_by_userssortorder($a, $b) {
        if ($a->sortorder == $b->sortorder) {
            return 0;
        }
        return ($a->sortorder > $b->sortorder) ? 1 : -1;
    }

    /** helper to uasort by lastacces of course.
     * 
     * @param record $a course1 
     * @param record $b course2
     * @return int
     */
    protected static function order_by_lastaccess($a, $b) {

        if ($a->lastaccess == $b->lastaccess) {
            return 0;
        }

        if ($a->lastaccess == 0) {
            return 1;
        }

        if ($b->lastaccess == 0) {
            return -1;
        }

        return ($a->lastaccess > $b->lastaccess) ? -1 : 1;
    }
    
    /** 
     * Helper to uasort by categories sortorder of course (type sortorder).
     * 
     * @param record $a course1 
     * @param record $b course2
     * @return int
     */
    protected static function order_by_catsortorder($a, $b) {
        if ($a->catsortorder == $b->catsortorder) {
            return 0;
        }
        return ($a->catsortorder > $b->catsortorder) ? 1 : -1;
    }
    

    /** sort courses using uasort
     * 
     * @param array $courses list or users courses
     * @param string $sorttype key of sort type (manual or lastaccess)
     * @return array sorted list of courses
     */
    protected static function sort_courses($courses, $sorttype) {
        global $DB;
        
        switch ($sorttype) {

            case 'manual' :

                // ... if there is a userdefined sortorder, add it to courses.
                if (!is_null($usersortorder = get_user_preferences('mbsmycourses_course_order'))) {

                    $order = unserialize($usersortorder);

                    $i = 0;
                    foreach ($courses as $course) {

                        $idtoorder = array_flip($order);
                        if (isset($idtoorder[$course->id])) {

                            $course->sortorder = $idtoorder[$course->id];
                        } else {
                            $course->sortorder = MAX_COURSES_IN_CATEGORY + $i;
                            $i++;
                        }
                    }

                    uasort($courses, array('mbsmycourses', 'order_by_userssortorder'));
                    return $courses;
                } else {
                    return $courses;
                }

                break;

            case 'lastaccess' :
                
                uasort($courses, array('mbsmycourses', 'order_by_lastaccess'));
                return $courses;
                
            case 'sortorder' : 

                // Gather all the uses cateories of courses.
                $catids = array();
                foreach ($courses as $course) {
                    $catids[$course->category] = $course->category;
                }
                
                if (empty($catids)) {
                    return $courses;
                }
                
                if (!$catid2sortorder = $DB->get_records_list('course_categories', 'id', $catids, '', 'id, sortorder')) {
                    return $courses;
                }
                
                foreach ($courses as $course) {
                    $coursesort = ($course->sortorder == 0)? $catid2sortorder[$course->category]->sortorder : $course->sortorder;
                    $course->catsortorder = $catid2sortorder[$course->category]->sortorder + $coursesort;
                }
                
                uasort($courses, array('mbsmycourses', 'order_by_catsortorder'));
                
                return $courses;

            default:
                return $courses;
        }
        
        return $courses;
    }

    /** retrieve courses belonging to selected school from courses. Unfortunately
     *  this cannot be done by SQL as we use moodle core function enrol_get_my_courses()
     *  to get users courses.
     * 
     * @param int $selectedschool id of selected school
     * @param array $courses all courses where this users is enrolled
     * @param array $schoolcategories all schoolcategories (i. e. coursecategory with depth = 3)
     *              where courses are in. This array provides a relation from
     *              course category id to schoolcategory id array(course->category => schoolcategoryid)
     * @return array all courses belonging to selected school
     */
    protected static function filter_school($selectedschool, $courses,
                                            $schoolcategories) {

        $schoolcourses = array();
        foreach ($courses as $course) {

            if ($selectedschool == $schoolcategories[$course->category]->id) {
                $schoolcourses[$course->id] = $course;
            }
        }
        return $schoolcourses;
    }

    /**
     * Return sorted list of user courses. Note there are two ways of sorting:
     * 
     * 1) Sorting that can be done via SQL (order by startdate, fullname)
     * 2) Sorting that must be done by uasort 
     *
     * @param bool $showallcourses if set true all courses will be visible.
     * @return array list of sorted courses and count of courses.
     */
    public static function get_sorted_courses($sortorder, $selectedschool,
                                              $showallcourses = false) {
        global $USER;

        $result = new stdClass();

        // ... we can do only order by startdate and fullname by SQL.
        // Other sorting is done with uasort later.
        if (in_array($sortorder, array('startdate', 'fullname'))) {
            $courses = enrol_get_my_courses('*', $sortorder);
        } else {
            $courses = enrol_get_my_courses('*');
        }
        
        // ...unset site course.
        $site = get_site();

        if (array_key_exists($site->id, $courses)) {
            unset($courses[$site->id]);
        }

        // ...filter courses by school.
        if ($selectedschool > 0) {

            $categoryids = array();

            foreach ($courses as $key => $course) {
                $categoryids[$course->category] = $course->category;
            }

            $result->schoolcategories = \local_mbs\local\schoolcategory::get_schoolcategories($categoryids);

            // Cannot be done by SQL, if we use enrol_get_my_courses.
            $courses = self::filter_school($selectedschool, $courses, $result->schoolcategories);
        }

        // ...add last access info for courses.
        foreach ($courses as $c) {
            if (isset($USER->currentcourseaccess[$c->id])) { // Current user session.
                $courses[$c->id]->lastaccess = $USER->currentcourseaccess[$c->id];
            } elseif (isset($USER->lastcourseaccess[$c->id])) { // Last user session.
                $courses[$c->id]->lastaccess = $USER->lastcourseaccess[$c->id];
            } else {
                $courses[$c->id]->lastaccess = 0;
            }
        }

        // ... try to sort by manual or last access.
        $sortedcourses = self::sort_courses($courses, $sortorder);
        
        // ...limit courses.
        $limit = self::get_max_user_courses($showallcourses);

        if ($limit > 0) {
            $sortedcourses = array_slice($sortedcourses, 0, $limit, true);
        }

        // From list extract site courses for overview.
        $result->sitecourses = array();

        // ...collect categories for schoolcatinformation.
        $categoryids = array();
        foreach ($sortedcourses as $course) {
            if ($course->id > 0) {
                $result->sitecourses[$course->id] = $course;
            }
            $categoryids[$course->category] = $course->category;
        }

        // Ensure that schoolcategories are set correctly.
        if (!isset($result->schoolcategories)) {
            $result->schoolcategories = \local_mbs\local\schoolcategory::get_schoolcategories($categoryids);
        }

        $result->total = count($courses);
        $result->sortedcourses = $sortedcourses;

        return $result;
    }

    /** add sortorder attribute to all recordes (grouped and indexed by category and sort them
     * 
     * @param array $groupedcourses (catid => object)
     * @return array $groupedcourses
     */
    protected static function sort_groupedcourses($groupedcourses) {

        // ... if there is a userdefined sortorder, add it to courses.
        if (!is_null($usersortorder = get_user_preferences('mbsmycourses_category_order'))) {

            $order = unserialize($usersortorder);

            $i = 0;
            foreach ($groupedcourses as $catid => $categorycourses) {

                $idtoorder = array_flip($order);
                if (isset($idtoorder[$catid])) {

                    $categorycourses->sortorder = $idtoorder[$catid];
                } else {
                    $categorycourses->sortorder = MAX_COURSE_CATEGORIES + $i;
                    $i++;
                }
            }

            uasort($groupedcourses, array('mbsmycourses', 'order_by_userssortorder'));
        }
        return $groupedcourses;
    }

    /** get all the courses (sorted by sortorder) and grouped by school for use in list view.
     * 
     * @global object $DB
     * @param  string $sortorder one of the sortorder keys, see get_coursesortorder_menu()
     * @param int $selectedschool id of selected school or 0 when not filtered.
     * @return record attribute groupedcourses contains the courses grouped by school.
     */
    public static function get_sorted_courses_group_by_school($sortorder,
                                                              $selectedschool) {
        global $DB;

        // Get courses information.
        $courses = self::get_sorted_courses($sortorder, $selectedschool, true);

        // Group courses by school and add schoolcatinformation.
        $groupedcourses = array();
        foreach ($courses->sortedcourses as $course) {

            if (isset($courses->schoolcategories[$course->category])) {
                $schoolcategory = $courses->schoolcategories[$course->category];
            } else {

                // Should only happen for courses in categories with depth < 3!
                $schoolcategory = $DB->get_record('course_categories', array('id' => $course->category));
            }

            if (!isset($groupedcourses[$schoolcategory->id])) {
                $groupedcourses[$schoolcategory->id] = new stdClass();
                $groupedcourses[$schoolcategory->id]->category = $schoolcategory;
                $groupedcourses[$schoolcategory->id]->courses = array();
            }
            $groupedcourses[$schoolcategory->id]->courses[] = $course;
        }

        $courses->groupedcourses = self::sort_groupedcourses($groupedcourses);
        return $courses;
    }

    /**
     * Get all schools of an user as a menu for select element.
     *
     * @return array schools (id, name)
     */
    public static function get_users_school_menu() {

        $usersschools = \local_mbs\local\schoolcategory::get_users_schools();

        $schools = array();
        foreach ($usersschools as $value) {
            $schools[$value->id] = $value->name;
        }

        return $schools;
    }

}