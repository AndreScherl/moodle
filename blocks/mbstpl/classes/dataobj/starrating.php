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
 * Class starrating
 * For block_mbstpl_template.
 * @package block_mbstpl
 */
class starrating extends base {

    public $required_fields = array('id', 'templateid', 'userid', 'rating');
    public $optional_fields = array('comment' => '', 'timecreated' => 0);
    public $noduplfields = array('templateid', 'userid');

    /* @var int templateid  */
    public $templateid;

    /* @var int userid */
    public $userid;

    /* @var int rating */
    public $rating;

    /* @var string comment  */
    public $comment;

    /* @var int timecreated  */
    public $timecreated;

    /**
     * Set the table name here.
     * @return string
     */
    public static function get_tablename() {
        return 'block_mbstpl_starrating';
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
}
