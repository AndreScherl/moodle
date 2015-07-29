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
 * The mebis core core renderer, heavily changed from trio version!
 *
 * @package theme_mebis
 * @copyright 2015 Andreas Wagner, andreas.wagner@isb.bayern.de
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/theme/bootstrap/renderers/course_renderer.php");
require_once($CFG->libdir . '/coursecatlib.php');

class theme_mebis_core_course_renderer extends theme_bootstrap_core_course_renderer {

    /**
     * Renders html to display a course search form
     *
     * @param string $value default value to populate the search field
     * @param string $format display format - 'plain' (default), 'short' or 'navbar'
     * @return string
     */
    public function course_search_form($value = '', $format = 'plain') {
        static $count = 0;
        $formid = 'coursesearch';
        if (( ++$count) > 1) {
            $formid .= $count;
        }
        $inputid = 'coursesearchbox';

        if ($format === 'navbar') {
            $formid = 'coursesearchnavbar';
            $inputid = 'navsearchbox';
        }

        $strsearchcourses = get_string("searchcourses");
        $searchurl = new moodle_url('/course/search.php');

        $output = html_writer::start_div('row');
        $output .= html_writer::start_div('col-md-12');
        $output .= html_writer::start_div('me-search-box');

        $form = array('id' => $formid, 'action' => $searchurl, 'method' => 'get', 'class' => "form-horizontal", 'role' => 'form');
        $output .= html_writer::start_tag('form', $form);
        $output .= html_writer::start_div('row');
        $output .= html_writer::start_div('col-md-12');

        $output .= html_writer::start_div('input-group');
        $output .= html_writer::tag('label', $strsearchcourses, array('for' => $inputid, 'class' => 'sr-only'));
        $search = array('type' => 'text', 'id' => $inputid, 'name' => 'search',
            'class' => 'form-control', 'value' => s($value), 'placeholder' => $strsearchcourses);
        $output .= html_writer::empty_tag('input', $search);
        $button = array('type' => 'submit', 'class' => 'btn btn-primary btn-search');
        $output .= html_writer::start_span('input-group-btn');
        $output .= html_writer::start_tag('button', $button);
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-suchbegriff-bestaetigen'));
        $output .= html_writer::tag('span', get_string('search'), array('class' => 'hidden-xs'));
        $output .= html_writer::end_tag('button');
        $output .= html_writer::end_span();
        $output .= html_writer::end_div(); // Close form-group.

        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_tag('form');

        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

        return $output;
    }

    /**
     * Override the original renderer method, to remove (comment out) some elements
     * of the page.
     * We do this intentionally NOT by css to gain higher performance (for example by
     * removing the coursecat::make_categories_list() select)
     *
     * Invoked from /course/index.php
     *
     * @param int|stdClass|coursecat $category
     */
    public function course_category($category) {
        global $CFG;
        require_once($CFG->libdir . '/coursecatlib.php');
        $coursecat = coursecat::get(is_object($category) ? $category->id : $category);
        $site = get_site();
        $output = '';

        if (can_edit_in_category($category)) {
            // Add 'Manage' button if user has permissions to edit this category.
            $managebutton = $this->single_button(new moodle_url('/course/management.php'), get_string('managecourses'), 'get');
            $this->page->set_button($managebutton);
        }
        /* awag: Don't use the category select element.
          if (!$coursecat->id) {
          if (coursecat::count_all() == 1) {
          // There exists only one category in the system, do not display link to it
          $coursecat = coursecat::get_default();
          $strfulllistofcourses = get_string('fulllistofcourses');
          $this->page->set_title("$site->shortname: $strfulllistofcourses");
          } else {
          $strcategories = get_string('categories');
          $this->page->set_title("$site->shortname: $strcategories");
          }
          } else {
          $this->page->set_title("$site->shortname: ". $coursecat->get_formatted_name());

          // Print the category selector
          $output .= html_writer::start_tag('div', array('class' => 'categorypicker'));
          $select = new single_select(new moodle_url('/course/index.php'), 'categoryid',
          coursecat::make_categories_list(), $coursecat->id, null, 'switchcategory');
          $select->set_label(get_string('categories').':');
          $output .= $this->render($select);
          $output .= html_writer::end_tag('div'); // .categorypicker
          }
         */

        // Print current category description
        $chelper = new coursecat_helper();
        if ($description = $chelper->get_category_formatted_description($coursecat)) {
            $output .= $this->box($description, array('class' => 'generalbox info'));
        }

        // Prepare parameters for courses and categories lists in the tree
        $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_AUTO)
                ->set_attributes(array('class' => 'category-browse category-browse-' . $coursecat->id));

