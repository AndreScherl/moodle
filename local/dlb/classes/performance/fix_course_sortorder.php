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
 * To store core changes linked to this pluign.
 *
 * @package   local_dlb
 * @copyright 2014 Andreas Wagner, mebis Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dlb\performance;


class fix_course_sortorder {
    
    public $starttime = array();

    private function __construct() {

    }

    /** create instance as a singleton */
    public static function instance() {
        static $fixsortorder;

        if (isset($fixsortorder)) {
            return $fixsortorder;
        }

        $fixsortorder = new fix_course_sortorder();
        return $fixsortorder;
    }
    
    
    /** do multiple updates in one statement to improve performance of fix_course_sortorder
     * 
     * @global object $DB
     * @param string $table name of table
     * @param string $id_column name of id column
     * @param string $update_column name of column to update
     * @param array $idstovals
     * @return boolean return true if succeded
     */
    public static function bulk_update_mysql($table, $id_column, $update_column, array &$idstovals) {
        global $DB;

        if (empty($idstovals)) {
            return false;
        }

        $sql = "UPDATE $table SET $update_column = CASE $id_column ";
        
        foreach ($idstovals as $id => $val) {
            $sql .= " WHEN '$id' THEN '$val' \n";
        }
        $sql .= " ELSE $update_column END";
        
        $DB->execute($sql);
        
        return true;
    }
    
    public static function start_profiling ($type) {
        
        $fixsortorder = self::instance();
        $fixsortorder->starttime[$type] = microtime(true);
    }
    
    public static function stop_profiling($type, $additionalinfo = '') {
        global $SESSION;
        
        if (empty($SESSION->profilefixsortorder)) {
            $SESSION->profilefixsortorder = '';
        }
        
        $fixsortorder = self::instance();
        
        if (isset($fixsortorder->starttime[$type])) {
            $add = (!empty($additionalinfo))? " ($additionalinfo)" : '';
            $SESSION->profilefixsortorder .= '<br/>'.$type.$add.': '.(microtime(true) - $fixsortorder->starttime[$type]);
        }
    }
    
    public static function next_profiling($next, $stoptype, $addstop = '') {
        self::stop_profiling($stoptype, $addstop);
        self::start_profiling ($next);
    }
}