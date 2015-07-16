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
 * Class template
 * For block_mbstpl_template.
 * @package block_mbstpl
 */
namespace block_mbstpl\dataobj;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/completion/data_object.php');

class template extends \data_object {

    const STATUS_CREATED = 0;
    const STATUS_UNDER_REVIEW = 1;
    const STATUS_UNDER_REVISION = 2;
    const STATUS_PUBLISHED = 3;
    const STATUS_ARCHIVED = 4;

    /* @var string Database table name that stores completion criteria information  */
    public $table = 'block_mbstpl_template';

    /**
     * Array of required table fields, must start with 'id'.
     * Defaults to id, course, criteriatype, module, moduleinstane, courseinstance,
     * enrolperiod, timeend, gradepass, role
     * @var array
     */
    public $required_fields = array('id', 'courseid', 'backupid', 'authorid');
    public $optional_fields = array(
        'reviewerid' => 0,
        'status' => self::STATUS_CREATED,
        'timemodified' => 0,
    );

    /* @var int Course id  */
    public $courseid;

    /* @var int backupid  */
    public $backupid;

    /* @var int authorid  */
    public $authorid;

    /* @var int reviewer id  */
    public $reviewerid;

    /* @var int status  */
    public $status;

    /* @var int timemodified  */
    public $timemodified;

    /**
     * Finds and returns a data_object instance based on params.
     *
     * @param array $params associative arrays varname=>value
     * @return data_object instance of data_object or false if none found.
     */
    public static function fetch($params) {
        return self::fetch_helper('block_mbstpl_template', __CLASS__, $params);
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
        $this->timemodified = time();
        parent::insert();
    }
}