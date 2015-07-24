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
 * Renderer for block_mbsschooltitle
 *
 * @package   block_mbsschooltitle
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use \block_mbsschooltitle\local\imagehelper as imagehelper;

function block_mbsschooltitle_pluginfile($course, $birecord, $context,
                                         $filearea, $args, $forcedownload,
                                         $params) {

    if ($filearea !== imagehelper::$filearea) {
        send_file_not_found();
    }

    $fs = get_file_storage();

    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    if (!$file = $fs->get_file($context->id, imagehelper::$component, $filearea, 0, $filepath, $filename) or $file->is_directory()) {
        send_file_not_found();
    }

    // NOTE: it would be nice to have file revisions here, for now rely on standard file lifetime,
    //       do not lower it because the files are displayed very often.
    \core\session\manager::write_close();
    send_stored_file($file);
}
