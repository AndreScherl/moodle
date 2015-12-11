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
 * A namespace contains license specific functions
 *
 * @since      Moodle 2.7
 * @package    local_mbs
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mbs\local;

defined('MOODLE_INTERNAL') || die();

class licensemanager {
    
    /**
     * Adding a new license type to core table license
     * @param object $license {
     *            shortname => string a shortname of license, will be refered by files table[required]
     *            fullname  => string the fullname of the license [required]
     *            source => string the homepage of the license type[required]
     *            enabled => int is it enabled?
     *            version  => int a version number used by moodle [required]
     * }
     */
    static public function add($license) {
        global $DB;
        if ($record = $DB->get_record('license', array('shortname'=>$license->shortname))) {
            // record exists
            if ($record->version < $license->version) {
                // update license record
                $license->enabled = $record->enabled;
                $license->id = $record->id;
                $DB->update_record('license', $license);
            }
        } else {
            $DB->insert_record('license', $license);
        }
        return true;
    }

    /**
     * Get license records
     * @param mixed $param
     * @return array
     */
    static public function get_licenses($param = null) {
        global $DB;        
        if (empty($param) || !is_array($param)) {
            $paramuserl = array(); 
            $paramcorel = array();
        } else {   
            $paramuserl = $param;
            $paramcorel = $param;
        }
        if (!empty($param['userid'])) {
            unset($paramcorel['userid']);
        }
        if (!empty($param['enabled'])) {
            unset($paramuserl['enabled']);
        }        
        
        $recordsoutput = array();
        // get licenses by conditions
        if ($records = $DB->get_records('block_mbslicenseinfo_ul', $paramuserl)) {
            $recordsoutput = $records;
        }        
        if ($records = $DB->get_records('license', $paramcorel)) {
            foreach ($records as $record) {
                array_push($recordsoutput, $record);
            }
        } 
        
        return $recordsoutput;
    }
    
    /**
     * Get core license records
     * @param mixed $param
     * @return array
     */
    static public function get_core_licenses($param = null) {
        global $DB;
        if (empty($param) || !is_array($param)) {
            $param = array();
        }
        
        $recordsoutput = array();
        // get licenses by conditions
        if ($records = $DB->get_records('license', $param)) {
            $recordsoutput = $records;
        }        
        return $recordsoutput;
    }
    
    /**
     * Get core license records
     * @param mixed $param
     * @return array
     */
    static public function get_user_licenses($param = null) {
        global $DB;
        if (empty($param) || !is_array($param)) {
            $param = array();
        }
        
        $recordsoutput = array();
        // get licenses by conditions
        if ($records = $DB->get_records('block_mbslicenseinfo_ul', $param)) {
            $recordsoutput = $records;
        }         
        return $recordsoutput;
    }

    /**
     * Get license record by shortname
     * @param mixed $param the shortname of license, or an array
     * @return object
     */
    static public function get_license_by_shortname($name) {
        global $DB;
        if ($record = $DB->get_record('license', array('shortname'=>$name))) {
            $record->table = 'license';
            return $record;
        } else if ($record = $DB->get_record('block_mbslicenseinfo_ul', array('shortname'=>$name))) {
            $record->table = 'block_mbslicenseinfo_ul';
            return $record;
        } else {
            return null;
        }
    }

    /**
     * Enable a license
     * @param string $license the shortname of license
     * @return boolean
     */
    static public function enable($license) {
        global $DB;
        if ($license = self::get_license_by_shortname($license)) {
            $license->enabled = 1;
            $DB->update_record($license->table, $license);
        }
        self::set_active_licenses();
        return true;
    }

    /**
     * Disable a license
     * @param string $license the shortname of license
     * @return boolean
     */
    static public function disable($license) {
        global $DB, $CFG;
        // Site default license cannot be disabled!
        if ($license == $CFG->sitedefaultlicense) {
            print_error('error');
        }
        if ($license = self::get_license_by_shortname($license)) {
            $license->enabled = 0;
            $DB->update_record($license->table, $license);
        }
        self::set_active_licenses();
        return true;
    }

    /**
     * Store active licenses in global $CFG
     */
    static private function set_active_licenses() {
        // set to global $CFG
        $licenses = self::get_core_licenses(array('enabled'=>1));
        $result = array();
        foreach ($licenses as $l) {
            $result[] = $l->shortname;
        }
        set_config('licenses', implode(',', $result));
    }
    
    /**
     * Get single core license
     * 
     * @global $DB
     * @param array $param - parameters for where clause
     * @return object - database record
     */
    static public function get_core_license($param) {
        global $DB;
        return $DB->get_record('license', $param);
    }
    
