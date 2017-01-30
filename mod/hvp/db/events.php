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
 * Event observers definition.
 *
 * @package mod_hvp
 * @category event
 * @copyright 2016 ISB Bayern
 * @author Andre Scherl <andre.scherl@isb.bayern.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$observers = array(

    array(
        'eventname' => '\core\event\course_module_created',
        'callback' => '\local_mbslicenseinfo\local\mbslicenseinfo::hvp_module_created',
        'includefile' => '/local/mbslicenseinfo/classes/local/mbslicenseinfo.php'
    ),
    array(
        'eventname' => '\core\event\course_module_updated',
        'callback' => '\local_mbslicenseinfo\local\mbslicenseinfo::hvp_module_updated',
        'includefile' => '/local/mbslicenseinfo/classes/local/mbslicenseinfo.php'
    )
);