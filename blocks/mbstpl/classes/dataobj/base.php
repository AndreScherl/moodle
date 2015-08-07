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

abstract class base extends \data_object {

    public $table = '';

    /**
     * We need to allow subclasses to declare the table name statically.
     * @param null $params
     * @param bool $fetch
     */
    public function __construct($params = null, $fetch = true) {
        $this->table = static::$tablename;
        parent::__construct($params, $fetch);
    }

    /**
     * Finds and returns a data_object instance based on params.
     *
     * @param array $params associative arrays varname=>value
     * @return data_object instance of data_object or false if none found.
     */
    public static function fetch($params) {
        return self::fetch_helper(static::$tablename, get_called_class(), $params);
    }

	/**
     * Finds and returns all data_object instances based on params.
     *
     * This function MUST be overridden by all deriving classes.
     *
     * @param array $params associative arrays varname => value
     * @throws coding_exception This function MUST be overridden
     * @return array array of data_object instances or false if none found.
     */
    public static function fetch_all($params) {
        if ($instances = self::fetch_all_helper(static::$tablename, get_called_class(), $params)) {
            return $instances;
        }
		return array();
	}
}