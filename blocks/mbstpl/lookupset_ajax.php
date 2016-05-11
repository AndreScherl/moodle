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

use \block_mbstpl\dataobj\template as template;

switch ($action) {

    case 'searchsubject' :
        if (block_mbstpl\perms::can_searchtemplates()) {
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
        }
        die;
        break;  
        
    case 'searchtags':
        if (block_mbstpl\perms::can_searchtemplates()) {
            $searchtext = optional_param('searchtext', '', PARAM_TEXT);
            $like = $DB->sql_like('tag', ':tag', false);
            $params = array('tag' => '%' . $searchtext . '%', 'status' => template::STATUS_PUBLISHED);

            $sql = "SELECT tag.id, tag.tag 
                FROM {block_mbstpl_tag} tag
                JOIN {block_mbstpl_meta} mta ON tag.metaid = mta.id
                JOIN {block_mbstpl_template} tpl ON mta.templateid = tpl.id                
                WHERE tpl.status = :status AND " . $like . "GROUP BY tag";
            
            $tags = $DB->get_records_sql($sql, $params);

            $results = array();
            foreach ($tags as $tag) {
                $results[] = html_writer::tag('span', $tag->tag, array('id' => $tag->id));
            }

            echo json_encode(array('error' => 0, 'results' => $results));
        }
        die;
        break;
    
    case 'searchauthor':
        if (block_mbstpl\perms::can_searchtemplates()) {
            $searchtext = optional_param('searchtext', '', PARAM_TEXT);
            
            $authnamefield = $DB->sql_fullname('au.firstname', 'au.lastname');
            $like = $DB->sql_like($authnamefield, ':author', false);
            $params = array('author' => '%' . $searchtext . '%', 'status' => template::STATUS_PUBLISHED);
            $sql = "SELECT au.id, $authnamefield AS authorname 
                FROM {block_mbstpl_template} tpl 
                RIGHT JOIN {user} au ON tpl.authorid = au.id 
                WHERE tpl.status = :status AND " . $like;       
            $authors = $DB->get_records_sql($sql, $params);

            $results = array();
            foreach ($authors as $author) {
                $results[] = html_writer::tag('span', $author->authorname, array('id' => $author->id));
            }

            echo json_encode(array('error' => 0, 'results' => $results));
        }
        die;
        break;
    
    case 'searchcoursename':
        if (block_mbstpl\perms::can_searchtemplates()) {
            $searchtext = optional_param('searchtext', '', PARAM_TEXT);
            
            $likes[] = $DB->sql_like('c.shortname', ':cname1', false);
            $likes[] = $DB->sql_like('c.fullname', ':cname2', false);
            $where = implode(' OR ', $likes);

            $cname = '%' . $searchtext . '%';
            $params = array('cname1' => $cname, 'cname2' => $cname, 'status' => template::STATUS_PUBLISHED);
            $sql = "SELECT c.id, c.fullname
                FROM {course} c 
                JOIN {block_mbstpl_template} tpl ON tpl.courseid = c.id 
                WHERE tpl.status = :status AND (" . $where . ") 
                GROUP BY c.fullname
                ORDER BY c.fullname ASC";
            $coursenames = $DB->get_records_sql($sql, $params);
            
            $results = array();
            foreach ($coursenames as $coursename) {
                $results[] = html_writer::tag('span', $coursename->fullname, array('id' => $coursename->id));
            }

            echo json_encode(array('error' => 0, 'results' => $results));
        }
        die;
        break;
}

print_error('unkown action');



