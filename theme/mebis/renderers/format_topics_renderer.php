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
 * Overriding the topics renderer 
 *
 * @package   theme_mebis
 * @copyright 2015 ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/topics/renderer.php');

/**
 * Basic renderer for onetopic format.
 */
class theme_mebis_format_topics_renderer extends format_topics_renderer {

    /** render the page action menu (i. e. the menu on the right side to jump to sections
     * 
     * @param object $course
     * @param arra $sections
     * @param boolean $onlyMobile
     * @return string HTML fo the section menu.
     */
    public function render_page_action_menu($course, $sections,
                                               $onlyMobile = false) {
        //Add side jump-navigation
        $menu_items = array();
        $output = '';
        
        if (count($sections)) {
            for ($i = 1; $i <= $course->numsections; $i++) {
                if ($sections[$i]->uservisible && $sections[$i]->visible && $sections[$i]->available) {
                    $menu_items[] = html_writer::link('#section-' . $i, '<span>' . get_section_name($course, $sections[$i]) . '</span>', array());
                }
            }
        }        

        foreach ($menu_items as $item) {
            $output .= html_writer::start_tag('li');
            $output .= html_writer::tag('div', '<span>' . $item . '</span>', array('class' => 'internal'));
            $output .= html_writer::end_tag('li');
        }
        
        return $output;
    }

    /** Renders course headline
     * 
     * @param  string headline (i. e. the courses fullname)
     * @return string
     */
    protected function render_course_headline($headline) {

        $o = html_writer::tag('h2', $headline);
        return html_writer::div($o, 'course-headline');
    }

    /**
     * overriding method to insert the page action menu to include rendering of 
     * the page action menu
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param array    $mods (argument not used)
     * @return string HTML to output.
     */
    public function print_multiple_section_page($course, $sections, $mods,
                                                $modnames, $modnamesused) {

        echo html_writer::start_div('course course-format-topics');
        
        echo $this->render_course_headline($course->fullname);
        parent::print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused);

        echo html_writer::end_div();
    }
}