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
 * API function calls
 *
 * @package   block_mbstpl
 * @copyright 2015 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function block_mbstpl_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {

    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    require_course_login($course, true);

    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = '/'.implode('/', $args);
    if ($filepath != '/') {
        $filepath .= '/';
    }

    if ($filearea == \block_mbstpl\dataobj\revhist::FILEAREA) {
        $revhist = new \block_mbstpl\dataobj\revhist(array('id' => $itemid), true, MUST_EXIST);
        $template = new \block_mbstpl\dataobj\template(array('id' => $revhist->templateid), true, MUST_EXIST);
    } else if ($filearea = \block_mbstpl\dataobj\template::FILEAREA) {
        $template = new \block_mbstpl\dataobj\template(array('id' => $itemid), true, MUST_EXIST);
    } else {
        return false;
    }

    if (!\block_mbstpl\perms::can_viewfeedback($template, $context)) {
        return false;
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'block_mbstpl', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}
