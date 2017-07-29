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
 * Override the renderer of course format collapsed topics to bring in action 
 * menu and course headline.
 *
 * @package   theme_mebis
 * @copyright 2015 ISB Bayern
 * @author    Andreas Wagner, ISB Bayern.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/topcoll/renderer.php');

/**
 * Basic renderer for onetopic format.
 */
class theme_mebis_format_topcoll_renderer extends format_topcoll_renderer {

    /** Renders course headline
     * 
     * @param  string headline (i. e. the courses fullname)
     * @return string
     */
    protected function render_course_headline($headline) {

        $o = html_writer::tag('h1', $headline);
        return html_writer::div($o, 'course-headline');
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     */
    public function print_multiple_section_page($course, $sections, $mods,
                                                $modnames, $modnamesused) {
        echo html_writer::start_tag('div', array('class' => 'course course-format-topcoll'));
        echo $this->render_course_headline($course->fullname);

        parent::print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused);
        echo html_writer::end_tag('div');
    }

}