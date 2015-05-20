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
require_once($CFG->dirroot . '/blocks/mbssearch/renderer.php');

class theme_mebis_block_mbssearch_renderer extends block_mbssearch_renderer {

    /** returns html code for a search form used directly in block search */
    protected static function render_search_form() {
        global $OUTPUT, $PAGE;

        $output = '';
        
        $label = html_writer::tag('label', get_string('searchschoolandcourse', 'block_mbssearch'), array('for' => 'searchtext'));
        $output .= html_writer::tag('div',$label, array('class' => 'col-md-4'));

        $output .= html_writer::start_div('col-md-8');
        $output .= html_writer::start_div('input-group');
        $output .= html_writer::empty_tag('input', array('id' => 'searchtext',
                    'type' => 'text',
                    'name' => 'searchtext',
                    'value' => '',
                    'placeholder' => get_string('search', 'block_mbssearch') . ' ...',
                    'class' => 'form-control')
        );

        $output .= html_writer::start_tag('span', array('class' => 'input-group-btn'));
        $output .= html_writer::tag('button', html_writer::tag('i', '', array('class' => 'fa fa-search')),
                array('type' => 'image',
                    'id' => 'search_submitbutton',
                    'name' => 'search',
                    'src' => $OUTPUT->pix_url('a/search'), 
                    'class' => 'btn btn-primary'));
        $output .= html_writer::end_tag('span');
        $output .= html_writer::end_div();
        

        // ... if we are in category context of a school (i. e. a category with depth >= 3, show "search only in" option.
        if ($PAGE->context->contextlevel == CONTEXT_COURSECAT) {

            $categoryid = $PAGE->context->instanceid;

            if ($schoolcat = \local_mbs\local\schoolcategory::get_schoolcategory($categoryid)) {

                $checkbox = html_writer::empty_tag('input', array('type' => 'checkbox',
                            'id' => 'search_schoolcatid',
                            'name' => 'search_schoolcatid',
                            'value' => $schoolcat->id, ));

                $checkbox .= html_writer::tag('label', get_string('searchonlyin', 'block_mbssearch', $schoolcat->name), array('for' => 'search_schoolcatid'));
                $output .= html_writer::tag('div', $checkbox, array('class' => 'col-md-12 pull-left'));
            }
        }
        
        $output .= html_writer::end_div();

        
        // Wrapping elements in form and containers.
        $actionurl = new moodle_url('/blocks/mbssearch/search.php');
        $output = html_writer::tag('form', $output, array('id' => 'mbssearch_form', 'action' => $actionurl->out(), 'method' => 'get', 'role' => 'form', 'class' => 'form-horizontal'));
        
        $output = html_writer::tag('div', $output, array('class' => 'row'));
        $output = html_writer::tag('div', $output, array('class' => 'container'));
        $output = html_writer::tag('div', $output, array('class' => 'me-media-search me-search-box'));
        
        // Loading AJAX.
        $config = get_config('block_mbssearch');
        $ajaxurl = new moodle_url('/blocks/mbssearch/ajax.php');
        $opts = array('url' => $ajaxurl->out(), 'lookupcount' => $config->lookupcount);

        $PAGE->requires->yui_module('moodle-block_mbssearch-blocksearch', 'M.block_mbssearch.blocksearch.init', array($opts));

        return $output;
    }
    
