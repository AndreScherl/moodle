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
 * @package    local_mbslicenseinfo
 * @author     Andreas Wagner
 * @copyright  2015 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

use \local_mbslicenseinfo\local\mbslicenseinfo as mbslicenseinfo;

function local_mbslicenseinfo_extend_settings_navigation(settings_navigation $navigation, context $context) {
    global $COURSE;

    if ($captype = mbslicenseinfo::get_license_capability($context)) {

        if ($coursenode = $navigation->get('courseadmin')) {

            $strkey = ($captype > mbslicenseinfo::$captype_viewall) ? 'editlicenses' : 'viewlicenses';
            $licenselink = new \moodle_url('/local/mbslicenseinfo/editlicenses.php', array('course' => $COURSE->id));
            $coursenode->add(get_string($strkey, 'local_mbslicenseinfo'), $licenselink);
        }
    }
}

/**
 * Serves the files from the local_mbslicenseinfo file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the newmodule's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function local_mbslicenseinfo_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = array()) {

    switch ($filearea) {

        case mbslicenseinfo::$fileareathumb:
            $fs = get_file_storage();

            $filename = array_pop($args);            
            $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

            $file = $fs->get_file($context->id, mbslicenseinfo::$component, $filearea, 0, $filepath, $filename);
            if (!$file) {
                $file = mbslicenseinfo::get_previewfile($context->id, $filename, $args);
            }

            send_stored_file($file);
            break;
            
        default:
            send_file_not_found(); // Invalid file area.
    }
}
