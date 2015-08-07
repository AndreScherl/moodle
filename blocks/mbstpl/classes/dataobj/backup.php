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
 * Class backup
 * For block_mbstpl_backup.
 * @package block_mbstpl
 */
namespace block_mbstpl\dataobj;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/completion/data_object.php');

class backup extends base {

    /**
     * Array of required table fields, must start with 'id'.
     * Defaults to id, course, criteriatype, module, moduleinstane, courseinstance,
     * enrolperiod, timeend, gradepass, role
     * @var array
     */
    public $required_fields = array('id', 'origcourseid', 'backupid', 'qformid');
    public $optional_fields = array(
        'creatorid' => 0,
        'incluserdata' => 1,
        'lastverstion' => 0,
    );

    /* @var int origcourseid id  */
    public $origcourseid;

    /* @var int backupid  */
    public $backupid;

    /* @var int creatorid  */
    public $creatorid;

    /* @var int qformid id  */
    public $qformid;

    /* @var int incluserdata  */
    public $incluserdata;

    /* @var int lastverstion  */
    public $lastverstion;

    /* @var string feedback  */
    public $feedback;

    /* @var int timecreated  */
    public $timecreated;

    /* @var int timemodified  */
    public $timemodified;

    /**
     * Set the table name here.
     * @return string
     */
    public static function get_tablename() {
        return 'block_mbstpl_backup';
    }

    /**
     * Get array of dependants.
     * @return array
     */
    public static function get_dependants() {
        return array('meta' => 'backupid');
    }


    /**
     * Updates this object in the Database, based on its object variables. ID must be set.
     *
     * @return bool success
     */
    public function update() {
        $this->timemodified = time();
        parent::update();
    }

    /**
     * Records this object in the Database, sets its id to the returned value, and returns that value.
     * If successful this function also fetches the new object data from database and stores it
     * in object properties.
     *
     * @return int PK ID if successful, false otherwise
     */
    public function insert() {
        $this->timecreated = time();
        $this->timemodified = time();
        parent::insert();
    }
}