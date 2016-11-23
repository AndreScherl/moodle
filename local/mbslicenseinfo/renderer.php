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
 * Mbslicenseinfo renderer
 *
 * @package    local
 * @subpackage mbslicenseinfo
 * @copyright  2016 Franziska Hübler, franziska.huebler@gmx.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/filter/mbslicenseinfo/filter.php');

/**
 * Mbslicenseinfo renderer class
 *
 * @package local
 * @subpackage mbslicenseinfo
 * @copyright 2016 Franziska Hübler, franziska.huebler@gmx.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_mbslicenseinfo_renderer extends plugin_renderer_base {

    /**
     * Render a button with the data-target attribute.
     *
     * @param int $collapseareid
     * @return string html for the button
     */
    public function render_collapse_button($collapseareid) {
        $output = '<button class="btn btn-primary btn-licenseinfo" type="button" data-toggle="collapse" data-target="#' . $collapseareid . '" '
                . 'aria-expanded="false" aria-controls="' . $collapseareid . '">';
        $output .= get_string('displayname', 'local_mbslicenseinfo');
        $output .= '</button>';
        return $output;
    }

    /**
     * Render collapse content.
     *
     * @param int $collapseareid
     * @param array $files array of filerecord objects
     * @param int $contextid
     * @return string html for the collapsearea
     */
    public function render_licenseinfo_collapsearea($collapseareid, $files, $contextid) {
        global $OUTPUT;

        $output = '<div class="collapse collapse-licenseinfo container" id="' . $collapseareid . '">';
        $output .= '<div class="row">';
        foreach ($files as $file) {
            if (strpos($file->mimetype, 'image') !== false && $file->filename != '.') {
                $previewfilepath = '/' . $file->component . '/' . $file->filearea . '/' . $file->itemid . $file->filepath;
                $url = \local_mbslicenseinfo\local\mbslicenseinfo::get_previewimageurl($contextid, $file->filename, $previewfilepath);
                $output .= '<div class="licenseinfo-img col-md-6">';
                $output .= html_writer::img($url, 'thumbnail');
                $output .= filter_mbslicenseinfo::build_license_div($file);
                $output .= '</div>';
            } else if (strpos($file->mimetype, 'video') !== false && $file->filename != '.') {
                $output .= '<div class="licenseinfo-video col-md-6">';
                $output .= html_writer::img($OUTPUT->pix_url('f/video-64'), 'video');
                $output .= filter_mbslicenseinfo::build_license_div($file);
                $output .= '</div>';
            } else if (strpos($file->mimetype, 'audio') !== false && $file->filename != '.') {
                $output .= '<div class="licenseinfo-audio col-md-6">';
                $output .= html_writer::img($OUTPUT->pix_url('f/mp3-64'), 'audio');
                $output .= filter_mbslicenseinfo::build_license_div($file);
                $output .= '</div>';
            }
        }
        $output .= '</div></div>';
        return $output;
    }

}
