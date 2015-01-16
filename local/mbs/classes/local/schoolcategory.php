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
 * main class schoolcategory
 * 
 * a school is a category of moodle with a specific depth
 *
 * @package    local_mbs
 * @copyright  Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mbs\local;

class schoolcategory {

    public static $schoolcatdepth = 3;

    /** get the category of the schoolcategory (i. e. the parent category of given
     * category with the depth value of $schoolcatdepth.
     * 
     * @param type $categoryid
     */
    public static function get_schoolcategory($categoryid) {
        global $DB;

        $sql = "SELECT sch.* FROM {course_categories} ca
                JOIN {course_categories} sch ON sch.id = REPLACE(SUBSTRING_INDEX(REPLACE(ca.path, SUBSTRING_INDEX(ca.path,'/',:depth),''), '/',2),'/','')
                WHERE ca.id = :catid ";

        return $DB->get_record_sql($sql, array('depth' => self::$schoolcatdepth, 'catid' => $categoryid));
    }

}