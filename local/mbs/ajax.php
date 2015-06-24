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
 * Handle AJAX requests
 *
 * @package   local_mbs
 * @copyright 2014 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__).'/../../config.php');
global $DB;

$action = required_param('action', PARAM_ALPHA);

switch ($action) {

    case 'checkshortname':
        
        require_login();
        
        $resp = (object)array(
            'response' => 'OK',
            'error' => '',
        );

        $id = required_param('id', PARAM_INT);
        $shortname = required_param('shortname', PARAM_TEXT);

        $select = "shortname = :shortname";
        $params = array('shortname' => $shortname);
        if ($id) {
            $select .= " AND id <> :id ";
            $params['id'] = $id;
        }
        if ($existingfullname = $DB->get_field_select('course', 'fullname', $select, $params)) {
            $resp->response = 'Exists';
            $resp->error = get_string('shortnametaken', 'core', $existingfullname);
        }

        echo json_encode($resp);
        break;
        
    default:
        throw new moodle_exception('unknownaction', 'local_mbs');
}
