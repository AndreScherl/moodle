<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/onetopic/renderer.php');

/**
 * Basic renderer for onetopic format.
 */
class theme_mebis_format_onetopic_renderer extends format_onetopic_renderer
{

    /**
     * Generate next/previous section links for navigation
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param int $sectionno The section number in the coruse which is being dsiplayed
     * @return array associative array with previous and next section link
     */
    protected function get_nav_links($course, $sections, $sectionno)
    {

        // FIXME: This is really evil and should by using the navigation API.
        $course = course_get_format($course)->get_course();
        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id))
        or !$course->hiddensections;

        $links = array('previous' => '', 'next' => '');
        $back = $sectionno - 1;

        while ((($back > 0 && $course->realcoursedisplay == COURSE_DISPLAY_MULTIPAGE) || ($back >= 0 && $course->realcoursedisplay != COURSE_DISPLAY_MULTIPAGE)) &&
            empty($links['previous'])) {
            if ($canviewhidden || $sections[$back]->uservisible) {
                $params = array();
                if (!$sections[$back]->visible) {
                    $params = array('class' => 'dimmed_text');
                }
                $previouslink = html_writer::tag('span', html_writer::tag('i', '', array('class' => 'icon-me-pfeil-zurueck')), array('class' => 'larrow'));
                $previouslink .= html_writer::tag('span', get_section_name($course, $sections[$back]), array('class' => 'hidden-sm hidden-xs'));
                $links['previous'] = html_writer::link(course_get_url($course, $back), $previouslink, $params);
            }
            $back--;
        }

        $forward = $sectionno + 1;
        while ($forward <= $course->numsections and empty($links['next'])) {
            if ($canviewhidden || $sections[$forward]->uservisible) {
                $params = array();
                if (!$sections[$forward]->visible) {
                    $params = array('class' => 'dimmed_text');
                }
                $nextlink = html_writer::tag('span', get_section_name($course, $sections[$forward]), array('class' => 'hidden-sm hidden-xs'));
                $nextlink .= html_writer::tag('span', html_writer::tag('i', '', array('class' => 'icon-me-pfeil-weiter')), array('class' => 'rarrow'));
                $links['next'] = html_writer::link(course_get_url($course, $forward), $nextlink, $params);
            }
            $forward++;
        }

        echo $this->render_page_action_menu($course, $sections, 'simple');

        return $links;
    }

    /**
     * Output the html for a single section page .
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param array $mods used for print_section()
     * @param array $modnames used for print_section()
     * @param array $modnamesused used for print_section()
     * @param int $displaysection The section number in the course which is being displayed
     */
    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection)
    {
        global $PAGE;

        $real_course_display = $course->realcoursedisplay;
        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();
        $course->realcoursedisplay = $real_course_display;
        $sections = $modinfo->get_section_info_all();

        // Can we view the section in question?
        $context = context_course::instance($course->id);
        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context);

        if (!isset($sections[$displaysection])) {
            // This section doesn't exist
            print_error('unknowncoursesection', 'error', null, $course->fullname);
            return;
        }

        echo html_writer::start_tag('div', array('class' => 'course course-format-onetopic'));

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, $displaysection);

        // General section if non-empty and course_display is multiple.
        if ($course->realcoursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            $thissection = $sections[0];
            if ($thissection->summary or $thissection->sequence or $PAGE->user_is_editing()) {
                echo $this->start_section_list();
                echo $this->section_header($thissection, $course, true);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
                echo $this->courserenderer->course_section_add_cm_control($course, 0, $displaysection);

                echo $this->section_footer();
                echo $this->end_section_list();
            }
        }

        echo $this->render_course_headline($course->fullname);

        // Start single-section div
        echo html_writer::start_tag('div', array('class' => 'single-section onetopic'));

        //Move controls
        $can_move = false;
        if ($PAGE->user_is_editing() && has_capability('moodle/course:movesections', $context) && $displaysection > 0) {
            $can_move = true;
        }
        $move_list_html = '';
        $count_move_sections = 0;

        //Init custom tabs
        $section = 0;

        $sectionmenu = array();
        $tabs = array();

        $default_topic = -1;

        while ($section <= $course->numsections) {

            if ($course->realcoursedisplay == COURSE_DISPLAY_MULTIPAGE && $section == 0) {
                $section++;
                continue;
            }

            $thissection = $sections[$section];

            $showsection = true;
            if (!$thissection->visible) {
                $showsection = false;
            } else if ($section == 0 && !($thissection->summary or $thissection->sequence or $PAGE->user_is_editing())) {
                $showsection = false;
            }

            if (!$showsection) {
                $showsection = (has_capability('moodle/course:viewhiddensections', $context) or !$course->hiddensections);
            }

            if (isset($displaysection)) {
                if ($showsection) {

                    if ($default_topic < 0) {
                        $default_topic = $section;

                        if ($displaysection == 0) {
                            $displaysection = $default_topic;
                        }
                    }

                    $sectionname = get_section_name($course, $thissection);

                    if ($displaysection != $section) {
                        $sectionmenu[$section] = $sectionname;
                    }

                    if ($section == 0) {
                        $url = new moodle_url('/course/view.php', array('id' => $course->id, 'section' => 0));
                    } else {
                        $url = course_get_url($course, $section);
                    }

                    $tabs[] = new tabobject("tab_topic_" . $section, $url,
                        s($sectionname), s($sectionname)
                    );

                    //Init move section list***************************************************************************
                    if ($can_move && $displaysection != $section) {
                        if ($section > 0) { // Move section
                            $baseurl = course_get_url($course, $displaysection);
                            $baseurl->param('sesskey', sesskey());

                            $url = clone($baseurl);

                            $url->param('move', $section - $displaysection);

                            $move_list_html .= html_writer::tag('li', html_writer::link($url, $sectionname));
                        }
                    }
                    //End move section list***************************************************************************
                }
            }

            $section++;
        }

        // Title with section navigation links.
        $sectionnavlinks = $this->get_nav_links($course, $sections, $displaysection);
        $sectiontitle = '';

        if (!$course->hidetabsbar && count($tabs) > 0) {
            $sectiontitle .= print_tabs(array($tabs), "tab_topic_" . $displaysection, null, null, true);
        }

        echo str_replace('nav-tabs nav-justified', 'nav-tabs', $sectiontitle);

        if (!$sections[$displaysection]->uservisible && !$canviewhidden) {
            if (!$course->hiddensections) {
                echo $this->start_section_list();
                echo $this->section_hidden($displaysection);
                echo $this->end_section_list();
            }
            // Can't view this section.
        } else {

            // Now the list of sections..
            echo $this->start_section_list();

            // The requested section page.
            $thissection = $sections[$displaysection];
            echo $this->section_header($thissection, $course, true);
            // Show completion help icon.
            $completioninfo = new completion_info($course);
            echo $completioninfo->display_help_icon();

            echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
            echo $this->courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);
            echo $this->section_footer();
            echo $this->end_section_list();
        }

        // Display section bottom navigation.
        $sectionbottomnav = '';
        $sectionbottomnav .= html_writer::start_div('section-navigation');
        $sectionbottomnav .= html_writer::start_div('row');

        //@FIXME: why did I have to override this entire method just so I could do this. THTBABW
        if (!empty($sectionnavlinks['previous'])) {
            $sectionbottomnav .= html_writer::tag('div', $sectionnavlinks['previous'], array('class' => 'col-md-5 col-xs-2'));
        }
        if (!empty($sectionnavlinks['next'])) {
            if (!empty($sectionnavlinks['previous'])) {
                $sectionbottomnav .= html_writer::tag('div', $sectionnavlinks['next'], array('class' => 'col-md-5 col-md-offset-2 col-xs-offset-8 col-xs-2'));
            } else {
                $sectionbottomnav .= html_writer::tag('div', $sectionnavlinks['next'], array('class' => 'col-md-5 col-md-offset-7 col-xs-offset-10 col-xs-2'));
            }
        }
        $sectionbottomnav .= html_writer::end_div();
        $sectionbottomnav .= html_writer::end_div();
        echo $sectionbottomnav;

        // close single-section div.
        echo html_writer::end_tag('div');

        //Move controls
        if ($can_move && !empty($move_list_html)) {
            echo html_writer::start_tag('div', array('class' => 'move-list-box'));
            print_string('movesectionto', 'format_onetopic');
            echo html_writer::tag('ul', $move_list_html, array('class' => 'move-list'));
            echo html_writer::end_tag('div');
        }

        echo html_writer::end_tag('div');
    }

    protected function render_page_action_menu($course, $sections, $onlyMobile=false)
    {
        //Add side jump-navigation
        $menu_items = array();
        $output = '';

        if($onlyMobile != 'simple') {

            if(count($sections)) {
                for($i = 1;$i <= $course->numsections;$i++){
                    if($sections[$i]->uservisible && $sections[$i]->visible && $sections[$i]->available ){
                        $menu_items[] = html_writer::link('#section-'.$i, '<span>'.$this->section_title($sections[$i], $course).'</span>',
                            array('class' => 'jumpnavigation-point', 'data-scroll' => '#section-'.$i));
                    }
                }
            }
        }

        $visibleClass = ($onlyMobile && $onlyMobile != 'simple') ? ' visible-xs' : '';
        $output = html_writer::start_tag('div', array('class' => 'me-in-page-menu' . $visibleClass));

        if(count($sections) && $onlyMobile != 'simple') {
            $icon = html_writer::tag('i', '', array('class' => 'icon-me-sprungnav-mobile-ansicht'));
            $output .= html_writer::tag('span', $icon, array('class' => 'me-in-page-menu-mobile-trigger', 'data-status' => 'hidden'));
        }

        $output .= html_writer::start_tag('ul', array('class' => 'me-in-page-menu-anchor-links'));
        foreach($menu_items as $item) {
            $output .= html_writer::start_tag('li');
            $output .= html_writer::tag('div', '<span>' . $item . '</span>', array('class' => 'internal'));
            $output .= html_writer::end_tag('li');
        }
        $output .= html_writer::end_tag('ul');

        $output .= html_writer::start_tag('ul', array('class' => 'me-in-page-menu-features'));
        $output .= html_writer::tag('li', html_writer::link('#top', '<i class="icon-me-back-to-top"></i>', array('class' => 'me-back-top', 'data-scroll' => 'top')));
        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /** Renders course headline
     * 
     * @param  string headline (i. e. the courses fullname)
     * @return string
     */
    protected function render_course_headline($headline) {

        $o = html_writer::tag('h1', $headline);
        return html_writer::div($o, 'course-headline');
    }
}