    /** render the search result page
     * 
     * @global type $OUTPUT
     * @param type $results
     * @param type $searchtext
     * @param type $filterby
     * @return type
     */
    public function render_search_page($results, $searchtext, $filterby) {
        global $OUTPUT, $PAGE;
        
        // ...input searchtext.
        $stext = html_writer::empty_tag('input', array(
                    'id' => 'searchtext',
                    'class' => 'form-control',
                    'type' => 'text',
                    'name' => 'searchtext',
                    'value' => $searchtext,
                    'placeholder' => get_string('search', 'block_mbssearch') . ' ...'));
        
        $sbutton = html_writer::tag('button', html_writer::tag('i', '', array('class' => 'fa fa-search')),
                array('type' => 'image',
                    'id' => 'search_submitbutton',
                    'name' => 'search',
                    'src' => $OUTPUT->pix_url('a/search'), 
                    'class' => 'btn btn-primary'));
        
        $sbgroup = html_writer::span($sbutton, 'input-group-btn');

        $output = html_writer::tag('div', $stext.$sbgroup, array('class' => 'input-group'));
        $output = html_writer::tag('div', $output, array('class' => 'col-lg-9'));

        // ... input order by.
        $options = array(
            'nofilter' => get_string('schoolandcourse', 'block_mbssearch'),
            'course' => get_string('filterbycourse', 'block_mbssearch'),
            'school' => get_string('filterbyschool', 'block_mbssearch')
        );
        $s = html_writer::select($options, 'filterby', $filterby, false);
        $output .= html_writer::div($s, 'col-lg-3', array('id' => 'mbssearch_filterbywrapper'));

        $actionurl = new moodle_url('/blocks/mbssearch/search.php');
        $output = html_writer::tag('form', $output, array('id' => 'mbssearchpage_form', 'action' => $actionurl->out(), 'method' => 'get'));
        $output = html_writer::div($output, 'row');
        
        // ...resultlist.
        $l = $this->render_resultlist($results);
        $l .= $this->render_more_results_link($results, $searchtext, $filterby);
        $output .= html_writer::tag('div', $l, array('id' => 'mbssearch_result'));
        
        $output = html_writer::div($output, 'mbssearchpage container-fluid');
        
        // ... javascript.
        $ajaxurl = new moodle_url('/blocks/mbssearch/ajax.php');
        $opts = array(
            'url' => $ajaxurl->out(),
            'results' => $results,
            'limitfrom' => $results->limitfrom + $results->limitnum,
            'limitnum' => $results->limitnum
        );

        $PAGE->requires->yui_module('moodle-block_mbssearch-searchpage', 'M.block_mbssearch.initsearchpage', array($opts));

        return $output;
    }
    
    /** renders a course for displaying in result list
     * 
     * @param record $course
     * @return string
     */
    protected function render_course($course) {
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
        $attributes = array('title' => $course->fullname);
        $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
        $coursefullname = format_string(get_course_display_name_for_list($course), true, $course->id);
        $html .= html_writer::start_tag('a', array('class' => 'coursebox-link', 'href' => $courseurl));
        $html .= html_writer::tag('span', $coursefullname, array('class' => 'coursename internal'));
        $category = \local_mbs\local\schoolcategory::get_schoolcategory($course->category);
        $html .= html_writer::tag('p', $category->name, array('class' => 'coursetype'));
        $html .= html_writer::end_tag('a');
        $html .= html_writer::end_tag('div'); //end class 'course_title'
        $html .= html_writer::end_tag('div'); //end class 'coursebox-inner'
        $html .= html_writer::end_tag('li');

        return $html;
    }
    /**
     * Need to overwrite this method because of some bootstrap classes
     * 
     * @param type $results
     * @param type $searchtext
     * @param type $filterby
     * @return string
     */
    protected function render_more_results_link($results, $searchtext, $filterby) {

        // ... are there more results?
        if ($results->limitfrom + $results->limitnum >= $results->total) {
            return '';
        }

        $nextlimitfrom = $results->limitfrom + $results->limitnum;

        $params = array(
            'searchtext' => $searchtext,
            'filterby' => $filterby,
            'limitfrom' => $nextlimitfrom,
            'limitnum' => $results->limitnum
        );

        $url = new moodle_url('/blocks/mbssearch/search.php', $params);
        $text = get_string('loadmoreresults', 'block_mbssearch');

        $o = html_writer::link($url, $text, array(
            'id' => 'loadmoreresults',
            'class' => 'btn btn-primary col-lg-12'));

        return $o;
    }
}