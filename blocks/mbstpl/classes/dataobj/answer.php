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
 * Class answer
 * For block_mbstpl_answer.
 * @package block_mbstpl
 */
namespace block_mbstpl\dataobj;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/completion/data_object.php');

class answer extends base {

    /**
     * Array of required table fields, must start with 'id'.
     * Defaults to id, course, criteriatype, module, moduleinstane, courseinstance,
     * enrolperiod, timeend, gradepass, role
     * @var array
     */
    public $required_fields = array('id', 'metaid', 'questionid');
    public $optional_fields = array(
        'data' => '',
        'dataformat' => FORMAT_MOODLE,
        'datakeyword' => '',
        'comment' => '',
    );
    public $noduplfields = array('metaid', 'questionid');

    /**
     * Set the table name here.
     * @return string
     */
    public static function get_tablename() {
        return 'block_mbstpl_answer';
    }

    /* @var int metaid  */
    public $metaid;

    /* @var int questionid  */
    public $questionid;

    /* @var string data  */
    public $data;

    /* @var int dataformat  */
    public $dataformat;

    /* @var string datakeyword  */
    public $datakeyword;

    /** @var string $comment */
    public $comment;

    public function insert() {
        $this->datakeyword = substr($this->data, 0, 254);
        parent::insert();
    }

    public function update() {
        $this->datakeyword = substr($this->data, 0, 254);
        parent::update();
    }
}