<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/theme/bootstrap/renderers/course_renderer.php");
require_once($CFG->libdir. '/coursecatlib.php');

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
        if (!$totalcount && !$this->page->user_is_editing() && has_capability('moodle/course:create', context_system::instance())) {
            // Print link to create a new course, for the 1st available category.
            return $this->add_new_course_button();
        }
        return $this->course_sortbox($chelper).$this->coursecat_courses($chelper, $courses, $totalcount).$this->course_loadmore();
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
        $classes = trim('col-xs-12 col-sm-6 col-md-4 coursebox');
        if ($chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            $classes .= ' collapsed';
        }

        // .coursebox
        $content .= html_writer::start_div($classes, array(
                'data-courseid' => $course->id,
                'data-type' => self::COURSECAT_TYPE_COURSE,
            )
        );

        // .coursebox-meta
        $content .= html_writer::start_div('coursebox-meta');

        $content .= html_writer::start_div('row');

        //TODO: figure out if new or not, gettext ?
        $content .= html_writer::start_div('col-md-6 course-is-new');
        $content .= html_writer::tag('span', 'NEU');
        $content .= html_writer::end_div();

        //TODO: If is not new, pull-right-class is needed (or change to col-12)
        $content .= html_writer::start_div('col-md-6 box-type text-right');
        $content .= html_writer::tag('i', '', array('class' => 'icon-me-lernplattform'));

        $content .= html_writer::end_div();

        $content .= html_writer::end_div();
        $content .= html_writer::end_div();

        // .coursebox-inner
        $content .= html_writer::start_div('coursebox-inner '. $additionalclasses);

        $url = new moodle_url('/course/view.php', array('id' => $course->id));
        $content .= html_writer::start_tag('a', array('class' => 'coursebox-link', 'href' => $url));


        // course name
        $coursename = $chelper->get_course_formatted_name($course);
        $content .= html_writer::tag('span', $coursename, array('class' => 'coursename'));

        $cat = coursecat::get($course->category, IGNORE_MISSING);
        if ($cat) {
            $content .= html_writer::tag('p', $cat->get_formatted_name(), array('class' => 'coursetype'));
        }

        // If we display course in collapsed form but the course has summary or course contacts, display the link to the info page.
        $content .= html_writer::start_tag('span', array('class' => 'moreinfo'));
        if ($chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            if ($course->has_summary() || $course->has_course_contacts() || $course->has_course_overviewfiles()) {

                $content .= html_writer::image($this->output->pix_url('i/info'), $this->strings->summary, array('title' => $this->strings->summary));
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
        }*/

        $content .= html_writer::end_tag('a'); // .coursebox-link


        $content .= html_writer::start_tag('span', array('class' => 'vbox'));
        $content .= html_writer::tag('i', '', array('class' => 'icon-me-pfeil-weiter'));
        $content .= html_writer::end_tag('span');

        $content .= html_writer::end_div(); // .coursebox
        $content .= html_writer::end_div(); // .coursebox
        return $content;
    }

    protected function course_sortbox(coursecat_helper $chelper)
    {
        $cats = coursecat::make_categories_list();
        $sortbox = html_writer::start_div('row');
        $sortbox .= html_writer::start_div('col-md-12');
        $sortbox .= html_writer::start_div('me-course-action-links text-right');
        $sortbox .= html_writer::start_tag('a', array('href' => new moodle_url('/course/edit.php', array('category' => '1','returnto' => 'category'))));
        $sortbox .= html_writer::tag('i', '', array('class' => 'fa fa-plus-circle'));
        $sortbox .= 'Kurs erstellen';
        $sortbox .= html_writer::end_tag('a');
        $sortbox .= html_writer::start_tag('a', array('href' => '#'));
        $sortbox .= html_writer::tag('i', '', array('class' => 'fa fa-dot-circle-o'));
        $sortbox .= 'Kurs anfordern';
        $sortbox .= html_writer::end_tag('a');
        $sortbox .= html_writer::end_div();
        $sortbox .= html_writer::start_div('course-sorting');
        $sortbox .= html_writer::start_div('me-search-filter');
        $sortbox .= html_writer::start_div('row');
        $sortbox .= html_writer::start_div('col-md-6');
        $sortbox .= html_writer::start_tag('select', array('name' => '', 'id' => 'me-select-schools', 'class' => 'form-control'));
        $sortbox .= html_writer::tag('option', 'Alle meine Schulen', array('value' => 'all'));
        foreach ($cats as $catId => $catName){
            $sortbox .= html_writer::tag('option', $catName, array('value' => $catId));
        }
        $sortbox .= html_writer::end_tag('select');
        $sortbox .= html_writer::end_div();
        $sortbox .= html_writer::start_div('col-md-4');
        $sortbox .= html_writer::start_tag('select', array('name' => '', 'id' => 'me-order-results', 'class' => 'form-control'));
        $sortbox .= html_writer::tag('option', 'Sortieren nach...', array('value' => ''));
        $sortbox .= html_writer::tag('option', 'manuelle Reihenfolge', array('value' => 'manual'));
        $sortbox .= html_writer::tag('option', 'Name', array('value' => 'name'));
        $sortbox .= html_writer::tag('option', 'Schule', array('value' => 'school'));
        $sortbox .= html_writer::tag('option', 'Zeit des Besuches', array('value' => 'time-visited'));
        $sortbox .= html_writer::tag('option', 'Zeit der Erstellung', array('value' => 'time-created'));
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
        $loadmore .= html_writer::tag('a', 'Weitere Ergebnisse laden...', array('class' => 'btn'));
        $loadmore .= html_writer::end_div();
        $loadmore .= html_writer::end_div();
        return $loadmore;
    }

    protected function coursecat_category_content(coursecat_helper $chelper, $coursecat, $depth) {
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
        if (!$totalcount && !$this->page->user_is_editing() && has_capability('moodle/course:create', context_system::instance())) {
            // Print link to create a new course, for the 1st available category.
            return $this->add_new_course_button();
        }
        return $this->coursecat_courses($chelper, $courses, $totalcount);
    }


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
        if ((++$count) > 1) {
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
        $output .= html_writer::tag('span', get_string('search'), array('class'=>'hidden-xs'));
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
    protected function coursecat_courses(coursecat_helper $chelper, $courses, $totalcount = null) {
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
                    $pagingbar .= html_writer::tag('div', html_writer::link($paginationurl->out(false, array('perpage' => 'all')),
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
            $pagingbar = html_writer::tag('div', html_writer::link($paginationurl->out(false, array('perpage' => $CFG->coursesperpage)),
                get_string('showperpage', '', $CFG->coursesperpage)), array('class' => 'paging paging-showperpage'));
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
        foreach ($courses as $course) {
            $coursecount ++;
            $classes = ($coursecount%2) ? 'odd' : 'even';
            if ($coursecount == 1) {
                $classes .= ' first';
            }
            if ($coursecount >= count($courses)) {
                $classes .= ' last';
            }
            $content .= $this->coursecat_coursebox($chelper, $course, $classes);
        }
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
    public function search_courses($searchcriteria) {
        global $CFG;
        $content = '';

        if (!empty($searchcriteria)) {
            // print search results
            require_once($CFG->libdir. '/coursecatlib.php');
            $displayoptions = array('sort' => array('displayname' => 1));
            // take the current page and number of results per page from query
            $perpage = optional_param('perpage', 0, PARAM_RAW);
            if ($perpage !== 'all') {
                $displayoptions['limit'] = ((int)$perpage <= 0) ? $CFG->coursesperpage : (int)$perpage;
                $page = optional_param('page', 0, PARAM_INT);
                $displayoptions['offset'] = $displayoptions['limit'] * $page;
            }
            // options 'paginationurl' and 'paginationallowall' are only used in method coursecat_courses()
            $displayoptions['paginationurl'] = new moodle_url('/course/search.php', $searchcriteria);
            $displayoptions['paginationallowall'] = true; // allow adding link 'View all'
            $class = 'course-search-result';
            foreach ($searchcriteria as $key => $value) {
                if (!empty($value)) {
                    $class .= ' course-search-result-'. $key;
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
                $content .= $this->heading("$totalcount " . get_string('searchresults'). " für '" . $searchcriteria['search'] . "'");
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