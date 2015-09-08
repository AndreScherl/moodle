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
 * Capabilities for mbsteamteaching enrolment plugin.
 *
 * @package    enrol_mbsteamteaching
 * @copyright  2015 Andre Scherl <andre.scherl@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    /* Add or edit enrol-mbsteamteaching instance in course. */
    'enrol/mbsteamteaching:config' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        )
    ),

    /* Manage user mbsteamteaching-enrolments. */
    'enrol/mbsteamteaching:manage' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        )
    ),

    /* Voluntarily unenrol mbsteamteaching from course - watch out for data loss. */
    'enrol/mbsteamteaching:unenrolmbsteamteaching' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'student' => CAP_PREVENT,
        )
    ),

    /* Unenrol anybody from course (including mbsteamteaching) -  watch out for data loss. */
    'enrol/mbsteamteaching:unenrol' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        )
    ),

);
