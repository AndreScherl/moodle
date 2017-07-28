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
 * local mbs webservice definitions
 * 
 * @package    local_mbs
 * @copyright  2016 Franziska Hübler, ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'local_mbs_create_user' => array(                                                                              
            'classname'   => 'local_mbs_external',
            'methodname'  => 'local_mbs_create_user',
            'classpath'   => 'local/mbs/externallib.php',
            'description' => 'Create a user with given parameters.',
            'type'        => 'write',
            'capabilities'  => 'moodle/user:create'
    ),
    'local_mbs_update_user' => array(                                                                              
            'classname'   => 'local_mbs_external',
            'methodname'  => 'local_mbs_update_user',
            'classpath'   => 'local/mbs/externallib.php',
            'description' => 'Update a user with given parameters.',
            'type'        => 'write',
            'capabilities'  => 'moodle/user:update'
    ),
    'local_mbs_delete_user' => array(                                                                              
            'classname'   => 'local_mbs_external',
            'methodname'  => 'local_mbs_delete_user',
            'classpath'   => 'local/mbs/externallib.php',
            'description' => 'Delete a user.',
            'type'        => 'write',
            'capabilities'  => 'moodle/user:delete'
    ),
    'local_mbs_get_user' => array(                                                                              
            'classname'   => 'local_mbs_external',
            'methodname'  => 'local_mbs_get_user',
            'classpath'   => 'local/mbs/externallib.php',
            'description' => 'Search a user.',
            'type'        => 'write',
            'capabilities'  => 'moodle/user:viewdetails, moodle/user:viewhiddendetails, moodle/course:useremail, moodle/user:update'
    )
);
