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
 * Report courses helper class.
 *
 * @package    report_mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagner@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_mbs\local;

class reportcourses {

    // Register new action here.
    private static $bulkactions = array(
        'delete', 'move', 'unenrol'
    );

    /**
     * Checks, whether action is valid and throws an error if a invalid action
     * is detected.
     *
     * @param string $action name of an action
     * @param \moodle_url $redirecturl the url to redirect, if an error occurs
     */
    public static function require_valid_action($action, \moodle_url $redirecturl) {
        if (!in_array($action, self::$bulkactions)) {
            print_error('unknownaction', 'report_mbs', $redirecturl);
        }
    }

    /**
     * Get a localized menu of actions for dropdown.
     *
     * @return array list of actions indexed by action key.
     */
    public static function get_actions_menu() {

        $actionmenu = [];

        foreach (self::$bulkactions as $action) {
            $actionmenu[$action] = get_string('bulkaction_' . $action, 'report_mbs');
        }
        return $actionmenu;
    }

    /**
     * Get courses stats data.
     *
     * @param array $filterdata
     * @param \flexible_table $table
     * @param int $perpage
     * @param boolean $download
     * @return array list of found courses.
     */
    public function get_courses_stats($filterdata, $table, $perpage, $download) {
        global $DB;

        $cols = "  c.id, c.fullname as coursename, c.timemodified as timemodified,
                   cs.participantscount as participantscount, cs.trainerscount as trainerscount,
                   cs.modulescount as modulescount, cs.lastviewed as lastviewed,
                   cs.filesize as filessize,
                   cc.id as categoryid, cc.name as categoryname ";

        $from = "FROM {course} c
                 JOIN {report_mbs_course} cs ON cs.courseid = c.id
                 JOIN {course_categories} cc ON cc.id = c.category ";

        $cond = array();
        $params = array();

        // Set filter for selected country.
        if (!empty($filterdata->coursename)) {

            if (strpos($filterdata->coursename, '*') !== false) {

                $cond[] = $DB->sql_like('c.fullname', ':coursename', false);
                $params['coursename'] = str_replace('*', '%', $filterdata->coursename);
            } else {
                $cond[] = " c.fullname = :coursename ";
                $params['coursename'] = $filterdata->coursename;
            }
        }

        if (isset($filterdata->maxparticipantscount) and ( $filterdata->maxparticipantscount !== '')) {

            $cond[] = " cs.participantscount <= :participantscount ";
            $params['participantscount'] = $filterdata->maxparticipantscount;
        }

        if (isset($filterdata->maxmodulescount) and ( $filterdata->maxmodulescount !== '')) {

            $cond[] = " cs.modulescount <= :modulescount ";
            $params['modulescount'] = $filterdata->maxmodulescount;
        }

        if (isset($filterdata->maxtrainerscount) and ( $filterdata->maxtrainerscount !== '')) {

            $cond[] = " cs.trainerscount <= :maxtrainerscount ";
            $params['maxtrainerscount'] = $filterdata->maxtrainerscount;
        }

        if (!empty($filterdata->lastviewedbefore)) {

            $lastviewedbefore = time() - $filterdata->lastviewedbefore;
            $cond[] = " cs.lastviewed <= :lastaccess ";
            $params['lastaccess'] = $lastviewedbefore;
        }

        if (!empty($filterdata->lastmodifiedbefore)) {

            $lastmodifiedbefore = time() - $filterdata->lastmodifiedbefore;
            $cond[] = " c.timemodified <= :timemodified ";
            $params['timemodified'] = $lastmodifiedbefore;
        }

        $where = '';
        if (count($cond) > 0) {
            $where = " WHERE " . implode(" AND ", $cond);
        }

        $orderby = '';
        if ($table) {
            $orderby = " ORDER BY " . $table->get_sql_sort() . ", c.id DESC ";
        }

        $total = $DB->get_records_sql("SELECT c.id " . $from . $where, $params);
        $total = count($total);

        if (!$download) {
            $table->pagesize($perpage, $total);
            $limitfrom = $table->get_page_start();
        } else {
            $limitfrom = 0;
            $perpage = 0;
        }

        $sql = "SELECT $cols " . $from . $where . $orderby;

        if (!$courses = $DB->get_records_sql($sql, $params, $limitfrom, $perpage)) {
            return array();
        }

        return $courses;
    }

