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
 * Class revhist
 * For block_mbstpl_revhist.
 * @package block_mbstpl
 */
namespace block_mbstpl\dataobj;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/completion/data_object.php');

class revhist extends \data_object {

    /* @var string Database table name that stores completion criteria information  */
    public $table = 'block_mbstpl_revhist';

    /**
     * Array of required table fields, must start with 'id'.
     * Defaults to id, course, criteriatype, module, moduleinstane, courseinstance,
     * enrolperiod, timeend, gradepass, role
     * @var array
     */
    public $required_fields = array('id', 'templateid', 'status');
    public $optional_fields = array(
        'assignedid' => 0,
        'feedback' => '',
        'feedbackformat' => FORMAT_MOODLE,
        'timecreated' => 0,
    );

    /* @var int templateid  */
    public $templateid;

    /* @var int status  */
    public $status;

    /* @var int authorid  */
    public $authorid;

    /* @var int assignedid  */
    public $assignedid;

    /* @var string feedback  */
    public $feedback;

    /* @var int feedbackformat  */
    public $feedbackformat;

    /* @var int timecreated  */
    public $timecreated;

    /**
     * Finds and returns a data_object instance based on params.
     *
     * @param array $params associative arrays varname=>value
     * @return data_object instance of data_object or false if none found.
     */
    public static function fetch($params) {
        return self::fetch_helper('block_mbstpl_revhist', __CLASS__, $params);
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
        parent::insert();
    }
}