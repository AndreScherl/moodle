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
 * report pimped courses (style and js customisations using html - block)
 * settings.
 *
 * @package    report
 * @subpackage mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagern@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_mbs\local;

class reportpimped {

    /** check whether the text contains at least one of the given needles.
     * For performance reasons we use strpos here, it would be easy to change that 
     * into a preg match search.
     * 
     * @param array $needles list of needles to search for
     * @param string $text
     * @return boolean true if at least one needle is found
     */
    protected static function matches($needles, $text) {

        foreach ($needles as $needle) {

            if (empty($needle)) {
                continue;
            }

            if (strpos($text, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /** search for block instances containing given searchpattern
     * 
     * @global object $DB
     * @param array $baseparams params containing the searchpattern
     * @return array list of block instances
     */
    protected static function get_pimped_blocks($baseparams) {
        global $DB;

        if (!$htmlblocks = $DB->get_records('block_instances', array('blockname' => 'html'))) {
            return array();
        }

        $patterns = explode('|', $baseparams['searchpattern']);

        // Pimped blocks group by parentcontextid.
        $pimpedblocks = array();
        foreach ($htmlblocks as $htmlblock) {

            $blockconfig = unserialize(base64_decode($htmlblock->configdata));

            if (empty($blockconfig->text)) {
                continue;
            }

            if ($needle = self::matches($patterns, $blockconfig->text)) {

                if (!isset($pimpedblocks[$htmlblock->parentcontextid])) {
                    $pimpedblocks[$htmlblock->parentcontextid] = array();
                }
                $pimpedblocks[$htmlblock->parentcontextid][] = s($blockconfig->text);
            }
        }
        return $pimpedblocks;
    }

    /** retreive all the trainers (i. e. users with given capabitlity) for courses.
     * 
     * @global object $DB
     * @param array $courseids list of courses
     * @return array list containing userinfo for trainers indexed by courseid.
     */
    protected static function get_trainers($courseids, $capability = 'moodle/role:assign') {
        global $DB;

        $params = array(CONTEXT_COURSE);

        list($incourseids, $incourseparams) = $DB->get_in_or_equal($courseids);
        $params = array_merge($params, $incourseparams);

        $trainerroles = get_roles_with_capability($capability);
        list($inroleids, $inroleparams) = $DB->get_in_or_equal(array_keys($trainerroles));
        $params = array_merge($params, $inroleparams);

        $allnames = get_all_user_name_fields(true, "u");

        $sql = "SELECT ra.id as raid, ra.roleid as roleid, ctx.instanceid as courseid, u.id as userid, $allnames, u.email
                FROM {user} u
                JOIN {role_assignments} ra ON ra.userid = u.id
                JOIN {context} ctx ON ctx.id = ra.contextid
                WHERE ctx.contextlevel = ? AND ctx.instanceid $incourseids AND ra.roleid $inroleids";

        if (!$results = $DB->get_records_sql($sql, $params)) {
            $results = array();
        }

        // Group Trainers by courses.
        $groupedtrainers = array();

        foreach ($results as $result) {
            if (!isset($groupedtrainers[$result->courseid])) {
                $groupedtrainers[$result->courseid][$result->userid] = $result;
            }
        }
        return $groupedtrainers;
    }

    /** retreive all the coordinators (i. e. users with given capability) for categories.
     * 
     * @global object $DB
     * @param array $schoolids list of ids fo schools
     * @return array list containing userinfo for coordinators indexed by schoolid
     */
    public static function get_coordinators($schoolids, $capability = 'moodle/category:manage') {
        global $DB;

        $params = array(CONTEXT_COURSECAT);
        list($incatids, $incatparams) = $DB->get_in_or_equal($schoolids);

        $params = array_merge($params, $incatparams);

        $mebiscoordinator = get_roles_with_capability($capability);

        list($inroleids, $inroleparams) = $DB->get_in_or_equal(array_keys($mebiscoordinator));
        $params = array_merge($params, $inroleparams);

        $allnames = get_all_user_name_fields(true, "u");

        $sql = "SELECT ra.id, ra.roleid as roleid, ctx.instanceid as schoolid, u.id as userid, $allnames, u.email
                FROM {user} u
                JOIN {role_assignments} ra ON ra.userid = u.id
                JOIN {context} ctx ON ctx.id = ra.contextid
                WHERE ctx.contextlevel = ? AND ctx.instanceid $incatids AND ra.roleid $inroleids";

        if (!$results = $DB->get_records_sql($sql, $params)) {
            $results = array();
        }

        $groupedcoordinators = array();
        foreach ($results as $result) {
            if (!isset($groupedcoordinators[$result->schoolid])) {
                $groupedcoordinators[$result->schoolid][$result->id] = $result;
            }
        }
        return $groupedcoordinators;
    }

    /** get all the reports data by:
     *  1. search for pimped blocks
     *  2. Getting courses containing these blocks
     *  3. Adding information for trainers, school and coordinators
     * 
     * @global object $DB
     * @param array $baseparams
     * @return array list of stats data indexed by courseid
     */
    public static function get_reports_data($baseparams) {
        global $DB;

        // Get all pimped blocks.
        // We expect a low number as result here, so add informations about teachers, schoolcatgory
        // in a second step.
        $pimpedblocks = self::get_pimped_blocks($baseparams);

        if (empty($pimpedblocks)) {
            return array();
        }

        // Retrieve additional informations.
        // Get courses.

        list($incontexts, $inparams) = $DB->get_in_or_equal(array_keys($pimpedblocks));

        $sql = "SELECT DISTINCT c.id, c.fullname as coursename, c.category, ctx.id as contextid
                FROM {course} c
                JOIN {context} ctx ON ctx.instanceid = c.id
                WHERE ctx.id {$incontexts} ORDER BY c.id";

        if (!$courses = $DB->get_records_sql($sql, $inparams)) {
            $courses = array();
        }

        // Get Trainers (courseid => list of trainers).
        $trainers = self::get_trainers(array_keys($courses));

        // Get Schools ($course->category => $school).
        $categoryids = array();
        foreach ($courses as $course) {
            $categoryids[$course->category] = $course->category;
        }

        $schools = \local_mbs\local\schoolcategory::get_schoolcategories($categoryids);
        $schoolids = array();
        foreach ($schools as $school) {
            $schoolids[] = $school->id;
        }

        // Get mebis - coordinators.
        $coordinators = self::get_coordinators($schoolids);

        foreach ($courses as $course) {

            if (isset($trainers[$course->id])) {
                $course->trainers = $trainers[$course->id];
            }

            if (isset($schools[$course->category])) {
                $course->school = $schools[$course->category];
            }

            if (isset($coordinators[$course->school->id])) {
                $course->coordinators = $coordinators[$course->school->id];
            }

            if (isset($pimpedblocks[$course->contextid])) {
                $course->blockscontent = $pimpedblocks[$course->contextid];
            }
        }

        return $courses;
    }

}