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
 * Repair courses with broken (doubled) grade items and grade categories.
 *
 * @package    enrol_mbs
 * @copyright  2016 ISB Bayern
 * @author     Andre Scherl <andre.scherl@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_mbs\task;

class fix_broken_course_grades extends \core\task\scheduled_task {
    
    protected $tables = array('grade_items', 'grade_categories');
    
    public function get_name() {
        
        return get_string('fixbrokencoursesgrades', 'enrol_mbs');
    }
    
    public function execute() {
        global $DB;
        
        // Get all templates' id
        $templates = $DB->get_records_menu('block_mbstpl_template');
        
        foreach ($templates as $id => $courseid) {
            // clean up grade_items table, grade item of type course should be unique
            $gradeitems = $DB->get_records_menu('grade_items', array('courseid' => $courseid, 'itemtype' => 'course'));
            if (count($gradeitems) > 1) {
                $firstgradeitem = reset($gradeitems);
                $firstkey = key($gradeitems);
                unset($gradeitems[$firstkey]);
                foreach ($gradeitems as $gid => $gcid) {
                    if($DB->delete_records('grade_items', array('id' => $gid))) {
                        mtrace("Removed grade item $gid of course $courseid.");
                    }
                }
            }
            // clean up grade_categories table, grade category path should be unique and course categories have no parent
            $gradecategories = $DB->get_records_menu('grade_categories', 
                    array('courseid' => $courseid, 'parent' => null), 
                    $sort='', 
                    $fields='id,path');
            if (count($gradecategories) > 1) {
                $firstgradecat = reset($gradecategories);
                $firstkey = key($gradecategories);
                unset($gradecategories[$firstkey]);
                foreach ($gradecategories as $gid => $gpath) {
                    if ($DB->delete_records('grade_categories', array('id' => $gid))) {
                        mtrace("Removed grade category $gid of course $courseid.");
                    }
                }
            }
            // clean up task_adhoc table, only one task for each enrol_mbs instance is needed
            $sqllike = $DB->sql_like('customdata', ':cdata');
            $adhoctasks = $DB->get_records_select_menu('task_adhoc',
                    $sqllike,
                    array('cdata' => '%\"courseid\":\"' . $courseid . '\"%'), 
                    $sort='', 
                    $fields='id,customdata');
            if (count($adhoctasks) > 1) {
                $lasttask = end($adhoctasks);
                $lastkey = key($adhoctasks);
                unset($adhoctasks[$lastkey]);
                foreach ($adhoctasks as $adhocid => $adhoccustomdata) {
                    if ($DB->delete_records('task_adhoc', array('id' => $adhocid))) {
                        mtrace("Removed adhoc task $adhocid with custom data $adhoccustomdata.");
                    }
                }
            }
        }
    }
    
    // Moodle 2.8+ supports the following optional method.
    /*
    public function get_run_if_component_disabled() {
        
        return false;
    }*/
}