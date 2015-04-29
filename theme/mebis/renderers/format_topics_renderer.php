<?php


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/topics/renderer.php');

/**
 * Basic renderer for onetopic format.
 */
class theme_mebis_format_topics_renderer extends format_topics_renderer
{
    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target)
    {
        parent::__construct($page, $target);



        if(!defined('PAGE_MENU_SET'))
            define('PAGE_MENU_SET', true);

        // Since format_topics_renderer::section_edit_controls() only displays the 'Set current section' control when editing mode is on
        // we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any other managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Generate a summary of a section for display on the 'coruse index page'
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param array    $mods (argument not used)
     * @return string HTML to output.
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused)
    {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();

        echo $this->render_page_action_menu($course, $sections);

        echo html_writer::start_tag('div', array('class' => 'course course-format-topics'));

        echo $this->render_course_headline($course->fullname);

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0) {
                // 0-section is displayed a little different then the others
                if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
                    echo $this->custom_section_header($thissection, $course, false, 0);
                    echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    echo $this->courserenderer->course_section_add_cm_control($course, 0, 0);
                    echo $this->section_footer();
                }
                continue;
            }
            if ($section > $course->numsections) {
                // activities inside this section are 'orphaned', this section will be printed as 'stealth' below
                continue;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display.
            $showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available &&
                    !empty($thissection->availableinfo));
            if (!$showsection) {
                // If the hiddensections option is set to 'show hidden sections in collapsed
                // form', then display the hidden section message - UNLESS the section is
                // hidden by the availability system, which is set to hide the reason.
                if (!$course->hiddensections && $thissection->available) {
                    echo $this->section_hidden($section, $course->id);
                }

                continue;
            }

            if (!$PAGE->user_is_editing() && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                // Display section summary only.
                echo $this->section_summary($thissection, $course, null);
            } else {
                echo $this->custom_section_header($thissection, $course, false, 0);
                if ($thissection->uservisible) {
                    echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    echo $this->courserenderer->course_section_add_cm_control($course, $section, 0);
                }
                echo $this->section_footer();
            }
        }

        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $course->numsections or empty($modinfo->sections[$section])) {
                    // this is not stealth section or it is empty
                    continue;
                }
                echo $this->custom_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

            echo html_writer::start_tag('div', array('id' => 'changenumsections', 'class' => 'mdl-right'));

            // Increase number of sections.
            $straddsection = get_string('increasesections', 'moodle');
            $url = new moodle_url('/course/changenumsections.php',
                array('courseid' => $course->id,
                      'increase' => true,
                      'sesskey' => sesskey()));
            $icon = $this->output->pix_icon('t/switch_plus', $straddsection);
            echo html_writer::link($url, $icon.get_accesshide($straddsection), array('class' => 'increase-sections'));

            if ($course->numsections > 0) {
                // Reduce number of sections sections.
                $strremovesection = get_string('reducesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php',
                    array('courseid' => $course->id,
                          'increase' => false,
                          'sesskey' => sesskey()));
                $icon = $this->output->pix_icon('t/switch_minus', $strremovesection);
                echo html_writer::link($url, $icon.get_accesshide($strremovesection), array('class' => 'reduce-sections'));
            }

            echo html_writer::end_tag('div');
        } else {
            echo $this->end_section_list();
        }

        echo html_writer::end_tag('div');
    }

    /**
     * Generate the edit controls of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of links with edit controls
     */
    protected function section_edit_controls($course, $section, $onsectionpage = false)
    {
        global $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $controls = array();

        return array_merge($controls, parent::section_edit_controls($course, $section, $onsectionpage));
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function custom_section_header($section, $course, $onsectionpage, $sectionreturn=null)
    {
        global $PAGE;

        $o = '';
        $currenttext = '';
        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        $o.= html_writer::start_tag('li', array('id' => 'section-'.$section->section,
            'class' => 'section main clearfix'.$sectionstyle, 'role'=>'region',
            'aria-label'=> get_section_name($course, $section)));

        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o.= html_writer::start_tag('div', array('class' => 'content'));

        // When not on a section page, we display the section titles except the general section if null
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' accesshide';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
        }
        $o.= $this->output->heading($this->section_title($section, $course), 2, 'sectionname' . $classes);

        $o.= html_writer::start_tag('div', array('class' => 'summary'));
        $o.= $this->format_summary_text($section);

        $context = context_course::instance($course->id);
        if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $context)) {
            $url = new moodle_url('/course/editsection.php', array('id'=>$section->id, 'sr'=>$sectionreturn));
            $o.= html_writer::link($url,
                html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/settings'),
                    'class' => 'iconsmall edit', 'alt' => get_string('edit'))),
                array('title' => get_string('editsummary')));
        }
        $o.= html_writer::end_tag('div');

        $o .= $this->section_availability_message($section,
                has_capability('moodle/course:viewhiddensections', $context));

        return $o;
    }

    protected function render_page_action_menu($course, $sections, $onlyMobile=false)
    {
        //Add side jump-navigation
        $menu_items = array();

        if(count($sections)) {
            for($i = 1;$i <= $course->numsections;$i++){
                if($sections[$i]->uservisible && $sections[$i]->visible && $sections[$i]->available ){
                    $menu_items[] = html_writer::link('#section-'.$i, '<span>'.$this->section_title($sections[$i], $course).'</span>',
                        array());
                }
            }
        }

        $visibleClass = ($onlyMobile) ? ' visible-xs' : '';
        $output = html_writer::start_tag('div', array('class' => 'me-in-page-menu' . $visibleClass));

        if(count($sections)) {
            $icon = html_writer::tag('i', '', array('class' => 'icon-me-sprungnav-mobile-ansicht'));
            $output .= html_writer::tag('span', $icon, array('class' => 'me-in-page-menu-mobile-trigger', 'data-status' => 'hidden'));
        }

        $output .= html_writer::start_tag('ul', array('class' => 'me-in-page-menu-anchor-links'));
        foreach($menu_items as $item) {
            $output .= html_writer::tag('li', '<span>' . $item . '</span>', array('class' => 'internal'));
        }
        $output .= html_writer::end_tag('ul');

        $output .= html_writer::start_tag('ul', array('class' => 'me-in-page-menu-features'));
        $output .= html_writer::tag('li', html_writer::link('#top', '<i class="icon-me-back-to-top"></i>', array('id' => 'me-back-top')));
        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Renders course headline
     * @param  string
     * @return string
     */
    protected function render_course_headline($headline)
    {
        $course_headline = html_writer::start_tag('div', array('class' => 'course-headline'));
        $course_headline .= html_writer::tag('h1', $headline);
        $course_headline .= html_writer::end_tag('div');
        return $course_headline;
    }
}
