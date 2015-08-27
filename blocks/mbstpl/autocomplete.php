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

define('AJAX_SCRIPT', true);

use \block_mbstpl\dataobj\template;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

require_login();
require_sesskey();

global $DB;

$keyword = required_param("keyword", PARAM_ALPHANUM);
$keyword = $DB->sql_like_escape($keyword);

// Metadata suggestions.
$sql =  'SELECT DISTINCT A.data FROM {block_mbstpl_answer} as A';
$sql .= ' JOIN {block_mbstpl_question} as Q ON Q.id = A.questionid';
$sql .= ' JOIN {block_mbstpl_meta} AS M ON M.id = A.metaid';
$sql .= ' JOIN {block_mbstpl_template} as T on T.id = M.templateid';
$sql .= ' WHERE A.data LIKE "%' . $keyword . '%" COLLATE UTF8_GENERAL_CI';
$sql .= ' AND T.status = ?';
$sql .= ' AND (Q.datatype = ? OR Q.datatype = ?)';

$metadatasuggestions = $DB->get_fieldset_sql($sql,
        array(template::STATUS_PUBLISHED, 'text', 'textarea'));

// Course data suggestions.
$sql = 'SELECT C.fullname, C.idnumber, C.shortname FROM {block_mbstpl_template} as T';
$sql .= ' JOIN mdl_course as C ON T.courseid = C.id';
$sql .= ' WHERE T.status = ?';
$sql .= ' AND (C.fullname LIKE "%' . $keyword . '%" COLLATE UTF8_GENERAL_CI';
$sql .= ' OR C.idnumber LIKE "%' . $keyword . '%" COLLATE UTF8_GENERAL_CI' ;
$sql .= ' OR C.shortname LIKE "%' . $keyword . '%" COLLATE UTF8_GENERAL_CI)';
$coursesuggestionrecords = $DB->get_records_sql($sql,
        array(template::STATUS_PUBLISHED));

$coursesuggestions = array();
foreach ($coursesuggestionrecords as $suggestion) {
    $coursesuggestions[] = $suggestion->fullname;
    $coursesuggestions[] = $suggestion->idnumber;
    $coursesuggestions[] = $suggestion->shortname;
}

// Merge the two resultset.
$suggestions = array_merge($coursesuggestions, $metadatasuggestions);

// Filter out empty values and duplicates.
$suggestions = array_unique (array_filter ($suggestions, function($value) use ($keyword) {
        return !!$value && strpos(strtolower($value), strtolower($keyword)) !== false;
}));
asort($suggestions);
header('Content-Type: application/json; charset=utf-8');

// Reset keys.
echo json_encode(array_values($suggestions));