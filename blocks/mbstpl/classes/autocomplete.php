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
 *
 * @package block_mbstpl
 * @copyright 2015 Bence Laky <b.laky@intrallect.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_mbstpl;

defined('MOODLE_INTERNAL') || die();

use \block_mbstpl\dataobj\template;


class autocomplete {

    const MAXLENGTH = 27;

    /**
     * Get keyword suggestions for the autocomplete by fieldname
     * @param $fieldname
     * @param $keyword
     * @return array
     */
    public function get_suggestions($fieldname, $keyword) {
        global $DB;

        // Escape the keyword.
        $keyword = $DB->sql_like_escape($keyword);
        $likekeyword = "%{$keyword}%";

        if ($fieldname == 'tag') {
            return $this->get_tag_suggestions($likekeyword);
        } else if ($fieldname == 'author') {
            return $this->get_author_suggestions($likekeyword);
        } else if ($fieldname == 'coursename') {
            return $this->get_course_suggestions($likekeyword);
        } else if (substr($fieldname, 0, 2) == 'q_') {
            return $this->get_customq_suggestions($fieldname, $likekeyword);
        }
        print_error('incorrectfieldname', 'block_mbstpl');
    }

    /**
     * Provide course name suggestions (can be in shortname, longname or idnumber).
     *
     * @param string $likekeyword pre-escaped and %%ed for like
     * @return array suggestions
     */
    private function get_course_suggestions($likekeyword) {
        global $DB;

        $sql = "
        SELECT DISTINCT(c.fullname)
        FROM {course} c
        JOIN {block_mbstpl_template} tpl ON tpl.courseid = c.id
        WHERE tpl.status = :status
        AND (
             ".$DB->sql_like('c.fullname', ':kw1', false)."
          OR ".$DB->sql_like('c.shortname', ':kw2', false)."
          OR ".$DB->sql_like('c.idnumber', ':kw3', false)."
        )
        ORDER BY c.fullname ASC
        ";
        $params = array(
            'status' => template::STATUS_PUBLISHED,
            'kw1' => $likekeyword,
            'kw2' => $likekeyword,
            'kw3' => $likekeyword,
        );
        $results = $DB->get_records_sql_menu($sql, $params);
        return array_keys($results);
    }

    /**
     * Provide tag suggestions.
     *
     * @param string $likekeyword pre-escaped and %%ed for like
     * @return array suggestions
     */
    private function get_tag_suggestions($likekeyword) {
        global $DB;

        $sql = "
        SELECT DISTINCT (tag)
        FROM {block_mbstpl_tag}
        WHERE ".$DB->sql_like('tag', ':kw', false)."
        ORDER BY tag ASC
        ";
        $params = array('kw' => $likekeyword);
        $results = $DB->get_records_sql_menu($sql, $params);
        return array_keys($results);
    }


    /**
     * Provide author suggestions.
     *
     * @param string $likekeyword pre-escaped and %%ed for like
     * @return array suggestions
     */
    private function get_author_suggestions($likekeyword) {
        global $DB;

        $authnamefield = $DB->sql_fullname('au.firstname', 'au.lastname');

        $sql = "
        SELECT DISTINCT ($authnamefield) author
        FROM {block_mbstpl_template} tpl
        JOIN {user} au ON au.id = tpl.authorid
        WHERE tpl.status = :status
        AND ".$DB->sql_like($authnamefield, ':kw', false)."
        ORDER BY author ASC
        ";
        $params = array(
            'status' => template::STATUS_PUBLISHED,
            'kw' => $likekeyword,
        );
        $results = $DB->get_records_sql_menu($sql, $params);
        return array_keys($results);
    }


    /**
     * Provide suggestions from a custom text field question.
     *
     * @param string $fieldname
     * @param string $likekeyword pre-escaped and %%ed for like
     * @return array suggestions
     */
    private function get_customq_suggestions($fieldname, $likekeyword) {
        global $DB;

        $fieldexp = explode('_', $fieldname);
        if (empty($fieldexp[1])) {
            print_error('incorrectfieldname', 'block_mbstpl');
        }
        $qid = (int)$fieldexp[1];
        if (empty($qid)) {
            print_error('incorrectfieldname', 'block_mbstpl');
        }

        $sql = "
        SELECT DISTINCT(".$DB->sql_substr('ans.datakeyword', 1, self::MAXLENGTH).")
        FROM {block_mbstpl_template} tpl
        JOIN {block_mbstpl_meta} mta ON mta.templateid = tpl.id
        JOIN {block_mbstpl_answer} ans ON ans.metaid = mta.id
        WHERE tpl.status = :status
        AND ans.questionid = :qid
        AND ".$DB->sql_like('ans.datakeyword', ':kw', false)."
        ORDER BY datakeyword ASC
        ";
        $params = array(
            'status' => template::STATUS_PUBLISHED,
            'qid' => $qid,
            'kw' => $likekeyword,
        );
        $results = $DB->get_records_sql_menu($sql, $params);
        return array_keys($results);
    }
}