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
 * @package   block_mbstpl
 * @copyright 2016 Franziska Hübler, ISB
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

$context = context_system::instance();

require_login();

$action = required_param('action', PARAM_TEXT);

switch ($action) {

    case 'search' :
        require_capability('block/mbstpl:createcoursefromtemplate', $context);

        $searchtext = optional_param('searchtext', '', PARAM_TEXT);
        $like = $DB->sql_like('subject', '?', false);
        $params = array('%' . $searchtext . '%');

        $sql = "SELECT * FROM {block_mbstpl_subjects} WHERE " . $like;
        $subjects = $DB->get_records_sql($sql, $params);

        $results = array();
        foreach ($subjects as $subject) {
            $results[] = html_writer::tag('span', $subject->subject, array('id' => $subject->id));
        }
        
        echo json_encode(array('error' => 0, 'results' => $results));
        die;
        break;        
}

print_error('unkown action');



