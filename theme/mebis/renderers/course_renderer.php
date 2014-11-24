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

        $chelper->set_attributes(array('class' => 'frontpage-course-list-all row'));
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
        $classes = trim('col-lg-4 coursebox');
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

        $url = new moodle_url('/course/info.php', array('id' => $course->id));
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
        $sortbox .= html_writer::start_div('col-md-3');
        $sortbox .= html_writer::start_tag('select', array('name' => '', 'id' => 'me-order-results', 'class' => 'form-control'));
        $sortbox .= html_writer::tag('option', 'Sortieren nach...', array('value' => ''));
        $sortbox .= html_writer::tag('option', 'manuelle Reihenfolge', array('value' => 'manual'));
        $sortbox .= html_writer::tag('option', 'Name', array('value' => 'name'));
        $sortbox .= html_writer::tag('option', 'Schule', array('value' => 'school'));
        $sortbox .= html_writer::tag('option', 'Zeit des Besuches', array('value' => 'time-visited'));
        $sortbox .= html_writer::tag('option', 'Zeit der Erstellung', array('value' => 'time-created'));
        $sortbox .= html_writer::end_tag('select');
        $sortbox .= html_writer::end_div();
        $sortbox .= html_writer::start_div('col-md-3 me-render-results text-right');
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
}