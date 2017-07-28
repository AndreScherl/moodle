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
 * @package local_mbslicenseinfo
 * @copyright 2016 Andreas Wagner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class restore_local_mbslicenseinfo_plugin extends restore_local_plugin {

    // Keep new meta from restored meta license data.
    private $newids = array();

    protected function define_course_plugin_structure() {

        $paths = array();

        $elename = 'fmeta';
        $elepath = $this->get_pathfor('/metainfos/fmeta');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    public function process_fmeta($data) {
        global $DB;

        $data = (object) $data;

        // Store a negative value temporarily
        // to avoid collision of existing files id.
        $data->files_id = -$data->files_id;
        $this->newids[] = $DB->insert_record('local_mbslicenseinfo_fmeta', $data);
    }

    /**
     * Get records from backup_files_temp indexed by old id from mdl_files
     * 
     * @global type $DB
     * @return boolean
     */
    private function get_temp_files_data() {
        global $DB;

        $tempfiles = $DB->get_records('backup_files_temp', array('backupid' => $this->get_restoreid()));

        if (!$tempfiles = $DB->get_records('backup_files_temp', array('backupid' => $this->get_restoreid()))) {
            return false;
        }

        $filesbyoldid = array();
        foreach ($tempfiles as $file) {

            $oldfile = (object) backup_controller_dbops::decode_backup_temp_info($file->info);
            $file->filepath = $oldfile->filepath;
            $file->filename = $oldfile->filename;
            $filesbyoldid[$oldfile->id] = $file;
        }

        return $filesbyoldid;
    }

    /**
     * Search new entries in mdl_files and map the id in restored license data.
     */
    public function after_restore_course() {
        global $DB;

        if (!$filesbyoldid = $this->get_temp_files_data()) {
            return;
        }
        
        if (empty($this->newids)) {
            return;
        }

        if (!$newmetas = $DB->get_records_list('local_mbslicenseinfo_fmeta', 'id', $this->newids)) {
            return;
        }

        $fs = get_file_storage();

        foreach ($newmetas as $meta) {

            $fileid = -$meta->files_id;

            if (!isset($filesbyoldid[$fileid])) {
                continue;
            }

            $finfo = $filesbyoldid[$fileid];

            // Get the id of files entry.
            if (!$file = $fs->get_file($finfo->newcontextid, $finfo->component, $finfo->filearea, $finfo->newitemid, $finfo->filepath, $finfo->filename)) {
                continue;
            }

            $meta->files_id = $file->get_id();
            $DB->update_record('local_mbslicenseinfo_fmeta', $meta);
        }
    }

}
