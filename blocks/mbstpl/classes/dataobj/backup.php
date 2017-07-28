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
        'lastversion' => 0,
        'userdataids' => null,
        'excludedeploydataids' => null,
        'timecreated' => 0,
        'timemodified' => 0
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

    /* @var int lastversion  */
    public $lastversion;

    /* @var string feedback  */
    public $feedback;

    /* @var int timecreated  */
    public $timecreated;

    /* @var int timemodified  */
    public $timemodified;

    /* @var string */
    public $userdataids;

    /* @var string */
    public $excludedeploydataids;

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

        $meta = new meta(array('backupid' => $this->id));
        $meta->insert();
        return $this->id;
    }

    /**
     * @param int[]|null $userdataids
     */
    public function set_userdata_ids($userdataids) {
        if ($userdataids === null) {
            $this->userdataids = null;
        } else {
            $this->userdataids = implode(',', $userdataids);
        }
    }

    /**
     * @return int[]|null
     */
    public function get_userdata_ids() {
        if ($this->userdataids === null) {
            return null;
        }
        if (!trim($this->userdataids)) {
            return array();
        }
        return explode(',', $this->userdataids);
    }

    /**
     * @param int[]|null $excludedeploydataids
     */
    public function set_exclude_deploydata_ids($excludedeploydataids) {
        if ($excludedeploydataids === null) {
            $this->excludedeploydataids = null;
        } else {
            $this->excludedeploydataids = implode(',', $excludedeploydataids);
        }
    }

    /**
     * @return int[]|null
     */
    public function get_exclude_deploydata_ids() {
        if (!trim($this->excludedeploydataids)) {
            return array();
        }
        return explode(',', $this->excludedeploydataids);
    }

    /**
     * Get information about the creator of the backup. When the user is deleted,
     * we hopefully have information about the first- and lastname of deleted
     * user.
     *
     *  @return object information object to describe the creator of backup.
     */
    private function get_creator_info() {
        global $DB;

        // Get creator.
        $sql = "SELECT u.*, ud.firstname as udfirstname, ud.lastname as udlastname
                FROM {user} u
                LEFT JOIN {block_mbstpl_userdeleted} ud ON u.id = ud.userid
                WHERE u.id = ?";

        $userdata = $DB->get_record_sql($sql, array($this->creatorid));

        return $userdata;
    }

    /**
     * Get informations about this backup.
     *
     * @return \stdClass information object for the backup.
     */
    public function get_backup_info() {
        global $DB;

        $info = new \stdClass();
        $info->origcourseid = $this->origcourseid;
        $info->origcourse = $DB->get_record('course', array('id' => $this->origcourseid));
        $info->creator = $this->get_creator_info();
        $info->includeuserdata = $this->incluserdata;
        $info->userdataids = $this->userdataids;
        $info->lastversion = $this->lastversion;

        return $info;
    }

}
