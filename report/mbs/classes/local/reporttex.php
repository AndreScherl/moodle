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
            
            if ($count = $DB->count_records_sql($sql.$like, $params)) {
                $entrycounts[$columnname] = $count;
            }
        }
        
        return $entrycounts;
    }
    
    public static function get_reports_data() {
        global $DB;
        
        $tables = $DB->get_tables();
        
        $result = array();
        foreach ($tables as $table) {
            
            if ($counts = self::get_entries_with_tex($table)) {
               $result[$table] = $counts; 
            }
        }
        
        return $result;
    }
    
    public static function replace_tex($tables) {
    }
}