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
 * AJAX destination point for meinek_old block
 *
 * @package   block_meinek_old
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', 1);
require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE;
require_once($CFG->dirroot.'/blocks/meinek_old/lib.php');

$action = required_param('action', PARAM_ALPHA);
$url = optional_param('pageurl', new moodle_url('/blocks/meinek_old/ajax.php'), PARAM_URL);
$PAGE->set_url($url);

require_login();
$PAGE->set_context(context_system::instance());

switch ($action) {
    case 'setschool':
        require_sesskey();
        $schoolid = required_param('schoolid', PARAM_INT);
        $prefs = meinek_old::get_prefs();
        $prefs->school = $schoolid;
        meinek_old::set_prefs($prefs);
        break;
    case 'getcourses':
        $page = optional_param('meinek_old_page', 0, PARAM_INT) + 1;
        $sortby = optional_param('meinek_old_sortby', null, PARAM_ALPHA);
        $sortdir = optional_param('meinek_old_sortdir', null, PARAM_ALPHA);
        $numcourses = optional_param('meinek_old_numcourses', null, PARAM_INT);
        $schoolid = optional_param('meinek_old_schoolid', null, PARAM_INT);
        $otherschoolid = optional_param('meinek_old_otherschoolid', null, PARAM_INT);
        list($name, $out) = meinek_old::output_course_list($page, $sortby, $sortdir, $numcourses, $schoolid, $otherschoolid);
        $prefs = meinek_old::get_prefs();
        $ret = (object)array(
            'error' => 0,
            'schoolid' => $prefs->school,
            'otherschoolid' => $prefs->otherschool,
            'name' => $name,
            'content' => $out,
            'sortdir' => $prefs->sortdir,
        );
        echo json_encode($ret);
        break;
    default:
        print_error('invalidaction', 'block_meinek_old');
}
