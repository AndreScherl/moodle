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

class search {

    /* @var array questions  */
    private $questions;

    /* @var array answers  */
    private $answers;

    /**
     * @param array $questions
     * @param \stdClass $formdata
     */
    function __construct($questions, $formdata) {
        $this->questions = $questions;
        $this->answers = $this->formdata_to_answers($formdata);
    }

    /**
     * Turns form data into an array of question id => answer (answer might be an array).
     * @param $formdata
     */
    private function formdata_to_answers($formdata) {
        $answers = array();
        foreach($formdata as $key => $answer) {
            if (empty($answer)) {
                continue;
            }
            $keys = explode('_', $key);
            if (count($keys) < 2) {
                continue;
            }
            if ($keys[0] != 'q') {
                continue;
            }
            $qid = $keys[1];
            if (count($keys) > 2) {
                $qparam = $keys[2];
                if (!isset($answers[$qid])) {
                    $answers[$qid] = array();
                }
                $answers[$qid][$qparam] = $answer;
                continue;
            }
            $answers[$qid] = $answer;
        }
        return $answers;
    }

    /**
     * Provide a list of courses that matches the criteria submitted from the search page.
     *
     * @param int $startrecord
     * @param int $pagesize
     *
     * @return array list of courses matching the search filters
     */
    public function get_search_result($startrecord, $pagesize) {
        global $DB;

        $wheres = array();
        $params = array();

        foreach($this->answers as $qid => $answer) {
            if (!isset($this->questions[$qid])) {
                continue;
            }
            $question = $this->questions[$qid];
            $typeclass = \block_mbstpl\questman\qtype_base::qtype_factory($question->datatype);
            $toadd = $typeclass->get_query_filters($question, $answer);
            $wheres = array_merge($wheres, $toadd['wheres']);
            $params = array_merge($params, $toadd['params']);
        }
        $wheres[] = 'tpl.status = :stpublished';
        $filterwheres = implode("\n          AND ", $wheres);

        $selectsql = "
        SELECT c.id, c.fullname, cat.name AS catname
        ";

        $coresql = "
        FROM {course} c
        JOIN {course_categories} cat ON cat.id = c.category
        JOIN {block_mbstpl_template} tpl ON tpl.courseid = c.id
        JOIN {block_mbstpl_meta} mta ON mta.templateid = tpl.id
        WHERE $filterwheres
        ";

        $params['stpublished'] = template::STATUS_PUBLISHED;

        $sql = "
        $selectsql
        $coresql
        ";

        $results = $DB->get_records_sql($sql, $params);
        return $results;
    }
}