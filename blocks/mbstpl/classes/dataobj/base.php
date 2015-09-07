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
    public $fetched = false;
    public $noduplfields = array(); // Combination of fields that must not be duplicated (not including id).

    /**
     * We need to allow subclasses to declare the table name statically.
     * @param mixed $params array of params or int id
     * @param bool $fetch
     * @param int $strictness
     */
    public function __construct($params = null, $fetch = true, $strictness=IGNORE_MISSING) {
        $this->optional_fields['fetched'] = 0;
        $this->table = static::get_tablename();
        if (is_numeric($params)) {
            $params = array('id' => $params);
        }
        parent::__construct($params, $fetch);
        if ($fetch && $strictness == MUST_EXIST && !$this->fetched) {
            throw new \moodle_exception('invalidrecord', '', '', $this->table);
        }
    }

    /**
     * Set the table name here.
     * @return string
     */
    public static function get_tablename() {
        throw new \coding_exception('Must declare get_tablename().');
    }

    /**
     * Finds and returns a data_object instance based on params.
     *
     * @param array $params associative arrays varname=>value
     * @return data_object instance of data_object or false if none found.
     */
    public static function fetch($params) {
        $result = self::fetch_helper(static::get_tablename(), get_called_class(), $params);
        if ($result) {
            $result->fetched = true;
        }
        return $result;
    }

    /**
     * Update if already exists (found by matching $noduplfields), otherwise insert.
     * @return bool success
     */
    public function insertorupdate() {
        if (empty($this->noduplfields)) {
            return (bool)$this->insert();
        }
        if (!empty($this->id)) {
            return $this->update();
        }
        $params = array();
        foreach ($this->noduplfields as $fieldname) {
            if (is_null($this->{$fieldname})) {
                return (bool)$this->insert();
            }
            $params[$fieldname] = $this->{$fieldname};
        }
        if (!$found = static::fetch($params)) {
            return (bool)$this->insert();
        }
        $this->id = $found->id;
        return $this->update();
    }

	/**
     * Finds and returns all data_object instances based on params.
     *
     * @param array $params associative arrays varname => value
     * @throws coding_exception This function MUST be overridden
     * @return base[] array of data_object instances
     */
    public static function fetch_all($params) {
        if ($instances = self::fetch_all_helper(static::get_tablename(), get_called_class(), $params)) {
            return $instances;
        }
        return array();
	}

    /**
     * Get array of dependants. Extend in subclasses that have dependant tables in this namespace extending this class.
     * Use an array of classname=>foreignkey to define dependant tables that will be auto-deleted.
     * @return array
     */
    public static function get_dependants() {
        return array();
    }

    /**
     * Cleanup after change - delete dependants etc.
     *
     * @param bool $deleted Set this to true if it has been deleted.
     */
    public function notify_changed($deleted) {
        global $DB;

        if ($deleted) {
            foreach(static::get_dependants() as $classname => $key) {
                /** @var base $class */
                $class = __NAMESPACE__ . '\\' .$classname;
                if (!class_exists($class)) {
                    echo $class;
                    continue;
                }
                if (!is_subclass_of($class, get_class())) {
                    continue;
                }
                $subdependants = $class::get_dependants();
                if (empty($subdependants)) {
                    // Just delete them with one query, to minimise query calls.
                    $DB->delete_records($class::get_tablename(), array($key => $this->id));
                } else {
                    $dependants = $class::fetch_all(array($key => $this->id));
                    foreach($dependants as $dependant) {
                        $dependant->delete();
                    }
                }
            }
        }
    }
}