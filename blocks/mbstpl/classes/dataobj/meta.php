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
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class meta
 * For block_mbstpl_meta.
 * @package block_mbstpl
 */
namespace block_mbstpl\dataobj;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/completion/data_object.php');

class meta extends base {

    /**
     * Array of required table fields, must start with 'id'.
     * @var array
     */
    public $required_fields = array('id');
    public $optional_fields = array(
        'backupid' => 0,
        'templateid' => 0,
    );

    /* @var int backupid  */
    public $backupid;

    /* @var int templateid  */
    public $templateid;

    /**
     * Set the table name here.
     * @return string
     */
    public static function get_tablename() {
        return 'block_mbstpl_meta';
    }

    /**
     * Get array of dependants.
     * @return array
     */
    public static function get_dependants() {
        return array('answer' => 'metaid');
    }


    /**
     * Records this object in the Database, sets its id to the returned value, and returns that value.
     * If successful this function also fetches the new object data from database and stores it
     * in object properties.
     *
     * @return int PK ID if successful, false otherwise
     */
    public function insert() {
        if (!($this->backupid xor $this->templateid)) {
            throw new \coding_exception('Meta needs to be linked to either a backup or a template.');
        }
        parent::insert();
    }
}