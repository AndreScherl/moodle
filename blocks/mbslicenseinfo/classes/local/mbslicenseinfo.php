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
        
        $fileids = $DB->get_fieldset_sql($sql . $likecondition, array('contextpath' =>  $coursecontext->path.'%'));
        
        foreach ($fileids as $fileid) {
            $files[] = new mbsfile($fileid);
        }
        
        return $files;
    }
    
    /*
     * Update the course files information
     * 
     * @param object $data - object containing form data (arrays for fiels with similar name)
     * @return bool - success of operation
     */
    public static function update_course_files($data) {
        global $DB;
        $files = self::resort_formdata($data);
        $success = true;
        
        foreach($files as $file) {
            // update files table (no insert because the file must exist)
            $filedata = new \stdClass();
            $filedata->id = $file->id;
            $filedata->filename = $file->filename;
            $filedata->author = $file->author;
            $filedata->license = $file->license->shortname;
            $success *= $DB->update_record('files', $filedata);
            
            // insert/update block_mbslicenseinfo_fmeta table
            $filemeta = new \stdClass();
            $filemeta->title = $file->title;
            $filemeta->source = $file->source;
            if($fmid = $DB->get_field('block_mbslicenseinfo_fmeta', 'id', array('files_id' => $file->id))) {
                $filemeta->id = $fmid;
                $success *= $DB->update_record('block_mbslicenseinfo_fmeta', $filemeta);
            } else {
                $filemeta->files_id = $file->id;
                $success *= $DB->insert_record('block_mbslicenseinfo_fmeta', $filemeta);
            }
            
            // insert/update block_mbslicenseinfo_ul table
            if(!empty($file->license->userid)) {
                $ul = $file->license;
                if($ulid = $DB->get_field('block_mbslicenseinfo_ul', 'id', array('shortname' => $ul->shortname))) {
                    $ul->id = $ulid;
                    $success *= $DB->update_record('block_mbslicenseinfo_ul', $ul);
                } else {
                    $success *= $DB->insert_record('block_mbslicenseinfo_ul', $ul);
                }
            }
        }
        
        return $success;
    }
    
    /*
     * Change the sort style of edit form data from column to row
     * 
     * @param object $data - object containing form data (arrays for fiels with similar name)
     * @return array of mbsfiles objects
     */
    protected static function resort_formdata($data){
        $files = array();
        for($i=0; $i<count($data->fileid); $i++) {
            $file = new mbsfile();
            $file->id = $data->fileid[$i];
            $file->filename = $data->filename[$i];
            $file->title = $data->title[$i];
            $file->source = $data->filesource[$i];
            $file->author = $data->author[$i];
            $license = new \stdClass();
            $license->id = (empty($data->licenseid[$i])) ? null : $data->licenseid[$i];
            $license->userid = (empty($data->userid[$i])) ? null : $data->userid[$i];
            $license->shortname = (empty($data->shortname[$i])) ? null: $data->shortname[$i];
            $license->fullname = (empty($data->fullname[$i])) ? null: $data->fullname[$i];
            $license->source = (empty($data->licensesource[$i])) ? null: $data->licensesource[$i];
            $file->license = $license;
            $files[] = $file;
        }
        
        return $files;
    }
    
    /**
     * Extend Course Admin Node
     * 
     * @param settings_navigation $navigation
     * @param context $context
     */
    public static function extend_course_admin_node(\settings_navigation $navigation, \context $context) {
        if($context->contextlevel == CONTEXT_COURSE && has_capability('block/mbslicenseinfo:editlicenses', $context)) {
            $courseid = $context->instanceid;
            $coursenode = $navigation->get('courseadmin');
            $licenselink = new \moodle_url('/blocks/mbslicenseinfo/editlicenses.php', array('courseid' => $courseid));
            $editlicense = $coursenode->add(get_string('editlicenses', 'block_mbslicenseinfo'), $licenselink);
        }
    } 
}