        $coursedisplayoptions = array();
        $catdisplayoptions = array();
        $browse = optional_param('browse', null, PARAM_ALPHA);
        $perpage = optional_param('perpage', $CFG->coursesperpage, PARAM_INT);
        $page = optional_param('page', 0, PARAM_INT);
        $baseurl = new moodle_url('/course/index.php');
        if ($coursecat->id) {
            $baseurl->param('categoryid', $coursecat->id);
        }
        if ($perpage != $CFG->coursesperpage) {
            $baseurl->param('perpage', $perpage);
        }
        $coursedisplayoptions['limit'] = $perpage;
        $catdisplayoptions['limit'] = $perpage;
        if ($browse === 'courses' || !$coursecat->has_children()) {
            $coursedisplayoptions['offset'] = $page * $perpage;
            $coursedisplayoptions['paginationurl'] = new moodle_url($baseurl, array('browse' => 'courses'));
            $catdisplayoptions['nodisplay'] = true;
            $catdisplayoptions['viewmoreurl'] = new moodle_url($baseurl, array('browse' => 'categories'));
            $catdisplayoptions['viewmoretext'] = new lang_string('viewallsubcategories');
        } else if ($browse === 'categories' || !$coursecat->has_courses()) {
            $coursedisplayoptions['nodisplay'] = true;
            $catdisplayoptions['offset'] = $page * $perpage;
            $catdisplayoptions['paginationurl'] = new moodle_url($baseurl, array('browse' => 'categories'));
            $coursedisplayoptions['viewmoreurl'] = new moodle_url($baseurl, array('browse' => 'courses'));
            $coursedisplayoptions['viewmoretext'] = new lang_string('viewallcourses');
        } else {
            // we have a category that has both subcategories and courses, display pagination separately
            $coursedisplayoptions['viewmoreurl'] = new moodle_url($baseurl, array('browse' => 'courses', 'page' => 1));
            $catdisplayoptions['viewmoreurl'] = new moodle_url($baseurl, array('browse' => 'categories', 'page' => 1));
        }
        $chelper->set_courses_display_options($coursedisplayoptions)->set_categories_display_options($catdisplayoptions);
        // Add course search form.
        // awag: Don't provide the course search form here.
        // $output .= $this->course_search_form();
        // Display course category tree.
        $output .= $this->coursecat_tree($chelper, $coursecat);

        // Add action buttons
        /* awag: Don't use any buttons, they are provided from block mbs_newcourse. 
          $output .= $this->container_start('buttons');
          $context = get_category_or_system_context($coursecat->id);
          if (has_capability('moodle/course:create', $context)) {
          // Print link to create a new course, for the 1st available category.
          if ($coursecat->id) {
          $url = new moodle_url('/course/edit.php', array('category' => $coursecat->id, 'returnto' => 'category'));
          } else {
          $url = new moodle_url('/course/edit.php', array('category' => $CFG->defaultrequestcategory, 'returnto' => 'topcat'));
          }
          $output .= $this->single_button($url, get_string('addnewcourse'), 'get');
          }
          ob_start();
          if (coursecat::count_all() == 1) {
          print_course_request_buttons(context_system::instance());
          } else {
          print_course_request_buttons($context);
          }
          $output .= ob_get_contents();
          ob_end_clean();
          $output .= $this->container_end(); */

        return $output;
    }

    /**
     * Renders html to display search result page
     *
     * @param array $searchcriteria may contain elements: search, blocklist, modulelist, tagid
     * @return string
     */
    public function search_courses($searchcriteria) {
        global $CFG;
        $content = '';

        if (!empty($searchcriteria)) {
            // print search results
            require_once($CFG->libdir . '/coursecatlib.php');
            $displayoptions = array('sort' => array('displayname' => 1));
            // take the current page and number of results per page from query
            $perpage = optional_param('perpage', 0, PARAM_RAW);
            if ($perpage !== 'all') {
                $displayoptions['limit'] = ((int) $perpage <= 0) ? $CFG->coursesperpage : (int) $perpage;
                $page = optional_param('page', 0, PARAM_INT);
                $displayoptions['offset'] = $displayoptions['limit'] * $page;
            }
            // options 'paginationurl' and 'paginationallowall' are only used in method coursecat_courses()
            $displayoptions['paginationurl'] = new moodle_url('/course/search.php', $searchcriteria);
            $displayoptions['paginationallowall'] = true; // allow adding link 'View all'
            $class = 'course-search-result';
            foreach ($searchcriteria as $key => $value) {
                if (!empty($value)) {
                    $class .= ' course-search-result-' . $key;
                }
            }
            $chelper = new coursecat_helper();
            $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED_WITH_CAT)->
                    set_courses_display_options($displayoptions)->
                    set_search_criteria($searchcriteria)->
                    set_attributes(array('class' => $class));
            $courses = coursecat::search_courses($searchcriteria, $chelper->get_courses_display_options());
            $totalcount = coursecat::search_courses_count($searchcriteria);
            $courseslist = $this->coursecat_courses($chelper, $courses, $totalcount);

            if (!empty($searchcriteria['search'])) {
                // print search form only if there was a search by search string, otherwise it is confusing
                $content .= $this->box_start('generalbox');
                $content .= $this->course_search_form($searchcriteria['search']);
                $content .= $this->box_end();
            }

            if (!$totalcount) {
                if (!empty($searchcriteria['search'])) {
                    $content .= $this->heading(get_string('nocoursesfound', '', $searchcriteria['search']));
                } else {
                    $content .= $this->heading(get_string('novalidcourses'));
                }
            } else {
                $content .= $this->heading("$totalcount " . get_string('searchresults') . " für '" . $searchcriteria['search'] . "'");
                $content .= $courseslist;
            }
        } else {
            // just print search form
            $content .= $this->course_search_form();
            $content .= $this->box_start('generalbox');
            $content .= html_writer::tag('div', get_string("searchhelp"), array('class' => 'searchhelp'));
            $content .= $this->box_end();
        }
        return $content;
    }

}
