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
 * @package block_mbstpl
 * @copyright 2015 Janek Lasocki-Biczysko, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_mbstpl\dataobj\template,
    block_mbstpl\dataobj\coursefromtpl;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility class for testing
 */
class block_mbstpl_test_utils {

    public static function create_template($courseid = 1, $backupid = 1, $authorid = 1) {

        $template = new template(array(
            'courseid' => $courseid,
            'backupid' => $backupid,
            'authorid' => $authorid
        ));
        $template->insert();

        return $template;
    }

    public static function create_coursefromtpl($templateid, $courseid = 1, $createdby = null) {

        $coursefromtpl = new coursefromtpl(array(
            'courseid' => $courseid,
            'templateid' => $templateid,
            'createdby' => $createdby
        ));
        $coursefromtpl->insert();

        return $coursefromtpl;
    }
}
