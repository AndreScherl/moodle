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
defined('MOODLE_INTERNAL') || die;

class block_mbsschooltitle_renderer extends plugin_renderer_base {

    public function render_content($titledata) {
        global $OUTPUT, $PAGE;

        $o = '';

        // Link to School.
        if (!empty($titledata->usersschoolid)) {
            $schoolurl = new moodle_url('/course/index.php', array('categoryid' => $titledata->usersschoolid));
            $schoollink = html_writer::link($schoolurl, get_string('toschoolcategory', 'block_mbsschooltitle'));
            $o = html_writer::tag('div', $schoollink);
        }

        // Headline of page.
        $headline = (!empty($titledata->headline)) ? $titledata->headline : $PAGE->heading;
        $headlinetag = html_writer::tag('h1', $headline);
        $o .= html_writer::tag('div', $headlinetag, array('class' => 'mbs-schooltitle-headline'));

        // Image.
        if (!empty($titledata->imageurl)) {
            $imagetag = html_writer::empty_tag('img', array('src' => $titledata->imageurl, 'alt' => get_string('imageofcategory', 'block_mbsschooltitle')));
            $o .= html_writer::tag('div', $imagetag, array('class' => 'mbs-schooltitle-image'));
        }

        // Editlink, capability is already checked.
        if (!empty($titledata->editurl)) {
            $editlink = $OUTPUT->action_icon($titledata->editurl, new pix_icon('t/edit', get_string('edit')));
            $o .= html_writer::tag('div', $editlink, array('class' => 'mbs-schooltitle-editlink'));
        }

        return html_writer::tag('div', $o, array('id' => 'mbs-schooltitle'));
    }

}