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
 * Renderer for block_mbsschooltitle
 *
 * @package   block_mbsschooltitle
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_block_mbsschooltitle_upgrade($oldversion) {
    global $DB;

    //$dbman = $DB->get_manager();

    if ($oldversion < 2015071601) {
        
        $sql = "SELECT * FROM {files}
                WHERE component = 'coursecat' AND filearea = 'description' AND filename like 'background%' ";
        
        $entries = $DB->get_records_sql($sql);
       
        foreach ($entries as $entry) {
            
            $entry->component = 'block_mbsschooltitle';
            $entry->filearea = 'schoollogo';
            $entry->filepath = '/';
            $pathname = '/'.$entry->contextid.'/'.$entry->component.'/'.$entry->filearea.'/'.$entry->itemid.$entry->filepath.$entry->filename;
            $entry->pathnamehash = sha1($pathname);
            
            if (!$exists = $DB->get_record('files', array('pathnamehash' => $entry->pathnamehash))) {
                $DB->update_record('files', $entry);
            }
            
            // Insert path.
            $pathentry = new stdClass();
            $pathentry->contenthash = 'da39a3ee5e6b4b0d3255bfef95601890afd80709';
            $pathentry->component = 'block_mbsschooltitle';
            $pathentry->filearea = 'schoollogo';
            $pathentry->filepath = '/';
            $pathentry->filename = '.';
            $pathentry->itemid = 0;
            $pathentry->contextid = $entry->contextid;
            $pathname = '/'.$entry->contextid.'/'.$entry->component.'/'.$entry->filearea.'/'.$entry->itemid.$entry->filepath.$entry->filename;
            $pathentry->pathnamehash = sha1($pathname);
            
            $pathentry->timemodified = time();
            $pathentry->timecreated = $pathentry->timemodified;
            
            if (!$exists = $DB->get_record('files', array('pathnamehash' => $pathentry->pathnamehash))) {
                $DB->insert_record('files', $pathentry);
            }
        }
        upgrade_plugin_savepoint(true, 2015071601, 'block', 'mbsschooltitle');
    }
    
    return true;
}