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
 * main class of block mbslicenseinfo
 *
 * @package   block_mbslicenseinfo
 * @copyright 2015, ISB Bayern
 * @author    Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbslicenseinfo\local;

class mbslicenseinfo {
        
    /** get a list of all course files including all extra infos (title, url, ...)
     * 
     * @global type $DB
     * @param int $courseid
     * @return array
     */
    public static function get_course_files($courseid) {
        global $DB;
        
        if (empty($courseid)) {
            throw new \coding_exception('Course id needed to proceed.');
        }

        $files = array();
        
        $coursecontext = \context_course::instance($courseid);
        
        $sql = "SELECT f.id 
                  FROM {files} AS f
                  JOIN {context} AS c
                    ON f.contextid = c.id
                 WHERE f.filename <> '.' AND ";
        $likecondition = $DB->sql_like('c.path', ':contextpath');
        
        echo $sql.$likecondition;
        $fileids = $DB->get_fieldset_sql($sql . $likecondition, array('contextpath' =>  $coursecontext->path.'%'));
        
        foreach ($fileids as $fileid) {
            $files[] = new mbsfile($fileid);
        }
        
        return $files;
    }
    
    /*
     * Update the course files information
     * 
     * @param array $files - file objects
     * @return mixed - array containing ids of updated files, false if something went wrong or nothing was updated
     */
    public static function update_course_files($files) {
        
    }
    
}