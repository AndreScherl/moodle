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
 * main class of local mbslicenseinfo
 *
 * @package   local_mbslicenseinfo
 * @copyright 2015, ISB Bayern
 * @author    Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mbslicenseinfo\local;

defined('MOODLE_INTERNAL') || die();

class mbslicenseinfo {
    
    /**
     * get number of course files to process some paging
     * 
     * @global $DB
     * @param int $courseid
     * @return int number of course files
     */
    public static function get_number_of_course_files($courseid) {
        global $DB;

        $sql = "SELECT COUNT(f.id) 
                  FROM {files} AS f
                  JOIN {context} AS c
                    ON f.contextid = c.id
                 WHERE f.filename <> '.' AND ";
        
        $likestatement = self::build_like_statement($courseid);
        
        return $DB->count_records_sql($sql . $likestatement->sql, $likestatement->params);
    }
    
    /** get a list of all course files including all extra infos (title, url, ...)
     * 
     * @global type $DB
     * @param int $courseid
     * @return array
     */
    public static function get_course_files($courseid, $page = 0, $limitnum = 0) {
        global $DB;
  
        $files = array();
        
        $sql = "SELECT f.id 
                  FROM {files} AS f
                  JOIN {context} AS c
                    ON f.contextid = c.id
                 WHERE f.filename <> '.' AND ";
        
        $likestatement = self::build_like_statement($courseid);
        $fileids = $DB->get_records_sql($sql . $likestatement->sql, $likestatement->params, $page*$limitnum, $limitnum);
        
        foreach ($fileids as $fileid) {
            $files[] = new mbsfile($fileid->id);
        }
        
        return $files;
    }
    
    private static function build_like_statement($courseid) {
        global $DB;
        
        if (empty($courseid)) {
            throw new \coding_exception('Course id needed to proceed.');
        }
        
        $coursecontext = \context_course::instance($courseid);
        
        $likecondition = $DB->sql_like('c.path', ':contextpath');
        $likeparams = array('contextpath' =>  $coursecontext->path.'%');
        
        $extensions = explode(',', get_config('local_mbslicenseinfo', 'extensionblacklist'));
        for($i=0;  $i<count($extensions); $i++) {
            $likecondition .= ' AND '.$DB->sql_like('f.filename', ':ext'.$i, $casesensitive = false, $accentsensitive = true, $notlike = true);
            $likeparams['ext'.$i] = '%'.$extensions[$i];
        }
        
        $likestatement = new \stdClass();
        $likestatement->sql = $likecondition;
        $likestatement->params = $likeparams;
        return $likestatement;
    }
    
    /*
     * Update the course files information
     * 
     * @param object $data - object containing form data (arrays for fiels with similar name)
     * @return bool - success of operation
     */
    public static function update_course_files($data) {
        global $DB, $USER;
        $files = self::resort_formdata($data);
        $success = true;
        
        foreach($files as $file) {       
            // insert/update local_mbslicenseinfo_fmeta table
            $filemeta = new \stdClass();
            $filemeta->title = $file->title;
            $filemeta->source = $file->source;
            if($fmid = $DB->get_field('local_mbslicenseinfo_fmeta', 'id', array('files_id' => $file->id))) {
                $filemeta->id = $fmid;
                $success *= $DB->update_record('local_mbslicenseinfo_fmeta', $filemeta);
            } else {
                $filemeta->files_id = $file->id;
                $success *= $DB->insert_record('local_mbslicenseinfo_fmeta', $filemeta);
            }
            
            // user license stuff
            $ul = $file->license;
            // insert local_mbslicenseinfo_ul table
            if($ul->shortname == '__createnewlicense__') {
                $ul->userid = $USER->id;
                $ul->shortname = null;
                $newulid = $DB->insert_record('local_mbslicenseinfo_ul', $ul);
                $ul->id = $newulid;
                $ul->shortname = 'ul_'.$newulid;
                $success *= $DB->update_record('local_mbslicenseinfo_ul', $ul);
            }
            // update local_mbslicenseinfo_ul table
            if($ulic = $DB->get_record('local_mbslicenseinfo_ul', array('shortname' => $ul->shortname))) {
                $ul->fullname = empty($ul->fullname) ? $ulic->fullname : $ul->fullname;
                $ul->source = empty($ul->source) ? $ulic->source : $ul->source;
                $ul->id = $ulic->id;
                $ul->userid = $ulic->userid;
                $success *= $DB->update_record('local_mbslicenseinfo_ul', $ul);
            }
            
            // update files table (no insert because the file must exist)
            $filedata = new \stdClass();
            $filedata->id = $file->id;
            $filedata->filename = $file->filename;
            $filedata->author = $file->author;
            $filedata->license = $file->license->shortname;
            $success *= $DB->update_record('files', $filedata);
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
            $license->userid = (empty($data->licenseuserid[$i])) ? null : $data->licenseuserid[$i];
            $license->shortname = (empty($data->licenseshortname[$i])) ? null: $data->licenseshortname[$i];
            $license->fullname = (empty($data->licensefullname[$i])) ? null: $data->licensefullname[$i];
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
        if($context->contextlevel == CONTEXT_COURSE && has_capability('local/mbslicenseinfo:editlicenses', $context)) {
            $course = $context->instanceid;
            $coursenode = $navigation->get('courseadmin');
            $licenselink = new \moodle_url('/local/mbslicenseinfo/editlicenses.php', array('course' => $course));
            $editlicense = $coursenode->add(get_string('editlicenses', 'local_mbslicenseinfo'), $licenselink);
        }
    } 
}