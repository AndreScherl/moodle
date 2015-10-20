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

use context_course;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/completion/data_object.php');

class template extends base {

    const STATUS_CREATED = 0;
    const STATUS_UNDER_REVIEW = 1;
    const STATUS_UNDER_REVISION = 2;
    const STATUS_PUBLISHED = 3;
    const STATUS_ARCHIVED = 4;

    const FILEAREA = 'template';

    /**
     * Get the template associated with this course id. If there's no template with this course id,
     * get the template from which this course (id) was created. Otherwise return null.
     *
     * @return \block_mbstpl\dataobj\template
     */
    public static function get_from_course($courseid) {

        // Try to load a template from the course id - meaning the user is rating the template itself.
        $template = new template(array('courseid' => $courseid));
        if (!$template->fetched) {

            // Check to see if this course was created from a template, and load that template instead.
            $coursefromtpl = new coursefromtpl(array('courseid' => $courseid), true);
            if ($coursefromtpl->templateid) {
                $template = new template($coursefromtpl->templateid);
            }
        }

        return $template->fetched ? $template : null;
    }

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
        'rating' => null,
        'reminded' => 0,
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

    /* @var float rating  */
    public $rating;

    /* @var int reminded  */
    public $reminded;

    /**
     * Set the table name here.
     * @return string
     */
    public static function get_tablename() {
        return 'block_mbstpl_template';
    }

    /**
     * Get array of dependants.
     * @return array
     */
    public static function get_dependants() {
        return array(
            'coursefromtpl' => 'templateid',
            'meta' => 'templateid',
            'revhist' => 'templateid',
            'starrating' => 'templateid',
        );
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
     * Updates rating without adding to revision history or changing timemodified.
     *
     * @return bool success
     */
    public function update_notouch() {
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
        $this->add_to_revhist();

        $meta = new meta(array('templateid' => $this->id));
        $meta->insert();
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
        $revhist = new revhist($params, false);
        $revhist->insert();

        // Copy any attached files.
        $context = context_course::instance($this->courseid);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'block_mbstpl', self::FILEAREA, $this->id, '', false);
        foreach ($files as $file) {
            $filerecord = (object)array(
                'filearea' => revhist::FILEAREA,
                'itemid' => $revhist->id,
            );
            $fs->create_file_from_storedfile($filerecord, $file);
        }
    }

    public function get_files() {
        $fs = get_file_storage();
        $context = context_course::instance($this->courseid);
        return $fs->get_area_files($context->id, 'block_mbstpl', self::FILEAREA, $this->id);
    }
}
