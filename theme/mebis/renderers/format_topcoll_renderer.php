<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/topcoll/renderer.php');

/**
 * Basic renderer for onetopic format.
 */
class theme_mebis_format_topcoll_renderer extends format_topcoll_renderer {

    private $tccolumnwidth = 100; // Default width in percent of the column(s).
    private $tccolumnpadding = 0; // Default padding in pixels of the column(s).
    private $mobiletheme = false; // As not using a mobile theme we can react to the number of columns setting.
    private $tablettheme = false; // As not using a tablet theme we can react to the number of columns setting.
    private $courseformat = null; // Our course format object as defined in lib.php;
    private $tcsettings; // Settings for the format - array.
    private $userpreference; // User toggle state preference - string.
    private $defaultuserpreference; // Default user preference when none set - bool - true all open, false all closed.
    private $togglelib;
    private $isoldtogglepreference = false;
    private $userisediting = false;
    private $tctoggleiconsize;

    /**
     * Constructor method 
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        $this->togglelib = new topcoll_togglelib;
        $this->courseformat = course_get_format($page->course); // Needed for collapsed topics settings retrieval.

        /* Since format_topcoll_renderer::section_edit_controls() only displays the 'Set current section' control when editing
          mode is on we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any
          other managing capability. */
        $page->set_other_editing_capability('moodle/course:setcurrentsection');