    /**
     * Insert new core license
     * 
     * @global $DB
     * @param object $data - data object holding the values of the table row
     * @return bool|int - false or id of inserted record
     */
    static public function new_core_license($data) {
        global $DB;
        return $DB->insert_record('license', $data);
    }
    
    /**
     * remove core license
     * 
     * @global $DB
     * @param int $id
     * @return bool true
     */
    static public function remove_core_license($id) {
        global $DB;
        return $DB->delete_records('license', array('id' => $id));
    }
    
    /**
     * Get all shortnames of all used licenses
     * 
     * @global $DB
     * @return array
     */
    public static function get_all_used_shortnames() {
        global $DB;

        $allshortnames = array();

        $tables = array(
            'block_mbstpl_asset' => 'license',
            'block_mbstpl_meta' => 'license',
            'files' => 'license'
        );

        foreach ($tables as $table => $column) {
            $shortnames = $DB->get_records_sql_menu("SELECT id,$column FROM {{$table}}");
            $allshortnames = array_merge($allshortnames, array_values($shortnames));
        }

        return array_unique($allshortnames);
    }

    /**
     * Install new mebis build-in licenses
     */
    static public function install_licenses() {
        $active_licenses = array();

        $license = new \stdClass();

        $license->shortname = 'gpl3';
        $license->fullname = 'GNU GPL 3.0';
        $license->source = 'http://www.gnu.org/licenses/gpl-3.0.html';
        $license->enabled = 1;
        $license->version = '2015120900';
        $active_licenses[] = $license->shortname;
        self::add($license);

        $license->shortname = 'cc0';
        $license->fullname = 'CC0 1.0';
        $license->source = 'https://creativecommons.org/publicdomain/zero/1.0/deed.de';
        $license->enabled = 1;
        $license->version = '2015120900';
        $active_licenses[] = $license->shortname;
        self::add($license);

        $license->shortname = 'public';
        $license->fullname = 'Public Domain';
        $license->source = 'https://creativecommons.org/licenses/publicdomain/deed.de';
        $license->enabled = 1;
        $license->version = '2015120900';
        $active_licenses[] = $license->shortname;
        self::add($license);

        $license->shortname = 'cc';
        $license->fullname = 'CC BY 3.0';
        $license->source = 'https://creativecommons.org/licenses/by/3.0/de/';
        $license->enabled = 1;
        $license->version = '2015120900';
        $active_licenses[] = $license->shortname;
        self::add($license);

        $license->shortname = 'cc-nd';
        $license->fullname = 'CC BY-ND 3.0';
        $license->source = 'http://creativecommons.org/licenses/by-nd/3.0/de';
        $license->enabled = 1;
        $license->version = '2015120900';
        $active_licenses[] = $license->shortname;
        self::add($license);

        $license->shortname = 'cc-nc-nd';
        $license->fullname = 'CC BY-NC-ND 3.0';
        $license->source = 'http://creativecommons.org/licenses/by-nc-nd/3.0/de';
        $license->enabled = 1;
        $license->version = '2015120900';
        $active_licenses[] = $license->shortname;
        self::add($license);

        $license->shortname = 'cc-nc';
        $license->fullname = 'CC BY-NC 3.0';
        $license->source = 'http://creativecommons.org/licenses/by-nc/3.0/de';
        $license->enabled = 1;
        $license->version = '2015120900';
        $active_licenses[] = $license->shortname;
        self::add($license);

        $license->shortname = 'cc-nc-sa';
        $license->fullname = 'CC BY-NC-SA 3.0';
        $license->source = 'http://creativecommons.org/licenses/by-nc-sa/3.0/de';
        $license->enabled = 1;
        $license->version = '2015120900';
        $active_licenses[] = $license->shortname;
        self::add($license);

        $license->shortname = 'cc-sa';
        $license->fullname = 'CC-BY SA 3.0';
        $license->source = 'http://creativecommons.org/licenses/by-sa/3.0/de';
        $license->enabled = 1;
        $license->version = '2015120900';
        $active_licenses[] = $license->shortname;
        self::add($license);
        
        $license->shortname = 'lal';
        $license->fullname = 'Licence Art Libre';
        $license->source = 'http://artlibre.org/licence/lal/de/';
        $license->enabled = 1;
        $license->version = '2015120900';
        $active_licenses[] = $license->shortname;
        self::add($license);
        
        $license->shortname = 'gemeinfrei';
        $license->fullname = 'gemeinfrei (gemäß §§ 5, 64-69, 70, 72 UrhG)';
        $license->source = '';
        $license->enabled = 1;
        $license->version = '2015120900';
        $active_licenses[] = $license->shortname;
        self::add($license);

        set_config('licenses', implode(',', $active_licenses));
    }

}
