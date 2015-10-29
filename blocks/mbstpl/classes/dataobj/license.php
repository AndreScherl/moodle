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
 * @package block_mbstpl
 * @copyright 2015 Janek Lasocki-Biczysko, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\dataobj;

defined('MOODLE_INTERNAL') || die();

/**
 * Class license
 *
 * @package block_mbstpl
 */
class license extends base {

    public static function fetch_all_used_shortnames() {
        global $DB;

        $allshortnames = array();

        $tables = array(
            asset::get_tablename() => 'license',
            meta::get_tablename() => 'license'
        );

        foreach ($tables as $table => $column) {
            $shortnames = $DB->get_records_sql_menu("SELECT id,$column FROM {{$table}}");
            $allshortnames = array_merge($allshortnames, array_values($shortnames));
        }

        return array_unique($allshortnames);
    }

    /**
     * @param \block_mbstpl\dataobj\asset[] $assets
     * @return \block_mbstpl\dataobj\license[]
     */
    public static function fetch_all_mapped_by_shortname($assets) {

        $shortnames = array_map(function($asset) {
            return $asset->license;
        }, $assets);

        $licenses = array();
        foreach (array_unique($shortnames) as $shortname) {
            $license = self::fetch(array('shortname' => $shortname));
            if ($license) {
                $licenses[$shortname] = $license;
            }
        }

        return $licenses;
    }

    public $required_fields = array('id', 'shortname', 'fullname', 'source');
    public $noduplfields = array('shortname');

    /* @var $shortname string */
    public $shortname;

    /* @var $fullname string */
    public $fullname;

    /* @var $source string */
    public $source;

    /**
     * Set the table name here.
     * @return string
     */
    public static function get_tablename() {
        return 'block_mbstpl_license';
    }
}
