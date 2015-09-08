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
 * Renderer for block mbssearch and search page
 *
 * @package   block_search
 * @copyright 2015 Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_mbssearch_renderer extends plugin_renderer_base {

    /** returns html code for a search form used directly in block search */
    protected static function render_search_form() {
        global $OUTPUT, $PAGE;

        $output = html_writer::tag('label', get_string('searchschoolandcourse', 'block_mbssearch'), array('for' => 'searchtext'));

        $output .= html_writer::empty_tag('input', array('id' => 'searchtext',
                    'type' => 'text',
                    'name' => 'searchtext',
                    'value' => '',
                    'placeholder' => get_string('search', 'block_mbssearch') . ' ...')
                );

        $output .= html_writer::empty_tag('input', array('type' => 'image',
                    'id' => 'search_submitbutton',
                    'name' => 'search',
                    'src' => $OUTPUT->pix_url('a/search')));

        // ... if we are in category context of a school (i. e. a category with depth >= 3, show "search only in" option.
        if ($PAGE->context->contextlevel == CONTEXT_COURSECAT) {

            $categoryid = $PAGE->context->instanceid;

            if ($schoolcat = \local_mbs\local\schoolcategory::get_schoolcategory($categoryid)) {

                $output .= html_writer::empty_tag('input', array('type' => 'checkbox',
                            'id' => 'search_schoolcatid',
                            'name' => 'search_schoolcatid',
                            'value' => $schoolcat->id));

                $output .= html_writer::tag('label', get_string('searchonlyin', 'block_mbssearch', $schoolcat->name), array('for' => 'search_schoolcatid'));
            }
        }

        $actionurl = new moodle_url('/blocks/mbssearch/search.php');
        $output = html_writer::tag('form', $output, array('id' => 'mbssearch_form', 'action' => $actionurl->out(), 'method' => 'get'));

        $config = get_config('block_mbssearch');
        $ajaxurl = new moodle_url('/blocks/mbssearch/ajax.php');
        $opts = array('url' => $ajaxurl->out(), 'lookupcount' => $config->lookupcount);

        $PAGE->requires->yui_module('moodle-block_mbssearch-blocksearch', 'M.block_mbssearch.blocksearch.init', array($opts));

        return $output;
    }

    /** render the block content */
    public function render_block_content() {
        return $this->render_search_form();
    }

    /** renders a school for displaying on the result page
     * 
     * @param record $school a course category of particular depth
     * @return sting
     */
    protected function render_school($school) {

        $output = html_writer::start_tag("li", array('class' => 'schoolbox'));
        $output .= html_writer::start_div('schoolbox-meta');
        $output .= html_writer::start_div('row');
        $output .= html_writer::start_div('col-xs-12 box-type text-right');
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-schule'));
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('schoolbox-inner');
        $link = new moodle_url("/course/index.php?categoryid=" . $school->id);
        $output .= html_writer::start_tag('a', array('class' => 'schoolbox-link', 'href' => $link));
        $output .= html_writer::tag('span', $school->name, array('class' => 'schoolname internal'));
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_div();
        $output .= html_writer::end_tag("li");
        return $output;
    }

    /** renders a course for displaying in result list
     * 
     * @param record $course
     * @return string
     */
    protected function render_course($course) {

        // ...start coursebox.
        $list = html_writer::start_tag("li", array('class' => 'coursebox'));
        $list .= html_writer::start_div('col-sm-12');
        $list .= html_writer::start_div('category-coursebox');
        $list .= html_writer::div('', 'iconbox');
        $list .= html_writer::div('NEU', 'newbox');
        $url = new moodle_url('/course/view.php', array('id' => $course->id));
        $list .= html_writer::link($url, $course->fullname);
        $list .= html_writer::end_div();
        $list .= html_writer::end_div();
        $list .= html_writer::end_tag("li");
        // ...end coursebox.

        return $list;
    }

    /**
     * Construct list of users schools
     *
     * @return string html to be displayed
     */
    public function render_resultlist($results) {

        if (empty($results->items)) {
            return get_string('noresults', 'block_mbssearch');
        }

        $output = html_writer::start_tag('div', array('class' => 'col-md-12'));
        $output .= html_writer::start_tag("ul", array("class" => "block-grid-xs-1 block-grid-xc-2 block-grid-md-3", 'id' => 'mbssearch_resultlist'));

        foreach ($results->items as $result) {

            if ($result->type == 'course') {
                $output .= $this->render_course($result->data);
            } else {
                if ($result->type == 'school') {
                    $output .= $this->render_school($result->data);
                }
            }
        }
        $output .= html_writer::end_tag("ul");
        $output .= html_writer::end_tag('div');

        return $output;
    }

    protected function render_more_results_link($results, $searchtext, $filterby) {

        // ... are there more results?
        if ($results->limitfrom + $results->limitnum >= $results->total) {
            return '';
        }

        $nextlimitfrom = $results->limitfrom + $results->limitnum;

        $params = array(
            'searchtext' => $searchtext,
            'schoolcatid' => $results->schoolcatid,
            'filterby' => $filterby,
            'limitfrom' => $nextlimitfrom,
            'limitnum' => $results->limitnum
        );

        $url = new moodle_url('/blocks/mbssearch/search.php', $params);
        $text = get_string('loadmoreresults', 'block_mbssearch');

        $o = html_writer::link($url, $text, array('id' => 'loadmoreresults'));

        return $o;
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
        $s = html_writer::empty_tag('input', array('id' => 'searchtext',
                    'type' => 'text',
                    'name' => 'searchtext',
                    'value' => $searchtext,
                    'placeholder' => get_string('search', 'block_mbssearch') . ' ...',
                    'size' => 80));
        
        $s .= html_writer::empty_tag('input', array('type' => 'image',
                    'id' => 'search_submitbutton',
                    'name' => 'search',
                    'src' => $OUTPUT->pix_url('a/search')));

        $output = html_writer::tag('div', $s, array('class' => 'mbssearch_inputwrapper'));

        // ... input order by.
        $options = array(
            'nofilter' => get_string('schoolandcourse', 'block_mbssearch'),
            'course' => get_string('filterbycourse', 'block_mbssearch'),
            'school' => get_string('filterbyschool', 'block_mbssearch')
        );
        $s = html_writer::select($options, 'filterby', $filterby, false);
        $output .= html_writer::tag('div', $s, array('id' => 'mbssearch_filterbywrapper'));

        $actionurl = new moodle_url('/blocks/mbssearch/search.php');
        $output = html_writer::tag('form', $output, array('id' => 'mbssearchpage_form', 'action' => $actionurl->out(), 'method' => 'get'));

        // ...resultlist.
        $l = $this->render_resultlist($results);
        $l .= $this->render_more_results_link($results, $searchtext, $filterby);
        $output .= html_writer::tag('div', $l, array('id' => 'mbssearch_result'));

        // ... javascript.
        $ajaxurl = new moodle_url('/blocks/mbssearch/ajax.php');
        $opts = array(
            'url' => $ajaxurl->out(),
            'results' => $results,
            'limitfrom' => $results->limitfrom + $results->limitnum,
            'limitnum' => $results->limitnum,
            'schoolcatid' => $results->schoolcatid
        );

        $PAGE->requires->yui_module('moodle-block_mbssearch-searchpage', 'M.block_mbssearch.initsearchpage', array($opts));

        return $output;
    }


    /** render the html retreived by ajax call
     * 
     * @param stdClass $results hold the results of the query with additional info (total, limitform, limitnum)
     * @param string $searchtext the expression to search
     * @param string $filterby the sortorder of results
     * @return string listitems for the unordered list
     */
    public function render_more_results_ajax($results, $searchtext, $filterby) {

        if (empty($results->items)) {
            return '';
        }

        $output = '';

        foreach ($results->items as $result) {

            if ($result->type == 'course') {
                $output .= $this->render_course($result->data);
            } else {
                if ($result->type == 'school') {
                    $output .= $this->render_school($result->data);
                }
            }
        }

        return $output;
    }

}
