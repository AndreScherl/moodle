<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/theme/bootstrap/renderers/course_renderer.php");
require_once($CFG->libdir . '/coursecatlib.php');

class theme_mebis_core_course_renderer extends theme_bootstrap_core_course_renderer
{
    /**
     * Returns HTML to print list of available courses for the frontpage
     *
     * @return string
     */
    public function frontpage_available_courses()
    {
        global $CFG;
        require_once($CFG->libdir . '/coursecatlib.php');

        $chelper = new coursecat_helper();
        $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED)->
            set_courses_display_options(array(
                'recursive' => true,
                'limit' => 12
                )
        );

        $chelper->set_attributes(array('class' => 'frontpage-course-list-all'));
        $courses = coursecat::get(0)->get_courses($chelper->get_courses_display_options());
        $totalcount = coursecat::get(0)->get_courses_count($chelper->get_courses_display_options());
        if (!$totalcount && !$this->page->user_is_editing() && has_capability('moodle/course:create',
                context_system::instance())) {
            // Print link to create a new course, for the 1st available category.
            return $this->add_new_course_button();
        }
        return $this->course_sortbox($chelper) . $this->coursecat_courses($chelper, $courses, $totalcount) . $this->course_loadmore();
    }

    /**
     * Displays one course in the list of courses.
     *
     * This is an internal function, to display an information about just one course
     * please use {@link core_course_renderer::course_info_box()}
     *
     * @param coursecat_helper $chelper various display options
     * @param course_in_list|stdClass $course
     * @param string $additionalclasses additional classes to add to the main <div> tag (usually
     *    depend on the course position in list - first/last/even/odd)
     * @return string
     */
    protected function coursecat_coursebox(coursecat_helper $chelper, $course, $additionalclasses = '')
    {
        global $CFG;
        if (!isset($this->strings->summary)) {
            $this->strings->summary = get_string('summary');
        }
        if ($chelper->get_show_courses() <= self::COURSECAT_SHOW_COURSES_COUNT) {
            return '';
        }
        if ($course instanceof stdClass) {
            require_once($CFG->libdir . '/coursecatlib.php');
            $course = new course_in_list($course);
        }

        $content = '';

        // .coursebox
        $content .= html_writer::start_tag('li',
                array(
                'class' => 'coursebox',
                'data-courseid' => $course->id,
                'data-type' => self::COURSECAT_TYPE_COURSE,
                )
        );

        // .coursebox-meta
        $content .= html_writer::start_div('coursebox-meta');

        $content .= html_writer::start_div('row');

        $content .= html_writer::start_div('col-md-6 col-xs-6 course-is-new');
        $content .= html_writer::tag('span', get_string('new', 'theme_mebis'));
        $content .= html_writer::end_div();

        //TODO: If is not new, pull-right-class is needed (or change to col-12)
        $content .= html_writer::start_div('col-md-6 col-xs-6 box-type text-right');
        $content .= html_writer::tag('i', '', array('class' => 'icon-me-lernplattform'));

        $content .= html_writer::end_div();

        $content .= html_writer::end_div();
        $content .= html_writer::end_div();

        // .coursebox-inner
        $content .= html_writer::start_div('coursebox-inner ' . $additionalclasses);

        $url = new moodle_url('/course/view.php', array('id' => $course->id));
        $content .= html_writer::start_tag('a', array('class' => 'coursebox-link', 'href' => $url));

        // course name
        $coursename = $chelper->get_course_formatted_name($course);
        $content .= html_writer::tag('span', $coursename, array('class' => 'coursename internal'));

        $cat = coursecat::get($course->category, IGNORE_MISSING);
        if ($cat) {
            $content .= html_writer::tag('p', $cat->get_formatted_name(), array('class' => 'coursetype'));
        }

        // If we display course in collapsed form but the course has summary or course contacts, display the link to the info page.
        $content .= html_writer::start_tag('span', array('class' => 'moreinfo'));
        if ($chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            if ($course->has_summary() || $course->has_course_contacts() || $course->has_course_overviewfiles()) {

                $content .= html_writer::image($this->output->pix_url('i/info'), $this->strings->summary,
                        array('title' => $this->strings->summary));
                // Make sure JS file to expand course content is included.
                $this->coursecat_include_js();
            }
        }
        $content .= html_writer::end_tag('span'); // .moreinfo

        /*
          // print enrolmenticons
          /*if ($icons = enrol_get_course_info_icons($course)) {
          $content .= html_writer::start_tag('div', array('class' => 'enrolmenticons'));
          foreach ($icons as $pix_icon) {
          $content .= $this->render($pix_icon);
          }
          $content .= html_writer::end_tag('div'); // .enrolmenticons
          } */

        $content .= html_writer::end_tag('a'); // .coursebox-link

        $content .= html_writer::end_div(); // .coursebox
        $content .= html_writer::end_tag('li'); // .coursebox

        return $content;
    }

    protected function course_sortbox(coursecat_helper $chelper)
    {
        $cats = coursecat::make_categories_list();
        $sortbox = html_writer::start_div('row');
        $sortbox .= html_writer::start_div('col-md-12');
        $sortbox .= html_writer::start_div('me-course-action-links text-right');
        $sortbox .= html_writer::start_tag('a',
                array('href' => new moodle_url('/course/edit.php', array('category' => '1', 'returnto' => 'category'))));
        $sortbox .= html_writer::tag('i', '', array('class' => 'fa fa-plus-circle'));
        $sortbox .= get_string('course-create', 'theme_mebis');
        $sortbox .= html_writer::end_tag('a');
        $sortbox .= html_writer::start_tag('a', array('href' => '#'));
        $sortbox .= html_writer::tag('i', '', array('class' => 'fa fa-dot-circle-o'));
        $sortbox .= get_string('course-request', 'theme_mebis');
        $sortbox .= html_writer::end_tag('a');
        $sortbox .= html_writer::end_div();
        $sortbox .= html_writer::start_div('course-sorting');
        $sortbox .= html_writer::start_div('me-search-filter');
        $sortbox .= html_writer::start_div('row');
        $sortbox .= html_writer::start_div('col-md-6');
        $sortbox .= html_writer::start_tag('select',
                array('name' => '', 'id' => 'me-select-schools', 'class' => 'form-control'));
        $sortbox .= html_writer::tag('option', get_string('all-schools', 'theme_mebis'), array('value' => 'all'));
        foreach ($cats as $catId => $catName) {
            $sortbox .= html_writer::tag('option', $catName, array('value' => $catId));
        }
        $sortbox .= html_writer::end_tag('select');
        $sortbox .= html_writer::end_div();
        $sortbox .= html_writer::start_div('col-md-4');
        $sortbox .= html_writer::start_tag('select',
                array('name' => '', 'id' => 'me-order-results', 'class' => 'form-control'));
        $sortbox .= html_writer::tag('option', get_string('sort-default', 'theme_mebis'), array('value' => ''));
        $sortbox .= html_writer::tag('option', get_string('sort-manual', 'theme_mebis'), array('value' => 'manual'));
        $sortbox .= html_writer::tag('option', get_string('sort-name', 'theme_mebis'), array('value' => 'name'));
        $sortbox .= html_writer::tag('option', get_string('sort-school', 'theme_mebis'), array('value' => 'school'));
        $sortbox .= html_writer::tag('option', get_string('sort-visit', 'theme_mebis'), array('value' => 'time-visited'));
        $sortbox .= html_writer::tag('option', get_string('sort-created2', 'theme_mebis'), array('value' => 'time-created'));
        $sortbox .= html_writer::end_tag('select');
        $sortbox .= html_writer::end_div();
        $sortbox .= html_writer::start_div('col-md-2 me-render-results text-right');
        $sortbox .= html_writer::start_tag('a', array('href' => '#', 'data-switch' => 'list'));
        $sortbox .= html_writer::tag('i', '', array('class' => 'fa fa-list'));
        $sortbox .= html_writer::end_tag('a');
        $sortbox .= html_writer::start_tag('a', array('href' => '#', 'data-switch' => 'grid'));
        $sortbox .= html_writer::tag('i', '', array('class' => 'fa fa-th'));
        $sortbox .= html_writer::end_tag('a');
        $sortbox .= html_writer::end_div();
        $sortbox .= html_writer::end_div();
        $sortbox .= html_writer::end_div();
        $sortbox .= html_writer::end_div();
        $sortbox .= html_writer::end_div();
        $sortbox .= html_writer::end_div();

        return $sortbox;
    }

    protected function course_loadmore()
    {
        $loadmore = html_writer::start_div('row');
        $loadmore .= html_writer::start_div('col-md-12 add-more-results');
        $loadmore .= html_writer::tag('a', get_string('load-more-results', 'theme_mebis'), array('class' => 'btn'));
        $loadmore .= html_writer::end_div();
        $loadmore .= html_writer::end_div();
        return $loadmore;
    }

    protected function coursecat_category_content(coursecat_helper $chelper, $coursecat, $depth)
    {
        global $CFG;
        require_once($CFG->libdir . '/coursecatlib.php');
        require_once($CFG->libdir . '/enrollib.php');

        $chelper = new coursecat_helper();
        $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED)->
            set_courses_display_options(array(
                'recursive' => true,
                'limit' => 12
                )
        );
        $categoryid = optional_param('categoryid', 0, PARAM_INT);
        $chelper->set_attributes(array('class' => 'frontpage-course-list-all'));
        $result = '';
        $result .= html_writer::start_div('row');
        $result .= html_writer::start_div('col-lg-12 category-box');
        $result .= $this->getCategoryTree($categoryid);
        $result .= html_writer::end_div();
        $result .= html_writer::end_div();
        $totalcount = coursecat::get(0)->get_courses_count($chelper->get_courses_display_options());
        if (!$totalcount && !$this->page->user_is_editing() && has_capability('moodle/course:create',
                context_system::instance())) {
            // Print link to create a new course, for the 1st available category.
            return $this->add_new_course_button();
        }
        return $result;
    }

    public function getCategoryTree($categoryId)
    {
        global $CFG;
        $result = '';
        $categories = coursecat::get($categoryId)->get_children();
        foreach ($categories as $category) {
            $result .= html_writer::start_div('category-container');
            $result .= html_writer::start_div('category-title');
            $result .= html_writer::start_span('category-title-name');
            $result .= html_writer::link(new moodle_url('/course/index.php?categoryid='.$category->id), $category->name);
            $result .= html_writer::end_span();
            if ($category->has_children() || $category->has_courses()) {
                $result .= html_writer::span('','category-toggle');
            }
            $result .= html_writer::end_div();
            if ($category->has_children() || $category->has_courses()) {
                $result .= html_writer::start_div('category-body');
                $result .= $this->getCategoryTree($category->id);
                $result .= html_writer::end_div();
            }
            $result .= html_writer::end_div();
        }
        $courses = coursecat::get($categoryId)->get_courses();
        if (count($courses) > 0) {
            foreach ($courses as $course) {
                if($course->is_uservisible()) {
                    $result .= html_writer::start_div('category-course');
                    $result .= html_writer::start_div('category-course-title');
                    $result .= html_writer::tag('i','',array('class' => 'icon-me-lernplattform'));
                    $result .= html_writer::start_span('category-course-title-name');
                    $result .= html_writer::link(new moodle_url('/course/view.php?id='.$course->id), $course->fullname);
                    $result .= html_writer::end_span();
                    if ($course->has_summary()) {
                        $result .= html_writer::span('','infoToggle icon-me-infoportal');
                    }
                    $icons = enrol_get_course_info_icons($course);
                    if ($icons) {
                        //var_dump($icons);
                        foreach ($icons as $pix_icon){
                            $result .= html_writer::span($this->render($pix_icon), 'accessible');
                        }
//                        if (isset($icons[0]) && $icons[0]->pix === 'withkey'){
//                            $result .= html_writer::span('', 'accessible icon-me-einschreibung-mit-schluessel');
//
//                        }elseif (isset($icons[0]) && $icons[0]->pix === 'withoutkey')
//                        {
//                            $result .= html_writer::span('', 'accessible icon-me-einschreibung-ohne-schluessel');
//                        } else
//                        {
//                            $result .= html_writer::span('', 'accessible icon-me-gastzugang');
//                        }
                    }
                    $result .= html_writer::end_div();
                    if ($course->has_summary()) {
                        $result .= html_writer::start_div('category-course-info');
                        $result .= html_writer::span($course->summary);
                        $result .= html_writer::end_div();
                    }
                    $result .= html_writer::end_div();
                }
            }
        }
        return $result;
    }

    /**
     * Renders html to display a course search form
     *
     * @param string $value default value to populate the search field
     * @param string $format display format - 'plain' (default), 'short' or 'navbar'
     * @return string
     */
    public function course_search_form($value = '', $format = 'plain')
    {
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
     * Renders HTML to display particular course category - list of it's subcategories and courses
     *
     * Invoked from /course/index.php
     *
     * @param int|stdClass|coursecat $category
     * @return string
     */
    public function course_category($category)
    {
        global $CFG;
        require_once($CFG->libdir . '/coursecatlib.php');
        $coursecat = coursecat::get(is_object($category) ? $category->id : $category);
        $site = get_site();
        $output = '';

        $this->page->set_button($this->course_search_form('', 'navbar'));

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

        //$output .= $this->render_category_headline($coursecat->name);

        // Display course category tree
        $output .= $this->coursecat_tree($chelper, $coursecat);

        // Add course search form (if we are inside category it was already added to the navbar)
        if (!$coursecat->id) {
            $output .= $this->course_search_form();
        }

        return $output;
    }


    /**
     * Returns HTML to display a tree of subcategories and courses in the given category
     *
     * @param coursecat_helper $chelper various display options
     * @param coursecat $coursecat top category (this category's name and description will NOT be added to the tree)
     * @return string
     */
    protected function coursecat_tree(coursecat_helper $chelper, $coursecat)
    {
        global $CFG, $PAGE, $OUTPUT;

        $categorycontent = $this->coursecat_category_content($chelper, $coursecat, 0);
        if (empty($categorycontent)) {
            return '';
        }

        // Start content generation
        $content = '';
        $attributes = $chelper->get_and_erase_attributes('course_category_tree clearfix');
        $content .= html_writer::start_tag('div', $attributes);

        require_once($CFG->libdir.'/blocklib.php');

        //$courseblock = new block_mbsnewcourse();

        $bm = new block_manager($PAGE);

        $bm->add_region('mbscoord');
        $bm->load_blocks();

        $content .= html_writer::tag('div', $categorycontent, array('class' => 'content'));

        $content .= html_writer::end_tag('div'); // .course_category_tree

        return $content;
    }

    /**
     * Renders the list of courses
     *
     * This is internal function, please use {@link core_course_renderer::courses_list()} or another public
     * method from outside of the class
     *
     * If list of courses is specified in $courses; the argument $chelper is only used
     * to retrieve display options and attributes, only methods get_show_courses(),
     * get_courses_display_option() and get_and_erase_attributes() are called.
     *
     * @param coursecat_helper $chelper various display options
     * @param array $courses the list of courses to display
     * @param int|null $totalcount total number of courses (affects display mode if it is AUTO or pagination if applicable),
     *     defaulted to count($courses)
     * @return string
     */
    protected function coursecat_courses(coursecat_helper $chelper, $courses, $totalcount = null)
    {
        global $CFG;
        if ($totalcount === null) {
            $totalcount = count($courses);
        }
        if (!$totalcount) {
            // Courses count is cached during courses retrieval.
            return '';
        }
        if ($chelper->get_show_courses() == self::COURSECAT_SHOW_COURSES_AUTO) {
            // In 'auto' course display mode we analyse if number of courses is more or less than $CFG->courseswithsummarieslimit
            if ($totalcount <= $CFG->courseswithsummarieslimit) {
                $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED);
            } else {
                $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_COLLAPSED);
            }
        }
        // prepare content of paging bar if it is needed
        $paginationurl = $chelper->get_courses_display_option('paginationurl');
        $paginationallowall = $chelper->get_courses_display_option('paginationallowall');
        if ($totalcount > count($courses)) {
            // there are more results that can fit on one page
            if ($paginationurl) {
                // the option paginationurl was specified, display pagingbar
                $perpage = $chelper->get_courses_display_option('limit', $CFG->coursesperpage);
                $page = $chelper->get_courses_display_option('offset') / $perpage;
                $pagingbar = $this->paging_bar($totalcount, $page, $perpage,
                    $paginationurl->out(false, array('perpage' => $perpage)));
                if ($paginationallowall) {
                    $pagingbar .= html_writer::tag('div',
                            html_writer::link($paginationurl->out(false, array('perpage' => 'all')),
                                get_string('showall', '', $totalcount)), array('class' => 'paging paging-showall'));
                }
            } else if ($viewmoreurl = $chelper->get_courses_display_option('viewmoreurl')) {
                // the option for 'View more' link was specified, display more link
                $viewmoretext = $chelper->get_courses_display_option('viewmoretext', new lang_string('viewmore'));
                $morelink = html_writer::tag('div', html_writer::link($viewmoreurl, $viewmoretext),
                        array('class' => 'paging paging-morelink'));
            }
        } else if (($totalcount > $CFG->coursesperpage) && $paginationurl && $paginationallowall) {
            // there are more than one page of results and we are in 'view all' mode, suggest to go back to paginated view mode
            $pagingbar = html_writer::tag('div',
                    html_writer::link($paginationurl->out(false, array('perpage' => $CFG->coursesperpage)),
                        get_string('showperpage', '', $CFG->coursesperpage)),
                    array('class' => 'paging paging-showperpage'));
        }
        // display list of courses
        $attributes = $chelper->get_and_erase_attributes('courses');
        $content = html_writer::start_tag('div', $attributes);
        if (!empty($pagingbar)) {
            $content .= html_writer::start_div('row pagination-row');
            $content .= html_writer::tag('div', $pagingbar, array('class' => 'col-md-12'));
            $content .= html_writer::end_div();
        }
        $coursecount = 0;
        $content .= html_writer::start_div('row');
        $content .= html_writer::start_div('col-md-12');
        $content .= html_writer::start_tag('ul',
                array('class' => 'block-grid-xs-1 block-grid-xc-2 block-grid-md-3 course_list courses'));
        foreach ($courses as $course) {
            $coursecount ++;
            $classes = ($coursecount % 2) ? 'odd' : 'even';
            if ($coursecount == 1) {
                $classes .= ' first';
            }
            if ($coursecount >= count($courses)) {
                $classes .= ' last';
            }
            $content .= $this->coursecat_coursebox($chelper, $course, $classes);
        }
        $content .= html_writer::end_tag('ul');
        $content .= html_writer::end_div();
        $content .= html_writer::end_div();
        if (!empty($pagingbar)) {
            $content .= html_writer::start_div('row pagination-row');
            $content .= html_writer::tag('div', $pagingbar, array('class' => 'col-md-12'));
            $content .= html_writer::end_div();
        }
        if (!empty($morelink)) {
            $content .= $morelink;
        }
        $content .= html_writer::end_tag('div'); // .courses
        return $content;
    }

    /**
     * Renders html to display search result page
     *
     * @param array $searchcriteria may contain elements: search, blocklist, modulelist, tagid
     * @return string
     */
    public function search_courses($searchcriteria)
    {
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

    /**
     * Renders course headline
     * @param  string
     * @return string
     */
    protected function render_category_headline($headline)
    {
        $category_headline = html_writer::start_tag('div', array('class' => 'category-headline'));
        $category_headline .= html_writer::tag('h1', $headline);
        $category_headline .= html_writer::end_tag('div');
        return $category_headline;
    }
}
