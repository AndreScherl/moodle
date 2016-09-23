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

    /**
     * Render result listing of block mbstpl
     *
     * @param object $searchresult result containing search result informations
     * @param string $layout grid or list
     * @return string html to be displayed
     */
    protected function templatesearch_resultlist($searchresult, $layout) {

        // Render content of list.
        $listitems = $this->templatesearch_listitems($searchresult, $layout);

        $listattributes = array(
            "class" => "block-grid-xs-1 block-grid-xc-2 block-grid-md-3",
            'id' => 'mbstpl-search-listing'
        );

        $list = html_writer::tag('ul', $listitems, $listattributes);
        return \html_writer::div($list, 'col-md-12');
    }

    /**
     * Render the lits items
     * 
     * @param object $searchresult result containing result informations
     * @param string $layout grid or list
     * @return type
     */
    protected function templatesearch_listitems($searchresult, $layout) {

        $output = '';

        // Complaining
        $complainturl = new moodle_url(get_config('block_mbstpl', 'complainturl'));

        foreach ($searchresult->courses as $course) {

            // Coursebox.          
            $html = html_writer::start_tag('li', array('id' => "course-{$course->id}", 'class' => 'coursebox'));

            // Render div.coursebox-meta
            $cbmeta = html_writer::tag('i', '', array('class' => 'icon-me-lernplattform'));
            $cbmeta = html_writer::div($cbmeta, 'col-xs-12 box-type text-right');
            $cbmeta = html_writer::div($cbmeta, 'row');
            $html .= html_writer::div($cbmeta, 'coursebox-meta');

            // Render div.coursebox-inner
            $html .= html_writer::start_div('coursebox-inner');
            $html .= html_writer::start_div('course_title');

            $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
            $coursetext = html_writer::tag('span', $course->fullname, array('class' => 'coursename internal'));
            $coursetext .= html_writer::tag('p', $course->authorname, array('class' => 'coursetype'));
            if (!is_null($course->rating)) {
                $template = \block_mbstpl\dataobj\template::get_from_course($course->id);
                $coursetext .= parent::rating($template, false);
            }

            $html .= html_writer::tag('a', $coursetext, array('class' => 'coursebox-link', 'href' => $courseurl));

            // Complainturl.
            $complaintcourseurl = clone($complainturl);
            $complaintcourseurl->param('courseid', $course->id);
            $text = html_writer::tag('i', '', array('class' => 'fa fa-gavel'));

            $complaintlink = \html_writer::link($complaintcourseurl, $text, array('title' => get_string('complaintform', 'block_mbstpl')));
            $html .= html_writer::div($complaintlink, 'righticons');

            $html .= html_writer::end_tag('div'); //end class 'course_title'
            $html .= html_writer::end_tag('div'); //end class 'coursebox-inner'
            $html .= html_writer::end_tag('li');

            // Add coursebox.
            $output .= $html;
        }
        return $output;
    }

    /**
     * Render the load more result element,
     * @return string HTML of load result element
     */
    protected function templatesearch_moreresults($formdata) {
        
        // Store POST array in hidden field of form.
        $searchurl = new moodle_url('/blocks/mbstpl/templatesearch.php', array('param' => base64_encode(serialize($formdata))));
        
        $loadmoreform = new \block_mbstpl\form\loadmore($searchurl, array(), 'post', '', array('id' => 'mbstpl-loadmore-form'));
        $o = $loadmoreform->render();

        $url = new moodle_url('#', array());
        $text = get_string('loadmoreresults', 'block_mbssearch');

        $link = html_writer::link($url, $text, array(
                    'id' => 'mbstpl-search-loadmoreresults',
                    'class' => 'btn load-more-results'));

        $o .= html_writer::div($link, 'row col-md-12 add-more-results');

        return $o;
    }

}