    /**
     * Get statistical data for courses
     *
     * @param array $courseids id of courses to get the data
     * @return array list of stats data index by courseid
     */
    private static function get_course_stats_data($courseids) {
        global $DB;

        $roles = get_roles_with_capability('moodle/course:enrolconfig');

        list($inroleid, $inroleparams) = $DB->get_in_or_equal(array_keys($roles), SQL_PARAMS_NAMED);

        $cols = "  c.id, count(DISTINCT ue.userid) as participantscount,
                   count(DISTINCT ra.userid) as trainerscount,
                   ctx.path as coursecontextpath ";

        $from = "FROM {course} c
                 JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel1
                 LEFT JOIN {enrol} e ON e.courseid = c.id
                 LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ue.userid = ra.userid AND ra.roleid $inroleid";

        $params = array('contextlevel1' => CONTEXT_COURSE);
        $params += $inroleparams;

        $groupby = " GROUP BY c.id ";

        // Restrict to given courses.
        list($incourseids, $incourseparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);

        $where = "WHERE c.id {$incourseids}";
        $params += $incourseparams;

        $sql = "SELECT $cols " . $from . $where . $groupby;

        // First Step: get courses with enrolments.
        $courses = $DB->get_records_sql($sql, $params);

        // Second Step: get lastaccesses in another query to avoid performance problems.
        $sql = "SELECT c.id, max(la.timeaccess) as lastviewed
                FROM {course} c
                LEFT JOIN {user_lastaccess} la ON la.courseid = c.id
                WHERE c.id {$incourseids} GROUP BY c.id ";

        $lastaccesses = $DB->get_records_sql($sql, $incourseparams);

        // Third Step: get modulcounts in another query to avoid performance problems.
        $sql = "SELECT c.id, count(cm.id) as modulescount
                FROM {course} c
                LEFT JOIN {course_modules} cm ON cm.course = c.id
                WHERE c.id {$incourseids} GROUP BY c.id ";

        $modulecounts = $DB->get_records_sql($sql, $incourseparams);

        foreach ($courses as $course) {

            $course->lastviewed = 0;
            $course->modulescount = 0;

            if (!empty($lastaccesses[$course->id])) {
                $course->lastviewed = $lastaccesses[$course->id]->lastviewed;
            }

            if (!empty($modulecounts[$course->id])) {
                $course->modulescount = $modulecounts[$course->id]->modulescount;
            }
        }

        return $courses;
    }

    /**
     * Get the size of all files that this course contains.
     *
     * @param string $coursecontextpath
     * @return int
     */
    private static function get_filesize($coursecontextpath) {
        global $DB;

        $sql = "SELECT SUM(f.filesize) as filesize
                FROM {files} f
                JOIN {context} cx ON f.contextid = cx.id AND cx.contextlevel >= :coursecontextlevel ";

        // Get where.
        $cond = array(" f.filename <> '.' AND f.filearea <> 'draft' ");
        $params = array('coursecontextlevel' => CONTEXT_COURSE);

        // Restrict to coursecontext.
        $cond[] = "((" . $DB->sql_like('cx.path', ':contextpath1', false, false) . ") OR (cx.path = :contextpath2))";
        $params['contextpath1'] = $coursecontextpath . '/%';
        $params['contextpath2'] = $coursecontextpath;

        $where = 'WHERE ' . implode(' AND ', $cond);

        $filesize = $DB->get_record_sql($sql . $where, $params);

        if (empty($filesize->filesize)) {
            return 0;
        }

        return $filesize->filesize;
    }

