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
 * Renderer for block_mbstpl
 *
 * @package    theme_mebis
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/mbstpl/renderer.php');


class theme_mebis_block_mbstpl_renderer extends block_mbstpl_renderer {

    
    public function templatesearch($searchform, $courses, $layout, $searchflag) {
        
        // Add the search form.
        $html = \html_writer::div($searchform->render(), 'mbstpl-search-form');

        // Render result listing.   
        if ($searchflag) {
            $headingpanel = \html_writer::tag('h3', get_string('searchresult', 'block_mbstpl'));
            $html .= \html_writer::div($headingpanel, 'mbstpl-heading-panel');
            $html .= $this->mbstpl_resultlist($courses, $layout, $searchflag);
        }
        return $html;
    }   
    
    /**
     * render result listing of block mbstpl
     *
     * @param array $courses list of courses
     * @param string $layout not in use
     * @return string html to be displayed
     */
    public function mbstpl_resultlist($courses, $layout, $searchflag) {
        if (count($courses) > 0) {
            return $this->mbstpl_grid($courses);
        } else if ($searchflag) {
            return \html_writer::tag('h3', get_string('noresults', 'block_mbstpl'));
        }
        return '';
    }

    /**
     * render resultlist of mbstpl block in grid layout
     *
     * @param array $courses list of courses
     * @return string html to be displayed
     */
    public function mbstpl_grid($courses) {
        $output = html_writer::start_tag('div', array('class' => 'col-md-12'));
        $output .= html_writer::start_tag("ul", array("class" => "block-grid-xs-1 block-grid-xc-2 block-grid-md-3", 
            'id' => 'mbstpl-search-listing'));

        foreach ($courses as $course) {
            // ...start coursebox.          
            $html = '';
            // .coursebox-meta
            $html .= html_writer::start_tag('li', array('id' => "course-{$course->id}", 'class' => 'coursebox'));
            $html .= html_writer::start_div('coursebox-meta');
                $html .= html_writer::start_div('row');
                    $html .= html_writer::start_div('col-xs-12 box-type text-right');
                        $html .= html_writer::tag('i', '', array('class' => 'icon-me-lernplattform'));
                    $html .= html_writer::end_div(); //'col-xs-12 box-type text-right'
                $html .= html_writer::end_div(); //class 'row'
            $html .= html_writer::end_div(); //end class 'coursebox-meta'

            // .coursebox-inner
            $html .= html_writer::start_div('coursebox-inner');
            $html .= html_writer::start_div('course_title');
                $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
                $html .= html_writer::start_tag('a', array('class' => 'coursebox-link', 'href' => $courseurl));
                    $html .= html_writer::tag('span', $course->fullname, array('class' => 'coursename internal'));
                    $html .= html_writer::tag('p', $course->authorname, array('class' => 'coursetype'));
                    if (!is_null($course->rating)) {
                        $template = \block_mbstpl\dataobj\template::get_from_course($course->id);
                        $html .= parent::rating($template, false);
                    } 
                $html .= html_writer::end_tag('a');
                //complaining
                $complainturl = new moodle_url(get_config('block_mbstpl', 'complainturl'));
                $externalurl = clone($complainturl);
                $externalurl->param('courseid', $course->id);
                $righticons = '';
                $text = html_writer::tag('i', '', array('class' => 'fa fa-gavel'));
                $complaintlink = \html_writer::link($externalurl, $text, 
                        array('title' => get_string('url-complaints', 'theme_mebis')));
                $righticons .= $complaintlink;
                $html .= html_writer::div($righticons, 'righticons');                
            $html .= html_writer::end_tag('div'); //end class 'course_title'
            $html .= html_writer::end_tag('div'); //end class 'coursebox-inner'
            $html .= html_writer::end_tag('li');
            // ...end coursebox.
            $output .= $html;
        }
        $output .= html_writer::end_tag("ul");
        $output .= html_writer::end_tag('div');

        return $output;
    }    
}
