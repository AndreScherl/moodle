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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package block_mbstpl
 * @copyright 2015 Bence Laky <b.laky@intrallect.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_mbstpl;

defined('MOODLE_INTERNAL') || die();

use \block_mbstpl\dataobj\template;

define('FILTER_REGEX', "/^q[0-9]/");

class search {

    /**
     * Create a WHERE clause part fragments on the data that has been posted from the drop down
     * filter options.
     *
     * @param \stdClass $formdata
     *
     * @return array
     */
    private function create_filter_criteria($formdata) {
        if ($formdata) {
            $filtercriteria = array();
            $formdata = get_object_vars($formdata);
            if ($formdata) {
                // If there has been data posted to the forms, find the data from the dropdown filters
                foreach (array_keys($formdata) as $settingkey) {
                    if (preg_match(FILTER_REGEX, $settingkey)) {
                        $questionid = intval(substr($settingkey, 1));
                        $value = required_param($settingkey, PARAM_ALPHANUM);

                        // Add filters to the search criteria.
                        if (!is_null($value) && strlen($value) > 0) {
                            $filtercriteria[] = "(questionid = {$questionid} AND data = {$value})";
                        }
                    }
                }
            }

            return $filtercriteria;
        } else {
            return array();
        }
    }

    /**
     * Create WHERE clause fragment for free text search.
     *
     * @return string
     */
    private function create_freetext_criteria() {
        global $DB;

        $sql = ' AND (';
        $sql .= '(' . $DB->sql_like('C.fullname', '?', false) . ')';
        $sql .= ' OR (' . $DB->sql_like('C.idnumber', '?', false) . ')';
        $sql .= ' OR (' . $DB->sql_like('C.shortname', '?', false) . ')';
        $sql .= ' OR (' . $DB->sql_like('A.data', '?', false) . ')';
        $sql .= ')';

        return $sql;
    }

    /**
     * Provide a list of courses that matches the criteria submitted from the search page.
     *
     * @param \stdClass $formdata
     * @param int $startrecord
     * @param int $pagesize
     *
     * @return array list of courses matching the search filters
     */
    public function get_search_result($formdata, $startrecord, $pagesize) {
        global $DB;

        $filtercriteria = $this->create_filter_criteria($formdata);
        $queryparams = array();

        // Set the base of the query.
        $sql = 'SELECT C.* FROM {block_mbstpl_answer} AS A';
        $sql .= ' JOIN {block_mbstpl_meta} as M ON M.id = A.metaid ';
        $sql .= ' JOIN {block_mbstpl_template} as T on M.templateid = T.id';
        $sql .= ' JOIN {course} as C on T.courseid = C.id';
        $sql .= ' WHERE T.status = ? ';
        $queryparams[] = template::STATUS_PUBLISHED;
        if (count($filtercriteria) > 0) {
            $sql .= ' AND (' . join(' OR ', $filtercriteria) . ')';
        }

        if (isset($formdata->keyword) && $formdata->keyword) {
            $sql .= $this->create_freetext_criteria();
            $keyword = '%' . $DB->sql_like_escape(required_param("keyword", PARAM_TEXT)) . '%';
            array_push($queryparams, $keyword, $keyword, $keyword, $keyword);
        }

        $sql .= ' GROUP BY metaid';

        // Append it if there was any filter criteria.
        if (count($filtercriteria) > 0) {
            $sql .= ' HAVING count(metaid) = ?';
            $queryparams[] = count($filtercriteria);
        }

        return $DB->get_records_sql($sql, $queryparams, $startrecord, $pagesize);
    }
}