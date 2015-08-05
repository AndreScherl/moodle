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
        'feedback' => '',
        'feedbackformat' => FORMAT_MOODLE,
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

    /* @var string feedback  */
    public $feedback;

    /* @var int feedbackformat  */
    public $feedbackformat;

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
     * Finds and returns all data_object instances based on params.
     *
     * @param array $params associative arrays varname => value
     * @throws coding_exception This function MUST be overridden
     * @return array array of data_object instances or false if none found.
     */
    public static function fetch_all($params) {
        if ($instances = self::fetch_all_helper('block_mbstpl_template', __CLASS__, $params)) {
            return $instances;
        }
        return array();
    }

    /**
     * Fetch all records where any user column matches the id.
     * @param $userid
     * @return array array of data_object instances or false if none found.
     */
    public static function fetch_by_user($userid) {
        global $DB;

        $wheresql = "authorid = :authorid OR reviewerid = :reviewerid";
        $params = array('authorid' => $userid, 'reviewerid' => $userid);
        if ($datas = $DB->get_records_select(self::$table, $wheresql, $params)) {
            $result = array();
            foreach($datas as $data) {
                $instance = new self();
                self::set_properties($instance, $data);
                $result[$instance->id] = $instance;
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Updates this object in the Database, based on its object variables. ID must be set.
     *
     * @return bool success
     */
    public function update() {
        $this->timemodified = time();
        parent::update();
        $this->add_to_revhist();
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
        $this->add_to_revhist();
    }

    /**
     * Log this template change in revision history table.
     */
    private function add_to_revhist() {
        $assignedid = 0;
        if ($this->status == self::STATUS_UNDER_REVIEW) {
            $assignedid = $this->reviewerid;
        } else if ($this->status == self::STATUS_CREATED || $this->status == self::STATUS_UNDER_REVISION) {
            $assignedid = $this->authorid;
        }
        $params = array(
            'templateid' => $this->id,
            'status' => $this->status,
            'assignedid' => $assignedid,
            'feedback' => $this->feedback,
            'feedbackformat' => $this->feedbackformat,
            'timecreated' => time(),
        );
        $revhist = new revhist($params);
        $revhist->insert();
    }

}