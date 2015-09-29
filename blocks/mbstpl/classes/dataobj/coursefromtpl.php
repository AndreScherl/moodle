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
 * Class coursefromtpl
 * For block_mbstpl_course from template.
 * @package block_mbstpl
 */
class coursefromtpl extends base {

    public $required_fields = array('id', 'courseid', 'templateid');
    public $noduplfields = array('courseid');
    public $optional_fields = array(
        'createdby' => null,
        'createdon' => null
    );

    /* @var int courseid  */
    public $courseid;

    /* @var int templateid */
    public $templateid;

    /* @var int createdby */
    public $createdby;

    /* @var int createdon */
    public $createdon;

    /**
     * Set the table name here.
     * @return string
     */
    public static function get_tablename() {
        return 'block_mbstpl_coursefromtpl';
    }

    public function insert() {
        $this->createdon = time();
        return parent::insert();
    }
}
