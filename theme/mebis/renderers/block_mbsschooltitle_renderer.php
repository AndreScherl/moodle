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
 * renderer fot block_mbsschooltitle
 *
 * @package   theme_mebis
 * @copyright 2015 ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/mbsschooltitle/renderer.php');

class theme_mebis_block_mbsschooltitle_renderer extends block_mbsschooltitle_renderer {

    public function render_content($titledata) {
        global $OUTPUT, $PAGE;

        $o = '';

        // Link to School.
        if (!empty($titledata->usersschoolid)) {
            $schoolurl = new moodle_url('/course/index.php', array('categoryid' => $titledata->usersschoolid));
            $schoollink = html_writer::link($schoolurl, get_string('toschoolcategory', 'block_mbsschooltitle'), array('class' => 'btn btn-secondary'));
            $o = html_writer::tag('div', $schoollink, array('class' => 'col-md-3 col-xs-12'));
        }

        // Headline of page.
        $headline = (!empty($titledata->headline)) ? $titledata->headline : $PAGE->heading;
        $headlinetag = html_writer::tag('h3', $headline);
        $headlinecontainer = html_writer::tag('div', $headlinetag, array('class' => 'mbs-schooltitle-headline pull-left'));
        
        // Editlink, capability is already checked.
        if (!empty($titledata->editurl)) {
            $editlink = $OUTPUT->action_icon($titledata->editurl, new pix_icon('editgray', get_string('edit'), 'theme_mebis'));
            $headlinecontainer .= html_writer::tag('div', $editlink, array('class' => 'mbs-schooltitle-editlink pull-left'));
        }
        $o .= html_writer::tag('div', $headlinecontainer, array('class' => 'col-md-7 col-xs-6'));

        // Image.
        if (!empty($titledata->imageurl)) {   
            $imagetag = html_writer::empty_tag('img', array('src' => $titledata->imageurl, 'alt' => get_string('imageofcategory', 'block_mbsschooltitle')));
            $o .= html_writer::tag('div', $imagetag, array('class' => 'col-md-2 col-xs-6 mbs-schooltitle-image pull-right text-right'));
        }

        $o = html_writer::tag('div', $o, array('class' => 'row'));
        $o = html_writer::tag('div', $o, array('class' => 'container'));
        return html_writer::tag('div', $o, array('class' => 'block_mbsschooltitle'));
    }

}