    /**
     * Get stats data for courses and insert the data into database.
     *
     * @param array $courseids
     * @return int count of inserted data objects.
     */
    private static function add_course_stats_data($courseids) {
        global $DB;

        $coursestats = self::get_course_stats_data($courseids);

        if (empty($coursestats)) {
            return 0;
        }

        // Insert gathered data.
        foreach ($coursestats as $data) {

            $stats = new \stdClass();
            $stats->courseid = $data->id;
            $stats->participantscount = $data->participantscount;
            $stats->trainerscount = $data->trainerscount;
            $stats->modulescount = $data->modulescount;
            $stats->lastviewed = (empty($data->lastviewed)) ? 0 : $data->lastviewed;
            $stats->filesize = self::get_filesize($data->coursecontextpath);
            $stats->timecreated = time();
            $stats->timelastsync = $stats->timecreated;

            $DB->insert_record('report_mbs_course', $stats);
        }

        return count($coursestats);
    }

    /**
     * Get stats data for courses and udpate the data in database.
     *
     * @param array $courseids
     * @return int count of inserted data objects.
     */
    public static function update_course_stats_data($courseids) {
        global $DB;

        $coursestats = self::get_course_stats_data($courseids);

        if (empty($coursestats)) {
            return 0;
        }

        // Load from database.
        $reportdata = $DB->get_records_list('report_mbs_course', 'courseid', $courseids);

        // Insert gathered data.
        foreach ($reportdata as $stats) {

            if (!isset($coursestats[$stats->courseid])) {
                continue;
            }

            $data = $coursestats[$stats->courseid];

            $stats->participantscount = $data->participantscount;
            $stats->trainerscount = $data->trainerscount;
            $stats->modulescount = $data->modulescount;
            $stats->lastviewed = (empty($data->lastviewed)) ? 0 : $data->lastviewed;
            $stats->filesize = self::get_filesize($data->coursecontextpath);
            $stats->timelastsync = time();

            $DB->update_record('report_mbs_course', $stats);
        }

        return count($reportdata);
    }

    public static function delete_courses_stats_data($courseids) {
        global $DB;

        $DB->delete_records_list('report_mbs_course', 'courseid', $courseids);
    }

    /**
     * Retrieve data for the courses to speed up report.
     *
     * 1- Find courses, where there is no stats data and add them.
     * 2- Find data for deleted courses and delete them.
     * 3- Sync data starting with the most last courses.
     *
     */
    public static function sync_courses_stats() {
        global $DB;

        $maxcount = get_config('report_mbs', 'reportcoursesynccount');

        // Find courses, where there is data to delete.
        $sql = "SELECT rc.courseid
                FROM {report_mbs_course} rc
                LEFT JOIN {course} c ON c.id = rc.courseid
                WHERE c.id IS NULL";

        $deletecourseids = $DB->get_records_sql($sql, array(), 0, $maxcount);

        $countdeleted = 0;
        if (!empty($deletecourseids)) {
            $countdeleted = count($deletecourseids);
            $DB->delete_records_list('report_mbs_course', 'courseid', array_keys($deletecourseids));
        }

        // Get courses to update.
        $updatecourseids = $DB->get_records('report_mbs_course', array(), 'timelastsync ASC', 'courseid', 0, $maxcount);
        $countupdated = 0;
        if (!empty($updatecourseids)) {
            $countupdated = self::update_course_stats_data(array_keys($updatecourseids));
        }

        // Find courses, where there is no data and add them.
        $sql = "SELECT c.id
                FROM {course} c
                LEFT JOIN {report_mbs_course} rc ON rc.courseid = c.id
                WHERE rc.courseid IS NULL AND c.id <> :siteid";

        $newcourseids = $DB->get_records_sql($sql, array('siteid' => SITEID), 0, $maxcount);

        $countadded = 0;
        if (!empty($newcourseids)) {
            $countadded = self::add_course_stats_data(array_keys($newcourseids));
        }

        mtrace("{$countadded} added, {$countdeleted} deleted, {$countupdated} updated");
    }

