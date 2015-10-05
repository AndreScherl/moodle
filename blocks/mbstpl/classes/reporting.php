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
        global $CFG, $DB;

        $delim = ',';

        if (!$nextrun = get_config('block_mbstpl', 'nextstatsreport')) {
            set_config('nextstatsreport', time() + 180 * DAYSECS, 'block_mbstpl');
            return;
        }
        if ($nextrun >= time()) {
            echo get_string('statsreporttooearly', 'block_mbstpl', userdate($nextrun));
        }

        // set_config('nextstatsreport', time() + 180 * DAYSECS, 'block_mbstpl');

        $sql = "
        SELECT tpl.id, c.fullname AS coursetplfull, c.shortname AS coursetplshort,
          (SELECT COUNT(1) FROM {logstore_standard_log} WHERE courseid = c.id AND eventname = :eventname) AS numviews,
          (SELECT COUNT(1) FROM {block_mbstpl_coursefromtpl} WHERE templateid = tpl.id) AS numduplicated,
          (SELECT MAX(timeaccess) FROM {user_lastaccess} WHERE courseid = tpl.courseid) AS tpllastaccess,
          (SELECT MAX(timecreated)
            FROM {course} ic JOIN {block_mbstpl_coursefromtpl} ibmc ON ibmc.courseid = ic.id
            WHERE ibmc.templateid = tpl.id
          ) AS lastdupdate
        FROM {block_mbstpl_template} tpl
        JOIN {course} c ON c.id = tpl.courseid
        ";
        $params = array('eventname' => '\core\event\course_viewed');
        $results = $DB->get_recordset_sql($sql, $params);

        // Create CSV.
        $filename = 'tplstats_'.date('Ymd').'.csv';
        $dir = $CFG->tempdir.'/mbstpl';
        if (!check_dir_exists($dir)) {
            throw new \moodle_exception('errorcsvdir', 'block_mbstpl');
        }
        $filepath = $dir . '/' . $filename;
        @unlink($filepath);
        $handle = fopen($filepath, 'w');
        $headers = array(
            'crsid',
            'coursetplfull',
            'coursetplshort',
            'numviews',
            'numduplicated',
            'tpllastaccess',
            'lastdupdate',
        );
        fputcsv($handle, $headers, $delim);
        foreach($results as $result) {
            $tocsv = array(
                $result->id,
                $result->coursetplfull,
                $result->coursetplshort,
                $result->numviews,
                $result->numduplicated,
                $result->tpllastaccess ? date('d/m/Y H:i:s', $result->tpllastaccess) : '',
                $result->lastdupdate ? date('d/m/Y H:i:s', $result->lastdupdate) : '',
            );
            fputcsv($handle, $tocsv, $delim);
        }
        fclose($handle);

        // Send mail.
        $users = self::get_recipients();
        $subject = get_string('emailstatsrep_subj', 'block_mbstpl');
        $messagetext = get_string('emailstatsrep_body', 'block_mbstpl');
        $from = notifications::get_fromuser();
        foreach($users as $user) {
            email_to_user($user, $from, $messagetext, null, $filepath);
        }
        echo get_string('startsreportsent', 'block_mbstpl');
    }

    /**
     * Recipients for the stats report.
     * @return array of users.
     */
    private static function get_recipients() {
        $users = get_users_by_capability(\context_system::instance(), 'block/mbstpl:coursetemplatemanager');
        return $users;
    }
}