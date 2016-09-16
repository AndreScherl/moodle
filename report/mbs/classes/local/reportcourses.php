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
 * Report courses settings.
 *
 * @package    report
 * @subpackage mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagern@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_mbs\local;

class reportcourses {

    /**
     * Get courses.
     *
     * @param array $filterdata
     * @param \flexible_table $table
     * @param int $perpage
     * @param boolean $download
     * @return array list of found courses.
     */
    public function get_courses($filterdata, $table, $perpage, $download) {
        global $DB;

        $roles = get_roles_with_capability('moodle/course:enrolconfig');

        list($inroleid, $inroleparams) = $DB->get_in_or_equal(array_keys($roles), SQL_PARAMS_NAMED);

        $cols = "  c.id, c.fullname as coursename, max(la.timeaccess) as lastviewed,
                   c.timemodified as timemodified, count(DISTINCT ue.userid) as participantscount,
                   count(DISTINCT ra.userid) as trainerscount,
                   count(DISTINCT cm.id) as modulescount, cc.id as categoryid, cc.name as categoryname ";

        $from = "FROM {course} c
                 JOIN mdl_context ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel1
                 LEFT JOIN {course_modules} cm ON cm.course = c.id
                 JOIN {course_categories} cc ON c.category = cc.id
                 LEFT JOIN {enrol} e ON e.courseid = c.id
                 LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 LEFT JOIN {user_lastaccess} la ON la.courseid = c.id
                 LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ue.userid = ra.userid AND ra.roleid $inroleid";

        $cond = array();
        $hcond = array();
        $params = array('contextlevel1' => CONTEXT_COURSE);
        $params += $inroleparams;

        // Set filter for selected country.
        if (!empty($filterdata->coursename)) {

            if (strpos($filterdata->coursename, '*') !== false) {

                $cond[] = $DB->sql_like('c.fullname', ':coursename');
                $params['coursename'] = str_replace('*', '%', $filterdata->coursename);
            } else {
                $cond[] = " c.fullname = :coursename ";
                $params['coursename'] = $filterdata->coursename;
            }
        }

        if (isset($filterdata->maxparticipantscount) and ( $filterdata->maxparticipantscount !== '')) {

            $hcond[] = " count(DISTINCT ue.userid) <= :participantscount ";
            $params['participantscount'] = $filterdata->maxparticipantscount;
        }

        if (isset($filterdata->maxmodulescount) and ( $filterdata->maxmodulescount !== '')) {

            $hcond[] = " count(DISTINCT cm.id) <= :modulescount ";
            $params['modulescount'] = $filterdata->maxmodulescount;
        }

        if (isset($filterdata->maxtrainerscount) and ( $filterdata->maxtrainerscount !== '')) {

            $hcond[] = " count(DISTINCT ra.userid) <= :maxtrainerscount ";
            $params['maxtrainerscount'] = $filterdata->maxtrainerscount;
        }

        if (!empty($filterdata->lastviewedbefore)) {

            $lastviewedbefore = time() - $filterdata->lastviewedbefore;
            $hcond[] = " max(la.timeaccess) <= :lastaccess ";
            $params['lastaccess'] = $lastviewedbefore;
        }

        if (!empty($filterdata->lastmodifiedbefore)) {

            $lastmodifiedbefore = time() - $filterdata->lastmodifiedbefore;
            $cond[] = " c.timemodified <= :timemodified ";
            $params['timemodified'] = $lastmodifiedbefore;
        }

        if (!empty($filterdata->showdetails)) {

            $cols .= ", sizes.sumfiles as filessize ";
            $from .= "LEFT JOIN (

                           SELECT c.id, SUM(f.filesize) as sumfiles
                           FROM mdl_course c
                           JOIN mdl_context ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel
                           JOIN mdl_files f
                           JOIN mdl_context cx ON f.contextid = cx.id
                           WHERE f.filesize > 0 AND f.filearea <> 'draft' AND LOCATE(ctx.path, cx.path) = 1
                           GROUP BY c.id

                        ) sizes ON sizes.id = c.id ";

            $params['contextlevel'] = CONTEXT_COURSE;

        } else {
            $cols .= ", 0 as filessize ";
        }

        $where = '';
        if (count($cond) > 0) {
            $where = " WHERE " . implode(" AND ", $cond);
        }

        $having = '';
        if (count($hcond) > 0) {
            $having = " HAVING " . implode(" AND ", $hcond);
        }

        $groupby = " GROUP BY c.id ";

        $orderby = '';
        if ($table) {
            $orderby = " ORDER BY " . $table->get_sql_sort() . ", c.id DESC ";
        }

        $total = $DB->get_records_sql("SELECT c.id " . $from . $where . $groupby . $having, $params);
        $total = count($total);

        if (!$download) {
            $table->pagesize($perpage, $total);
            $limitfrom = $table->get_page_start();
        } else {
            $limitfrom = 0;
            $perpage = 0;
        }

        $sql = "SELECT $cols " . $from . $where . $groupby . $having . $orderby;

        if (!$courses = $DB->get_records_sql($sql, $params, $limitfrom, $perpage)) {
            return array();
        }

        return $courses;
    }
}
