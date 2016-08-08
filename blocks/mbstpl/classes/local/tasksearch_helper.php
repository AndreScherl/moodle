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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>..

/**
 * Form to search for tasks.
 *
 * @package block_mbstpl
 * @copyright 2016 Andreas Wagner, ISB Bayern
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace block_mbstpl\local;

defined('MOODLE_INTERNAL') || die();

class tasksearch_helper {

    public static function get_template_overview($filterdata, $table, $perpage, $download) {
        global $DB;

        $concat = 'CONCAT("{\"courseid\":\"", c.id)';


        //, $concat,
        $select = "SELECT t.*, c.id as courseid1, c.fullname, b.incluserdata,
                   u.id as userid, u.firstname, u.lastname,
                   ud.userid as uduserid, ud.firstname as udfirstname, ud.lastname as udlastname,
                   task.customdata, task.nextruntime ";

        $from =   "FROM {block_mbstpl_template} t
                   LEFT JOIN {course} c ON c.id = t.courseid
                   LEFT JOIN {user} u ON u.id = t.authorid
                   LEFT JOIN {block_mbstpl_userdeleted} ud ON ud.userid = t.authorid
                   LEFT JOIN {block_mbstpl_backup} b ON b.id = t.backupid
                   LEFT JOIN {task_adhoc} task ON LOCATE(CONCAT('{\"courseid\":\"', c.id), task.customdata) = 1";

        $params = array();
        $params['component'] = 'enrol_mbs';

        $cond = array();

        if (!empty($filterdata->status)) {
            $cond[] = ' t.status = :status';
            $params['status'] = $filterdata->status;
        }

        if (!empty($filterdata->userdata)) {
            $cond[] = ' b.incluserdata = :userdata';
            $params['userdata'] = ($filterdata->userdata % 2);
        }

        $where = '';
        if (!empty($cond)) {
            $where = ' WHERE '.implode(' AND ', $cond);
        }

          // Page size of table.
        $total = $DB->count_records_sql("SELECT count(t.id) " . $from . $where, $params);

        if (!$download) {
            $table->pagesize($perpage, $total);
            $limitfrom = $table->get_page_start();
        } else {
            $limitfrom = 0;
            $perpage = 0;
        }

        $orderby = " ORDER BY ".$table->get_sql_sort();
        $sql = $select . $from . $where . $orderby;

        $data = $DB->get_records_sql($sql, $params, $limitfrom, $perpage);

        return $data;
    }

}
