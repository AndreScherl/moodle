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
 * @package block_mbstpl
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl;

defined('MOODLE_INTERNAL') || die();

/**'
 * Class reporting
 * For emailed reports etc.
 * @package block_mbstpl
 */
class reporting {
    public static function statscron() {
        global $DB;

        if (!$nextrun = get_config('block_mbstpl', 'nextstatsreport')) {
            set_config('nextstatsreport', time() + 180 * DAYSECS, 'block_mbstpl');
            return;
        }
        if ($nextrun >= time()) {
            echo get_string('statsreporttooearly', 'block_mbstpl', userdate($nextrun));
        }

        // set_config('nextstatsreport', time() + 180 * DAYSECS, 'block_mbstpl');

        $sql = "
        SELECT tpl.id, c.fullname, c.shortname,
          (SELECT COUNT(1) FROM {logstore_standard_log} WHERE courseid = c.id AND eventname = :eventname) AS tplviewed,
          (SELECT COUNT(1) FROM {block_mbstpl_coursefromtpl} WHERE templateid = tpl.id) AS numdups,
          (SELECT MAX(timeaccess) FROM {user_lastaccess} WHERE courseid = tpl.courseid) AS clastaccess,
          (SELECT MAX(timecreated)
            FROM {course} ic JOIN {block_mbstpl_coursefromtpl} ibmc ON ibmc.courseid = ic.id
            WHERE ibmc.templateid = tpl.id
          ) AS lastdup
        FROM {block_mbstpl_template} tpl
        JOIN {course} c ON c.id = tpl.courseid
        ";
        $params = array('eventname' => '\core\event\course_viewed');
        $results = $DB->get_recordset_sql($sql, $params);
        
    }

    private static function get_recipients() {

    }
}