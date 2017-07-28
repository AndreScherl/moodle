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
 * @package   block_mbsnews
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

require_login();

$action = required_param('action', PARAM_TEXT);

switch ($action) {

    case 'searchinstances' :

        $context = context_system::instance();
        require_capability('block/mbsnews:sendnews', $context);

        $searchtext = optional_param('searchtext', '', PARAM_TEXT);
        $contextlevel = optional_param('id_contextlevel', CONTEXT_COURSECAT, PARAM_TEXT);

        if ($contextlevel == CONTEXT_COURSECAT) {

            $like = $DB->sql_like('name', '?', false);
            $params = array('%' . $searchtext . '%', \local_mbs\local\schoolcategory::$schoolcatdepth);

            $sql = "SELECT * FROM {course_categories} WHERE " . $like. " AND depth = ?";
            $categories = $DB->get_records_sql($sql, $params);

            $results = array();
            foreach ($categories as $category) {
                $results[] = html_writer::tag('span', $category->name, array('id' => $category->id));
            }
        }

        if ($contextlevel == CONTEXT_COURSE) {

            $like = $DB->sql_like('fullname', '?', false);
            $params = array('%' . $searchtext . '%');

            $sql = "SELECT * FROM {course} WHERE " . $like;
            $courses = $DB->get_records_sql($sql, $params);

            $results = array();
            foreach ($courses as $course) {
                $results[] = html_writer::tag('span', $course->fullname, array('id' => $course->id));
            }
        }

        echo json_encode(array('error' => 0, 'results' => $results));
        die;
        break;

    case 'getroleoptions':

        $context = context_system::instance();
        require_capability('block/mbsnews:sendnews', $context);

        $contextlevel = optional_param('contextlevel', CONTEXT_COURSECAT, PARAM_TEXT);

        $sql = "SELECT r.*
                FROM {role} r
                JOIN {role_context_levels} rcl ON r.id = rcl.roleid AND rcl.contextlevel = ?";

        $roles = $DB->get_records_sql($sql, array($contextlevel));

        $results = array();
        $results[] = array('value' => '0', 'text' => get_string('select'));
        foreach ($roles as $role) {
            $results[] = array('value' => $role->id, 'text' => role_get_name($role));
        }

        echo json_encode(array('error' => 0, 'results' => $results));
        die;
        break;

    case 'searchrecipients':

        $context = context_system::instance();
        require_capability('block/mbsnews:sendnews', $context);

        $params = array();
        $params['contextlevel'] = optional_param('contextlevel', 0, PARAM_INT);
        $params['roleid'] = optional_param('roleid', 0, PARAM_INT);
        $params['instanceids'] = optional_param('instanceids', '', PARAM_TEXT);

        $result = \block_mbsnews\local\newshelper::search_recipients($params);

        echo json_encode($result);
        die;
        break;

    case 'markasread' :

        $messageid = required_param('messageid', PARAM_INT);

        if (!$message = $DB->get_record('block_mbsnews_message', array('id' => $messageid))) {
        
            $result = array('error' => 0, 'results' => array('id' => $messageid));
            
        } else {
            
            $result = \block_mbsnews\local\newshelper::mark_message_read($message);
        }

        echo json_encode($result);
        die;
        break;
}

print_error('unkown action');