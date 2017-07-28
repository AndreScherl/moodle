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

class revhist extends base {

    const FILEAREA = 'revhist';

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
     * Set the table name here.
     * @return string
     */
    public static function get_tablename() {
        return 'block_mbstpl_revhist';
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
        return parent::insert();
    }

    /**
     * Get a list of files that were uploaded when this revision was created.
     * @param \context_course $context
     * @return \stored_file[]
     */
    public function get_files(\context_course $context) {
        $fs = get_file_storage();
        return $fs->get_area_files($context->id, 'block_mbstpl', self::FILEAREA, $this->id, 'filepath, filename', false);
    }

    /**
     * Returns the full name of the user who was assigned at this point in the
     * revision history
     * WARNING: this uses a DB query to find the user - do not use this function
     * if it is going to be called a lot of times.
     *
     * @return string
     */
    public function get_assigned_name() {
        global $DB;
        if (!$this->assignedid) {
            return '';
        }
        if (!$user = $DB->get_record('user', array('id' => $this->assignedid), get_all_user_name_fields(true))) {
            return '';
        }
        return fullname($user);
    }
}