        global $PAGE;
        $this->userisediting = $PAGE->user_is_editing();
        $this->tctoggleiconsize = clean_param(get_config('format_topcoll', 'defaulttoggleiconsize'), PARAM_TEXT);
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage,
                                      $sectionreturn = null) {
        $o = '';

        $sectionstyle = '';
        $rightcurrent = '';
        $context = context_course::instance($course->id);

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if ($this->courseformat->is_section_current($section)) {
                $section->toggle = true; // Open current section regardless of toggle state.
                $sectionstyle = ' current';
                $rightcurrent = ' left';
            }
        } else {
            $sectionstyle = ' summary';
        }

        $liattributes = array(
            'id' => 'section-' . $section->section,
            'class' => 'section main clearfix' . $sectionstyle,
            'role' => 'region',
            'aria-label' => $this->courseformat->get_topcoll_section_name($course, $section, false)
        );

        $liattributes['style'] = 'width:' . $this->tccolumnwidth . '%;';

        $o .= html_writer::start_tag('li', $liattributes);

        if ((($this->mobiletheme === false) && ($this->tablettheme === false)) || ($this->userisediting)) {
            $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
            $o .= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

            $rightcontent = '';
            if (($section->section != 0) && $this->userisediting && has_capability('moodle/course:update', $context)) {
                $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));

                $rightcontent .= html_writer::link($url, html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/edit'),
                                    'class' => 'icon edit tceditsection', 'alt' => get_string('edit'))), array('title' => get_string('editsummary'), 'class' => 'tceditsection'));
                $rightcontent .= html_writer::empty_tag('br');
            }
            $rightcontent .= $this->section_right_content($section, $course, $onsectionpage);
            $o .= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        }

        $o .= html_writer::start_tag('div', array('class' => 'content'));

        if (($onsectionpage == false) && ($section->section != 0)) {

            if (empty($this->tcsettings)) {
                $this->tcsettings = $this->courseformat->get_settings();
            }

            if ((!($section->toggle === null)) && ($section->toggle == true)) {
                $toggleclass = 'toggle_open';
                $sectionclass = ' sectionopen';
                $invisible = '';
                $closeButtonSymbol = "";
            } else {
                $toggleclass = 'toggle_closed';
                $sectionclass = '';
                $invisible = 'display:none;';
                $closeButtonSymbol = "";
            }

            $toggleclass .= ' the_toggle ' . $this->tctoggleiconsize;
            $toggleurl = new moodle_url('/course/view.php', array('id' => $course->id));

            $o .= html_writer::start_tag('div', array('class' => 'section-header'));
            $o .= html_writer::start_tag('div', array('class' => 'row'));

            $o .= html_writer::start_tag('div', array('class' => 'col-md-9'));
            $title = $this->courseformat->get_topcoll_section_name($course, $section, true);
            if ((($this->mobiletheme === false) && ($this->tablettheme === false)) || ($this->userisediting)) {
                $o .= $this->output->heading($title, 2, 'section-title');
            } else {
                $o .= html_writer::tag('h2', $title);
            }
            $o .= html_writer::end_tag('div');

            $o .= html_writer::start_tag('div', array('class' => 'col-md-3 sectionhead toggle', 'id' => 'toggle-' . $section->section));
            $o .= html_writer::start_tag('a', array('class' => $toggleclass, 'href' => $toggleurl));
            $o .= html_writer::div($closeButtonSymbol, 'closebutton', array('id' => 'close-' . $section->section));
            $o .= html_writer::end_tag('a');
            $o .= html_writer::end_tag('div');

            $o .= html_writer::end_tag('div');
            $o .= html_writer::end_tag('div');
            $o .= html_writer::end_tag('li');
            $o .= html_writer::start_tag('li', array('class' => 'section clearfix body', 'style' => 'width:100%;' . $invisible));
            $o .= html_writer::start_div('content', array('style' => $invisible));
            $o .= html_writer::start_tag('div', array('class' => 'sectionbody toggledsection' . $sectionclass,
                        'id' => 'toggledsection-' . $section->section));

            if ($this->userisediting && has_capability('moodle/course:update', $context)) {
                $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
                $o .= html_writer::link($url, html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/edit'),
                                    'class' => 'iconsmall edit', 'alt' => get_string('edit'))), array('title' => get_string('editsummary')));
            }

            $o .= html_writer::start_tag('div', array('class' => 'summary'));
            $o .= $this->format_summary_text($section);

            $o .= html_writer::end_tag('div');

            $o .= $this->section_availability_message($section, has_capability('moodle/course:viewhiddensections', $context));
        } else {
            // When on a section page, we only display the general section title, if title is not the default one.
            $hasnamesecpg = ($section->section == 0 && (string) $section->name !== '');

            if ($hasnamesecpg) {
                $o .= $this->output->heading($this->section_title($section, $course), 2, 'section-title');
            }
            $o .= html_writer::start_tag('div', array('class' => 'summary'));
            $o .= $this->format_summary_text($section);

            if ($this->userisediting && has_capability('moodle/course:update', $context)) {
                $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
                $o .= html_writer::link($url, html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/edit'),
                                    'class' => 'iconsmall edit', 'alt' => get_string('edit'))), array('title' => get_string('editsummary')));
            }
            $o .= html_writer::end_tag('div');

            $o .= $this->section_availability_message($section, has_capability('moodle/course:viewhiddensections', $context));
        }
        return $o;
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

    /** render the page action menu (i. e. the menu on the right side to jump to sections
     * 
     * @param object $course
     * @param arra $sections
     * @param boolean $onlyMobile
     * @return string HTML fo the section menu.
     */
    protected function render_page_action_menu($course, $sections,
                                               $onlyMobile = false) {
        //Add side jump-navigation
        $menu_items = array();

        for ($i = 1; $i <= $course->numsections; $i++) {
            if ($sections[$i]->uservisible && $sections[$i]->visible && $sections[$i]->available) {
                $menu_items[] = html_writer::link('#section-' . $i, '<span>' . $this->section_title($sections[$i], $course) . '</span>', array('class' => 'jumpnavigation-point', 'data-scroll' => '#section-' . $i));
            }
        }

        $visibleClass = ($onlyMobile) ? ' visible-xs' : '';
        $output = html_writer::start_tag('div', array('class' => 'me-in-page-menu' . $visibleClass));

        $icon = html_writer::tag('i', '', array('class' => 'icon-me-sprungnav-mobile-ansicht'));
        $output .= html_writer::tag('span', $icon, array('class' => 'me-in-page-menu-mobile-trigger', 'data-status' => 'hidden'));

        $output .= html_writer::start_tag('ul', array('class' => 'me-in-page-menu-anchor-links'));
        foreach ($menu_items as $item) {
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
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     */
    public function print_multiple_section_page($course, $sections, $mods,
                                                $modnames, $modnamesused) {
        echo html_writer::start_tag('div', array('class' => 'course course-format-topcoll'));
        echo $this->render_course_headline($course->fullname);

        $modinfo = get_fast_modinfo($course);
        $sectioninfo = $modinfo->get_section_info_all();
        echo $this->render_page_action_menu($course, $sectioninfo);

        parent::print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused);
        echo html_writer::end_tag('div');
    }

}