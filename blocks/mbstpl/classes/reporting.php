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

        $interval = DAYSECS * 180;
        $delim = ',';

        if (!$nextrun = get_config('block_mbstpl', 'nextstatsreport')) {
            set_config('nextstatsreport', time() + $interval, 'block_mbstpl');
            return;
        }
        if ($nextrun >= time()) {
            mtrace(get_string('statsreporttooearly', 'block_mbstpl', userdate($nextrun)));
            return;
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

        notifications::notify_stats($filepath);

        mtrace(get_string('startsreportsent', 'block_mbstpl'));
        set_config('nextstatsreport', time() + $interval, 'block_mbstpl');
    }

    /**
     * Reminder for untouched templates.
     */
    public static function remindercron() {
        global $DB;

        if (!$period = get_config('block_mbstpl', 'tplremindafter')) {
            return;
        }
        $fromtime = time() - $period;

        $sql = "
        SELECT tpl.id, c.id AS cid, c.fullname AS cname
        FROM {block_mbstpl_template} tpl
        JOIN {course} c ON c.id = tpl.courseid
        WHERE tpl.reminded = 0
        AND tpl.timemodified <= :fromtime1
        AND c.timecreated <= :fromtime2
        AND c.timemodified <= :fromtime3
        AND NOT EXISTS(SELECT 1 FROM {logstore_standard_log} WHERE courseid = tpl.courseid AND timecreated > :fromtime4)
        ";
        $params = array(
            'fromtime1' => $fromtime,
            'fromtime2' => $fromtime,
            'fromtime3' => $fromtime,
            'fromtime4' => $fromtime,
        );
        $templates = $DB->get_records_sql($sql, $params);
        if (empty($templates)) {
            mtrace(get_string('nountouchedtemplates', 'block_mbstpl'));
            return;
        }

        notifications::notify_reminder($templates);

        mtrace(get_string('tplremindersent', 'block_mbstpl'));

        // Update templates - notification is sent only once per template.
        $ids = array_map(function($template) { return $template->id; }, $templates);
        list($idin, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $params['reminded'] = 1;
        $sql = "UPDATE {block_mbstpl_template} SET reminded = :reminded WHERE id $idin";
        $DB->execute($sql, $params);
    }

}
