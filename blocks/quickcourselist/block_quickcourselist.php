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
 * Defines the class for the Quick Course List block
 *
 * @package    block_quickcourselist
 * @author      Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @copyright   2010 Tauntons College, UK
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class definition for the Quick Course List Block
 *
 * @uses block_base
 */
class block_quickcourselist extends block_base {

    public function init() {
        $this->content_type = BLOCK_TYPE_TEXT;
        $this->title = get_string('quickcourselist', 'block_quickcourselist');
    }

    //stop it showing up on any add block lists
    public function applicable_formats() {
        return (array('all' => false, 'site' => true, 'my' => true));
    }

    public function getcategoryHTML() {
         global $CFG, $DB;

        $context_block = $this->context;
         $search = optional_param('quickcategorylistsearch', '', PARAM_TEXT);
        $quickcategorysubmit = optional_param('quickcategorysubmit', false, PARAM_TEXT);

            $inputattrs = array(
                'autocomplete' => 'off',
                'name' => 'quickcategorylistsearch',
                'id' => 'quickcategorylistsearch',
                'value' => $search
            );
            $input = get_string('search_categories', 'block_quickcourselist')."<br />".html_writer::empty_tag('input', $inputattrs);
            $progressattrs = array(
                'src' => $this->page->theme->pix_url('i/loading_small', 'moodle'),
                'class' => 'quickcategoryprogress',
                'id' => 'quickcategoryprogress',
                'alt' => get_string('loading', 'block_quickcourselist')
            );
            $progress = html_writer::empty_tag('img', $progressattrs);
            $submitattrs = array(
                'type' => 'submit',
                'name' => 'quickcategorysubmit',
                'class' => 'submitbutton',
                'value' => get_string('search')
            );
            $submit = html_writer::empty_tag('input', $submitattrs);
            $formattrs = array(
                'id' => 'quickcategoryform',
                'method' => 'post',
                'action' => $this->page->url->out().'#quickcategorylistanchor'
            );
            
            $form = html_writer::tag('form', $input.$progress.$submit, $formattrs);

            $list_contents = "";
            if (!empty($quickcategorysubmit)) {
                $params = array("%$search%");
                $where = 'name LIKE ? ';
 
                if (!has_capability('moodle/category:viewhiddencategories', $context_block)) {
                    $where .= ' AND visible = 1';
                }

                if ($categorys = $DB->get_records_select('course_categories', $where, $params)) {
                    foreach ($categorys as $category) {
                        $url = new moodle_url('/category/view.php', array('id' => $category->id));
                        $link = html_writer::tag('a',
                                                 $category->name,
                                                 array('href' => $url->out()));
                        $li = html_writer::tag('li', $link);
                        $list_contents .= $li;
                    }
                }
            }

            $list = html_writer::tag('ul', $list_contents, array('id' => 'quickcategorylist'));

            return $form.$list;
    }

    

    /**
     * Displays the form for searching courses, and the results if a search as been submitted
     *
     * @access public
     * @return
     */
    public function get_content() {
        global $CFG, $DB;
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        $context_block = $this->context;
        $search = optional_param('quickcourselistsearch', '', PARAM_TEXT);
        $quickcoursesubmit = optional_param('quickcoursesubmit', false, PARAM_TEXT);

        if (has_capability('block/quickcourselist:use', $context_block)) {

            $list_contents = '';
            $anchor = html_writer::tag('a', '', array('name' => 'quickcourselistanchor'));
            $inputattrs = array(
                'autocomplete' => 'off',
                'name' => 'quickcourselistsearch',
                'id' => 'quickcourselistsearch',
                'value' => $search
            );
            $input = get_string('search_courses', 'block_quickcourselist')."<br />".html_writer::empty_tag('input', $inputattrs);
            $progressattrs = array(
                'src' => $this->page->theme->pix_url('i/loading_small', 'moodle'),
                'class' => 'quickcourseprogress',
                'id' => 'quickcourseprogress',
                'alt' => get_string('loading', 'block_quickcourselist')
            );
            $progress = html_writer::empty_tag('img', $progressattrs);
            $submitattrs = array(
                'type' => 'submit',
                'name' => 'quickcoursesubmit',
                'class' => 'submitbutton',
                'value' => get_string('search')
            );
            $submit = html_writer::empty_tag('input', $submitattrs);
            $formattrs = array(
                'id' => 'quickcourseform',
                'method' => 'post',
                'action' => $this->page->url->out().'#quickcourselistanchor'
            );
            $form = html_writer::tag('form', $input.$progress.$submit, $formattrs);

            if (!empty($quickcoursesubmit)) {
                $params = array(SITEID, "%$search%", "%$search%");
                $where = 'id != ? AND (shortname LIKE ? OR fullname LIKE ?)';

                if (!has_capability('moodle/course:viewhiddencourses', $context_block)) {
                    $where .= ' AND visible = 1';
                }

                if ($courses = $DB->get_records_select('course', $where, $params)) {
                    foreach ($courses as $course) {
                        $url = new moodle_url('/course/view.php', array('id' => $course->id));
                        $link = html_writer::tag('a',
                                                 $course->shortname.': '.$course->fullname,
                                                 array('href' => $url->out()));
                        $li = html_writer::tag('li', $link);
                        $list_contents .= $li;
                    }
                }
            }

            $list = html_writer::tag('ul', $list_contents, array('id' => 'quickcourselist'));

            $this->content->text = $anchor.$form.$list.$this->getcategoryHTML();

            $jsmodule = array(
                'name'  =>  'block_quickcourselist',
                'fullpath'  =>  '/blocks/quickcourselist/module.js',
                'requires'  =>  array('base', 'node', 'json', 'io')
            );
            $jsdata = array(
                'instanceid' => $this->instance->id,
                'sesskey' => sesskey()
            );

            $this->page->requires->js_init_call('M.block_quickcourselist.init',
                                                $jsdata,
                                                false,
                                                $jsmodule);
            $this->page->requires->js_init_call('M.block_quickcategorylist.init',
                                                $jsdata,
                                                false,
                                                $jsmodule);
        }
        $this->content->footer='';
        return $this->content;
    }
}