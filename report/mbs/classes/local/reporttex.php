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
 * report pimped courses (style and js customisations using html - block)
 * settings.
 *
 * @package    report
 * @subpackage mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagern@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_mbs\local;

class reporttex {

    private static $excludetypes = array('tinyint', 'smallint', 'mediumint', 'bigint', 'decimal', 'double', 'float');

    private static function get_text_column_names($table) {
        global $DB;

        if (!$columns = $DB->get_columns($table)) {
            return array();
        }

        $result = array();

        foreach ($columns as $columns) {

            if (!in_array($columns->type, self::$excludetypes)) {
                $result[] = $columns->name;
            }
        }

        return $result;
    }

    private static function get_entries_with_tex($table) {
        global $DB;

        $columnnames = self::get_text_column_names($table);

        $params = array('searchpattern' => '%$$%$$%');

        $entrycounts = array();
        foreach ($columnnames as $columnname) {

            $sql = "SELECT count(*) FROM {{$table}} WHERE ";
            $like = $DB->sql_like($columnname, ':searchpattern');

            if ($count = $DB->count_records_sql($sql . $like, $params)) {
                $entrycounts[$columnname] = $count;
            }
        }

        return $entrycounts;
    }

    public static function get_reports_data() {
        global $DB;

        self::add_missing_tables();
        return $DB->get_records('report_mbs_tex', null, 'tablename ASC');
    }

    protected static function add_missing_tables() {
        global $DB;

        $tablenames = $DB->get_tables();

        // Get existing tables.
        if (!$existingtables = $DB->get_records('report_mbs_tex', null, '', 'tablename')) {
            $existingtables = array();
        } else {
            $existingtables = array_keys($existingtables);
        }

        // Add tables.
        $tablenamestoadd = array_diff($tablenames, $existingtables);

        if (!empty($tablenamestoadd)) {

            foreach ($tablenamestoadd as $tablenametoadd) {

                $entry = new \stdClass();
                $entry->tablename = $tablenametoadd;
                $entry->count = '';
                $entry->active = 0;
                $entry->timecreated = time();
                $entry->timemodified = $entry->timecreated;

                $DB->insert_record('report_mbs_tex', $entry);
            }
        }

        // Delete tables.
        $tablenamestodelete = array_diff($existingtables, $tablenames);

        if (!empty($tablenamestodelete)) {
            $DB->delete_records_list('report_mbs_tex', 'tablename', $tablenamestodelete);
        }
    }

    /**
     * Collect report data for one table by cron and store results in the 
     * table of the plugin.
     * 
     */
    public static function report_tables($tablename = '') {
        global $DB;

        self::add_missing_tables();

        $select = "SELECT * FROM {report_mbs_tex} ";
        $orderby = " ORDER BY timemodified ASC ";

        $where = '';
        $params = array();
        if (!empty($tablename)) {
           $where = " WHERE tablename = ? ";
           $params = array($tablename);
        }
        
        $sql = $select.$where.$orderby;
        
        // Get most recent table.
        if (!$reportdate = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE)) {
            mtrace('...nothing to do ');
        }
        $counts = self::get_entries_with_tex($reportdate->tablename);

        $countstr = '';
        if (!empty($counts)) {
            $colstr = array();
            foreach ($counts as $key => $count) {
                $colstr[] = $key . " (" . $count . ")";
            }
            $countstr = implode(", ", $colstr);
        } else {
            $countstr = '';
        }

        $reportdate->count = $countstr;
        $reportdate->timemodified = time();
        $DB->update_record('report_mbs_tex', $reportdate);
        mtrace('...updated ' . $reportdate->tablename);
    }

    public static function save($activetables) {
        global $DB;

        if (!$reportdata = $DB->get_records('report_mbs_tex', null, 'tablename ASC')) {
            return false;
        }

        foreach ($reportdata as $date) {

            $active = (isset($activetables[$date->tablename])) ? 1 : 0;

            if ($active != $date->active) {
                $date->active = $active;
                $DB->update_record('report_mbs_tex', $date);
            }
        }
        return true;
    }

    public static function replace_tex_text($text) {

        // No to the replacement.
        $text .= ' ';
        $matches = array();
        preg_match_all('/\$\$(.+?)\$\$/s', $text, $matches);

        for ($i = 0; $i < count($matches[0]); $i++) {
            $replacement = "\( " . $matches[1][$i] . " \)";
            $text = str_replace($matches[0][$i], $replacement, $text);
        }

        return $text;
    }

    private static function update_column_with_tex($table, $columnname) {
        global $DB;

        $params = array('searchpattern' => '%$$%$$%');
        $sql = "SELECT * FROM {{$table}} WHERE ";
        $like = $DB->sql_like($columnname, ':searchpattern');

        if (!$entries = $DB->get_records_sql($sql . $like, $params)) {
            return 0;
        }

        $count = 0;
        foreach ($entries as $entry) {

            $entry->$columnname = self::replace_tex_text($entry->$columnname);

            $DB->update_record($table, $entry);
            $count++;
        }

        return $count;
    }

    public static function replace_tex() {
        global $DB;

        $sql = "SELECT * FROM {report_mbs_tex} WHERE active = ? and count <> '' ORDER BY timemodified ASC";

        // Get most recent table.
        if (!$reportdate = $DB->get_record_sql($sql, array(1), IGNORE_MULTIPLE)) {
            mtrace('...nothing to do ');
        }

        // Replace text in current table.
        $columnnames = self::get_text_column_names($reportdate->tablename);
        $total = 0;
        foreach ($columnnames as $columnname) {
            $count = self::update_column_with_tex($reportdate->tablename, $columnname);
            $total += $count;
        }

        mtrace("replaced total: ".$total);
        
        return true;
    }

}
