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
 * @package   local_mbslicenseinfo
 * @copyright 2015, ISB Bayern
 * @author    Andreas Wagner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_mbslicenseinfo_install() {
    global $CFG, $DB;

    if (file_exists($CFG->dirroot.'/blocks/mbslicenseinfo/db/install.xml')) {

        if ($licenseinfos = $DB->get_records('block_mbslicenseinfo_fmeta')) {
            $DB->insert_records('local_mbslicenseinfo_fmeta', $licenseinfos);
        }

        if ($userlicenses = $DB->get_records('block_mbslicenseinfo_ul')) {
            $DB->insert_records('local_mbslicenseinfo_ul', $userlicenses);
        }

    }
}