    /**
     * Render a information about the stats data.
     *
     * @return string HTML to show on report page.
     */
    public function render_cron_info() {
        global $DB;

        $info = new \stdClass();

        // Find courses, where there is data to delete.
        $sql = "SELECT rc.courseid
                FROM {report_mbs_course} rc
                LEFT JOIN {course} c ON c.id = rc.courseid
                WHERE c.id IS NULL";

        $deletecourseids = $DB->get_records_sql($sql, array(), 0, 1);
        $info->counttodelete = count($deletecourseids);

        // Find courses, where there is no data and add them.
        $sql = "SELECT c.id
                FROM {course} c
                LEFT JOIN {report_mbs_course} rc ON rc.courseid = c.id
                WHERE rc.courseid IS NULL AND c.id <> :siteid";

        $newcourseids = $DB->get_records_sql($sql, array('siteid' => SITEID), 0, 1);
        $info->counttoadd = count($newcourseids);

        $allcourses = (($info->counttodelete == 0) && ($info->counttoadd == 0));

        $sql = "SELECT min(timelastsync) as oldestsync FROM {report_mbs_course}";
        $oldestentry = $DB->get_record_sql($sql, array());

        // Render info.
        $o = '';
        $class = 'notifysuccess';
        if ($allcourses) {
            $o .= get_string('coursesstatscomplete', 'report_mbs');
        } else {
            $o .= get_string('coursesstatsincomplete', 'report_mbs', $info);
            $class = 'notifyproblem';
        }

        $o .= " (" . get_string('status', 'report_mbs') . ": " . userdate($oldestentry->oldestsync) . ") ";

        return \html_writer::tag('p', $o, array('class' => $class));
    }

    /**
     * Replace the categories ids in path with category names.
     *
     * @param string $path ids of categories separated by directory separator
     * @param array $categories categories indexed by category id
     * @return string path with categories names
     */
    private static function replace_catids($path, $categories) {

        $ids = explode('/', trim($path, '/'));
        $path = $path . '/';

        foreach ($ids as $id) {
            $path = str_replace("/{$id}/", "/{$categories[$id]->name}/", $path);
        }
        return $path;
    }

    /**
     * Shorten the given category path.
     *
     * @param string $path
     * @param int $offset the number of parent path to cut off
     * @return string shortened category path
     */
    private static function shorten_catpath($path, $offset) {

        $parts = explode('/', trim($path, '/'));
        $parts = array_slice($parts, $offset);
        return '../' . implode('/', $parts) . '/';
    }

    /**
     * Search for categories, wherd name is like given searchtext.
     *
     * @param string $searchtext
     * @param int $searchlimit
     * @param int $shortenlimit
     * @return array list of found categories for ajax return.
     */
    public static function get_categories_menu($searchtext, $searchlimit, $shortenlimit) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/lib/coursecatlib.php');

        $like = $DB->sql_like('name', '?', false);
        $params = array('%' . $searchtext . '%');

        // Search for categories and collect parentids.
        $sql = "SELECT id, name, path, depth FROM {course_categories} WHERE " . $like . " ORDER BY path";
        $catset = $DB->get_recordset_sql($sql, $params, 0, $searchlimit);

        $parentids = [];
        $categories = [];
        foreach ($catset as $cat) {
            $parentids = array_merge($parentids, explode('/', trim($cat->path, '/')));
            $categories[$cat->id] = $cat;
        }
        $catset->close();

        // Get parent categories.
        $parents = $DB->get_records_list('course_categories', 'id', array_unique($parentids), '', 'id, name');

        // Build catmenu.
        $catmenu = [];

        foreach ($categories as $cat) {
            $path = self::replace_catids($cat->path, $categories + $parents);
            if (($shortenlimit > 0) && ($cat->depth > $shortenlimit)) {
                $path = self::shorten_catpath($path, $shortenlimit);
            }

            $catmenu[$cat->id] = array('value' => $cat->id, 'label' => $path);
        }

        return array_values($catmenu);
    }

}
