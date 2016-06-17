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

class tplsearch {
    /* @var array questions  */
    private $questions;

    /* @var array answers  */
    private $answers;

    /* @var string tag  */
    private $tag;

    /* @var array author  */
    private $author;

    /* @var array coursename  */
    private $coursename;

    /* @var object sortby */
    private $sortby;

    /**
     * @param array $questions
     * @param \stdClass $formdata
     */
    public function __construct($questions, $formdata) {
        $this->questions = $questions;
        $this->answers = $this->formdata_to_answers($formdata);
        $this->sortby = $this->formdata_to_sort($formdata);
        $this->tag = (!empty($formdata->tag)) ? $formdata->tag : '';
        $this->author = (!empty($formdata->author)) ? $formdata->author : '';
        $this->coursename = (!empty($formdata->coursename)) ? $formdata->coursename : '';
    }

    /**
     * Turns form data into an array of question id => answer (answer might be an array).
     * @param $formdata
     */
    private function formdata_to_answers($formdata) {
        $answers = array();
        foreach ($formdata as $key => $answer) {
            if ($answer !== '0' && empty($answer)) {
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
     * Create "order by" details object if provided.
     * @param object $formdata
     * @return object|null
     */
    private function formdata_to_sort($formdata) {
        if (empty($formdata->sortby)) {
            return null;
        }
        $expsorts = explode('_', $formdata->sortby);
        if (count($expsorts) < 2) {
            return null;
        }
        $ascdesc = $expsorts[0] == 'asc' ? 'ASC' : 'DESC';
        return (object) array('field' => $expsorts[1], 'ascdesc' => $ascdesc);
    }

    /**
     * Provide a list of courses that matches the criteria submitted from the search page.
     *
     * @param int $limitfrom
     * @param int $limitnum
     *
     * @return array list of courses matching the search filters
     */
    public function get_search_result($limitfrom, $limitnum) {
        global $DB;

        $wheres = array();
        $joins = array();
        $params = array();
        $alreadyjoineds = array();

        foreach ($this->answers as $qid => $answer) {
            if (!isset($this->questions[$qid])) {
                continue;
            }
            $question = $this->questions[$qid];
            $typeclass = \block_mbstpl\questman\qtype_base::qtype_factory($question->datatype);
            $toadd = $typeclass->get_query_filters($question, $answer);
            if (!empty($toadd['wheres'])) {
                $wheres = array_merge($wheres, $toadd['wheres']);
            }
            if (!empty($toadd['joins'])) {
                $joins = array_merge($joins, $toadd['joins']);
            }
            if (!empty($toadd['params'])) {
                $params = array_merge($params, $toadd['params']);
                $alreadyjoineds[$qid] = $qid;
            }
        }
        $wheres[] = 'tpl.status = :stpublished';

        if (!empty($this->tag)) {
            $searchvalues = array_values($this->tag);
            list($searchcriteria, $parameter) = $DB->get_in_or_equal($searchvalues, SQL_PARAMS_NAMED, 'tag');
            $wheres[] = "EXISTS (SELECT 1 FROM {block_mbstpl_tag} WHERE metaid = mta.id AND tag " . $searchcriteria . ")";
            $params = array_merge($params, $parameter);
        }

        if (!empty($this->author)) {
            $searchids = array_keys($this->author);
            list($searchcriteria, $parameter) = $DB->get_in_or_equal($searchids, SQL_PARAMS_NAMED, 'author');
            $wheres[] = 'au.id ' . $searchcriteria;
            $params = array_merge($params, $parameter);
        }

        if (!empty($this->coursename)) {
            $searchvalues = array_values($this->coursename);
            list($searchcriteria, $parameter) = $DB->get_in_or_equal($searchvalues, SQL_PARAMS_NAMED, 'cname');
            $wheres[] = 'c.fullname ' . $searchcriteria;
            $params = array_merge($params, $parameter);
        }
        
        $filterwheres = implode("\n          AND ", $wheres);

        $authnamefield = $DB->sql_fullname('au.firstname', 'au.lastname');
        $selectsql = "SELECT c.id, c.fullname, cat.name AS catname, tpl.rating, $authnamefield AS authorname";

        $orderby = '';
        if (!empty($this->sortby)) {
            $orderby = "ORDER BY ";
            if (is_number($this->sortby->field)) {
                $qid = $this->sortby->field;
                $salias = 'q' . $qid;
                if (!isset($alreadyjoineds[$qid])) {
                    $joins[] = "LEFT JOIN {block_mbstpl_answer} $salias ON $salias.metaid = mta.id AND $salias.questionid = :$salias";
                    $params[$salias] = $qid;
                }
                $orderby .= "$salias.datakeyword";
            } else {
                $orderby .= 'tpl.rating';
            }
            // Adding c.id as second order, otherwise order will be randomized, when contains a value of NULL.
            $orderby .= ' ' . $this->sortby->ascdesc. ', c.id DESC';
        }
        $joins = implode("\n        ", $joins);

        $coresql = "
        FROM {course} c
        JOIN {course_categories} cat ON cat.id = c.category
        JOIN {block_mbstpl_template} tpl ON tpl.courseid = c.id
        JOIN {block_mbstpl_meta} mta ON mta.templateid = tpl.id
        LEFT JOIN {user} au ON au.id = tpl.authorid
        $joins
        WHERE $filterwheres
        ";

        $params['stpublished'] = template::STATUS_PUBLISHED;

        $sql = "
        $selectsql
        $coresql
        $orderby
        ";
        
        /*$esql = str_replace('{', 'mdl_', $sql);
        $esql = str_replace('}', '', $esql);
        print_r($esql);
        print_r($params);
        print_r($limitfrom);
        print_r($limitnum);*/
        
        $countsql = "SELECT count(c.id) $coresql ";

        $result = new \stdClass();
        $result->total = $DB->count_records_sql($countsql, $params);
        $result->courses = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        $result->limitfrom = $limitfrom;
        $result->limitnum = $limitnum;
        
        return $result;
    }

}
