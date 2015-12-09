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
 * file class of block mbslicenseinfo - represents a file with all the mebis license and metadata stuff
 *
 * @package   block_mbslicenseinfo
 * @copyright 2015, ISB Bayern
 * @author    Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbslicenseinfo\local;

class mbsfile {
        
    public $id;
    public $filename;
    public $title;
    public $source;
    public $author;
    public $license;
            
    /*
     * Constructs a file object with all appropriate data, if id is given.
     * 
     * @param int $id id of the file in files table 
     */
    function __construct($id = null) {
        if ($id) {
            $this->id = $id;
            if($file = $this->get_file($id)) {
                $this->filename = $file->filename;
                $this->author = $file->author;
                if ($license = $this->get_license($file->license)) {
                    $this->license = $license;
                }
            }
            if($filemeta = $this->get_filemeta($id)) {
                $this->title = $filemeta->title;
                $this->source = $filemeta->source;
            }
        }
    }
    
    /*
     * Get the license of the file
     * 
     * @param string $shortname - shortname of license
     * @return object - license object
     */
    public function get_license($shortname) {
        global $DB;
        $license = new \stdClass();
        $license->id = null;
        $license->userid = null;
        $license->shortname = $shortname;
        $license->fullname = null;
        $license->source = null;
        
        // check for license of moodle core license table
        if ($lic = $DB->get_record('license', array('shortname' => $shortname))) {
            $license->id = $lic->id;
            $license->fullname = $lic->fullname;
            $license->source = $lic->source;
        }
        
        // check for user license of block_mbslicenseinfo_ul table
        if ($lic = $DB->get_record('block_mbslicenseinfo_ul', array('shortname' => $shortname))) {
            $license->id = $lic->id;
            $license->userid = $lic->userid;
            $license->fullname = $lic->fullname;
            $license->source = $lic->source;
        }
        
        return  $license;
    }
    
    public function set_license() {
        
    }
    
    /*
     * Get files data out of files table
     * 
     * @param int $id - id of the file in files table
     * @return object - files object (row of database table)
     */
    private function get_file($id) {
        global $DB;
        return $DB->get_record('files', array('id' => $id));
    }
    
    /*
     * Get the meta data of the file out of block_mbslicenseinfo_fmeta table
     * 
     * @param int $id - id of the file in files table
     * @return object - metadata (row of database table)
     */
    private function get_filemeta($id) {
        global $DB;
        return $DB->get_record('block_mbslicenseinfo_fmeta', array('files_id' => $id));
    }
}