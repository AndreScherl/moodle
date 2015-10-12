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

class search {

    /* @var array questions  */
    private $questions;

    /* @var array answers  */
    private $answers;

    /* @var string tag  */
    private $tag;

    /* @var array author  */
    private $author;

    /* @var array keyword  */
    private $keyword;

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
        $this->tag = trim($formdata->tag);
        $this->author = trim($formdata->author);
        $this->keyword = trim($formdata->keyword);
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
        return (object)array('field' => $expsorts[1], 'ascdesc' => $ascdesc);
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
            $wheres[] = "EXISTS (SELECT 1 FROM {block_mbstpl_tag} WHERE metaid = mta.id AND tag = :tag)";
            $params['tag'] = $this->tag;
        }

        $authnamefield = $DB->sql_fullname('au.firstname', 'au.lastname');
        if (!empty($this->author)) {
            $wheres[] = "$authnamefield LIKE :author";
            $params['author'] = '%' . $this->author . '%';
        }

        if (!empty($this->keyword)) {
            $wheres[] = "(c.shortname LIKE :cname1 OR c.fullname LIKE :cname2)";
            $keywordwc = '%' . $this->keyword . '%';
            $params['cname1'] = $keywordwc;
            $params['cname2'] = $keywordwc;
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
            $orderby .= ' ' . $this->sortby->ascdesc;
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

        $results = $DB->get_records_sql($sql, $params);
        return $results;
    }
}
