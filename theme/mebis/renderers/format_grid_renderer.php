<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/grid/renderer.php');
require_once($CFG->dirroot . '/course/format/grid/lib.php');

/**
 * Basic renderer for onetopic format.
 */
class theme_mebis_format_grid_renderer extends format_grid_renderer
{
    private $topic0_at_top; // Boolean to state if section zero is at the top (true) or in the grid (false).
    private $courseformat; // Our course format object as defined in lib.php.
    private $settings; // Settings array.
    private $shadeboxshownarray = array(); // Value of 1 = not shown, value of 2 = shown - to reduce ambiguity in JS.
    /**
     * Constructor method, calls the parent constructor - MDL-21097
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */

    public function __construct(moodle_page $page, $target)
    {
        parent::__construct($page, $target);
        $this->courseformat = course_get_format($page->course);
        $this->settings = $this->courseformat->get_settings();

        if(!defined('PAGE_MENU_SET'))
            define('PAGE_MENU_SET', true);

        /* Since format_grid_renderer::section_edit_controls() only displays the 'Set current section' control when editing
          mode is on we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any
          other managing capability. */
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param array $mods
     * @param array $modnames
     * @param array $modnamesused
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused)
    {
        global $PAGE;

        echo $this->render_page_action_menu($course, $sections, false);

        //End side jump-navigation

        echo html_writer::start_tag('div', array('class' => 'course course-format-grid'));

        $summarystatus = $this->courseformat->get_summary_visibility($course->id);
        $context = context_course::instance($course->id);
        $editing = $PAGE->user_is_editing();
        $hascapvishidsect = has_capability('moodle/course:viewhiddensections', $context);

        if ($editing) {
            $streditsummary = get_string('editsummary');
            $urlpicedit = $this->output->pix_url('t/edit');
        } else {
            $urlpicedit = false;
            $streditsummary = '';
        }

        echo $this->render_course_headline($course->fullname);

        echo html_writer::start_tag('div', array('id' => 'gridmiddle-column'));
        echo $this->output->skip_link_target();

        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();

        // Start at 1 to skip the summary block or include the summary block if it's in the grid display.
        $this->topic0_at_top = $summarystatus->showsummary == 1;
        if ($this->topic0_at_top) {
            $this->topic0_at_top = $this->make_block_topic0($course, $sections, $modinfo, $editing, $urlpicedit,
                $streditsummary, false);
            // For the purpose of the grid shade box shown array topic 0 is not shown.
            $this->shadeboxshownarray[0] = 1;
        }
        echo html_writer::start_tag('div',
            array('id' => 'gridiconcontainer', 'role' => 'navigation',
            'aria-label' => get_string('gridimagecontainer', 'format_grid'), 'class'=>'container'));
        echo html_writer::start_tag('div', array('class' => 'gridicons row'));
        // Print all of the imaege containers.

        echo html_writer::start_tag('div', array('class' => 'col-md-12'));
        $this->make_block_icon_topics($context->id, $modinfo, $course, $editing, $hascapvishidsect, $urlpicedit);
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');

        echo html_writer::start_tag('div', array('id' => 'gridshadebox', 'style' => 'display:none;'));

        echo html_writer::tag('div', '', array('id' => 'gridshadebox_overlay', 'style' => 'display:none;'));
        echo html_writer::start_tag('div',
            array('id' => 'gridshadebox_content', 'class' => 'hide_content container',
            'role' => 'region',
            'aria-label' => get_string('shadeboxcontent', 'format_grid')));

        $close = html_writer::tag('span', '<i class="fa fa-close"></i>' . get_string('coursedialog-close', 'theme_mebis'),
            array('id' => 'gridshadebox_close', 'style' => 'display:none;',
            'role' => 'link',
            'aria-label' => get_string('closeshadebox', 'format_grid')));

        echo html_writer::tag('div', $close, array('class' => 'gridshadebox-close-button'));

        echo $this->start_section_list();
        // If currently moving a file then show the current clipboard.
        $this->make_block_show_clipboard_if_file_moving($course);

        // Print Section 0 with general activities.
        if (!$this->topic0_at_top) {
            //$this->make_block_topic0($course, $sections, $modinfo, $editing, $urlpicedit, $streditsummary, false);
        }

        // Now all the normal modules by topic.
        // Everything below uses "section" terminology - each "section" is a topic/module.
        $this->make_block_topics($course, $sections, $modinfo, $editing, $hascapvishidsect, $streditsummary,
            $urlpicedit, false);

        echo html_writer::start_div('row');
        echo html_writer::tag('div', '<i class="icon-me-pfeil-zurueck"></i>',
            array('id' => 'gridshadebox_left', 'class' => 'gridshadebox_arrow col-md-5 col-xs-2',
            'role' => 'link',
            'aria-label' => get_string('previoussection', 'format_grid')));
        echo html_writer::tag('div', '<i class="icon-me-pfeil-weiter"></i>',
            array('id' => 'gridshadebox_right', 'class' => 'gridshadebox_arrow col-md-5 col-md-offset-2 col-xs-2 col-xs-offset-8',
            'role' => 'link',
            'aria-label' => get_string('nextsection', 'format_grid')));
        echo html_writer::end_div();

        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::tag('div', '&nbsp;', array('class' => 'clearer'));
        echo html_writer::end_tag('div');

        $sectionredirect = null;
        if ($course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            // Get the redirect URL prefix for keyboard control with the 'Show one section per page' layout.
            $sectionredirect = $this->courseformat->get_view_url(null)->out(true);
        }

        // Initialise the shade box functionality:...
        $PAGE->requires->js_init_call('M.format_grid.init',
            array(
            $PAGE->user_is_editing(),
            $sectionredirect,
            $course->numsections,
            json_encode($this->shadeboxshownarray)));
        // Initialise the key control functionality...
        $PAGE->requires->yui_module('moodle-format_grid-gridkeys', 'M.format_grid.gridkeys.init', null, null, true);

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
        if (has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $strmarkedthissection = get_string('markedthissection', 'format_grid');
                $controls[] = html_writer::link($url,
                        html_writer::empty_tag('img',
                            array('src' => $this->output->pix_url('i/marked'),
                            'class' => 'icon ', 'alt' => $strmarkedthissection)),
                        array('title' => $strmarkedthissection, 'class' => 'editing_highlight'));
            } else {
                $strmarkthissection = get_string('markthissection', 'format_grid');
                $url->param('marker', $section->section);
                $controls[] = html_writer::link($url,
                        html_writer::empty_tag('img',
                            array('src' => $this->output->pix_url('i/marker'),
                            'class' => 'icon', 'alt' => $strmarkthissection)),
                        array('title' => $strmarkthissection, 'class' => 'editing_highlight'));
            }
        }

        return array_merge($controls, parent::section_edit_controls($course, $section, $onsectionpage));
    }

    // Grid format specific code.
    /**
     * Makes section zero.
     */
    protected function make_block_topic0($course, $sections, $modinfo, $editing, $urlpicedit, $streditsummary,
        $onsectionpage)
    {
        //Returning nothing adds an initial help block so we add an empty, hidden block to counter it
        //@todo: this is probably not neccessary
        return '<div style="display:none;"></div>';
    }

    /**
     * Makes the grid image containers.
     */
    protected function make_block_icon_topics($contextid, $modinfo, $course, $editing, $hascapvishidsect, $urlpicedit)
    {
        global $USER, $CFG;

        if ($this->settings['newactivity'] == 2) {
            $currentlanguage = current_language();
            if (!file_exists("$CFG->dirroot/course/format/grid/pix/new_activity_" . $currentlanguage . ".png")) {
                $currentlanguage = 'en';
            }
            $url_pic_new_activity = $this->output->pix_url('new_activity_' . $currentlanguage, 'format_grid');

            // Get all the section information about which items should be marked with the NEW picture.
            $sectionupdated = $this->new_activity($course);
        }

        if ($editing) {
            $streditimage = get_string('editimage', 'format_grid');
            $streditimagealt = get_string('editimage_alt', 'format_grid');
        }

        // Get the section images for the course.
        $sectionimages = $this->courseformat->get_images($course->id);

        // CONTRIB-4099:...
        $gridimagepath = $this->courseformat->get_image_path();

        echo html_writer::start_tag('ul', array('class' => 'block-grid-xs-1 block-grid-xc-2 block-grid-sm-2 block-grid-md-3 me-block-grid'));

        // Start at 1 to skip the summary block or include the summary block if it's in the grid display.
        for ($section = $this->topic0_at_top ? 1 : 0; $section <= $course->numsections; $section++) {

            echo html_writer::start_tag('li');

            $thissection = $modinfo->get_section_info($section);

            // Check if section is visible to user.
            $showsection = $hascapvishidsect || ($thissection->visible && ($thissection->available ||
                $thissection->showavailability || !$course->hiddensections));

            if ($showsection) {
                // We now know the value for the grid shade box shown array.
                $this->shadeboxshownarray[$section] = 2;

                $sectionname = $this->courseformat->get_section_name($thissection);

                /* Roles info on based on: http://www.w3.org/TR/wai-aria/roles.
                  Looked into the 'grid' role but that requires 'row' before 'gridcell' and there are none as the grid
                  is responsive, so as the container is a 'navigation' then need to look into converting the containing
                  'div' to a 'nav' tag (www.w3.org/TR/2010/WD-html5-20100624/sections.html#the-nav-element) when I'm
                  that all browsers support it against the browser requirements of Moodle. */
                $liattributes = array(
                    'role' => 'region',
                    'aria-label' => $sectionname,
                    'class' => 'me-block-inner'
                );

                if ($this->courseformat->is_section_current($section)) {
                    $liattributes['class'] .= ' currenticon';
                }

                echo html_writer::start_tag('div', $liattributes);
                echo html_writer::start_tag('div');

                // Ensure the record exists.
                if (($sectionimages === false) || (!array_key_exists($thissection->id, $sectionimages))) {
                    // get_image has 'repair' functionality for when there are issues with the data.
                    $sectionimage = $this->courseformat->get_image($course->id, $thissection->id);
                } else {
                    $sectionimage = $sectionimages[$thissection->id];
                }

                // If the image is set then check that displayedimageindex is greater than 0 otherwise create the displayed image.
                // This is a catch-all for existing courses.
                if (isset($sectionimage->image) && ($sectionimage->displayedimageindex < 1)) {
                    // Set up the displayed image:...
                    $sectionimage->newimage = $sectionimage->image;
                    $sectionimage = $this->courseformat->setup_displayed_image($sectionimage, $contextid,
                        $this->settings);
                    if (format_grid::is_developer_debug()) {
                        error_log('make_block_icon_topics: Updated displayed image for section ' . $thissection->id . ' to ' .
                            $sectionimage->newimage . ' and index ' . $sectionimage->displayedimageindex);
                    }
                }

                if ($course->coursedisplay != COURSE_DISPLAY_MULTIPAGE) {
                    echo html_writer::start_tag('a',
                        array(
                        'href' => '#section-' . $thissection->section,
                        'id' => 'gridsection-' . $thissection->section,
                        'class' => 'me-block-link gridicon_link ',
                        'role' => 'link',
                        'aria-label' => $sectionname));


                    if (($this->settings['newactivity'] == 2) && (isset($sectionupdated[$thissection->id]))) {
                        // The section has been updated since the user last visited this course, add NEW label.
                        echo html_writer::empty_tag('img',
                            array(
                            'class' => 'new_activity',
                            'src' => $url_pic_new_activity,
                            'alt' => ''));
                    }

                    $showimg = false;
                    $imgurl = null;
                    $localImageUrl = '';
                    if (is_object($sectionimage) && ($sectionimage->displayedimageindex > 0)) {
                        $imgurl = moodle_url::make_pluginfile_url(
                                $contextid, 'course', 'section', $thissection->id, $gridimagepath,
                                $sectionimage->displayedimageindex . '_' . $sectionimage->image);
                        $showimg = true;
                    } else if ($section == 0) {
                        $imgurl = $this->output->pix_url('info', 'format_grid');
                        $showimg = true;
                    }

                    /* ToDo: If image is portrait-view */
                    if (is_object($imgurl)) {
                        $localImageUrl = $imgurl->out();
                    }

                    if (empty($localImageUrl)) {
                        $showimg = false;
                    }

                    if($showimg && @file_get_contents($localImageUrl)) {
                        list($imgWidth, $imgHeight) = getimagesize($localImageUrl);
                        $specialClass = ($imgWidth < $imgHeight) ? ' portrait' : '';

                        if($imgWidth < 300) {
                            $specialClass = ' portrait';
                        }

                        if($imgHeight > 160) {
                            $specialClass = ' portrait';
                        }

                    } else {
                        $specialClass = '';
                    }

                    echo html_writer::start_tag('div', array('class' => 'format-grid-image' . $specialClass));

                    if ($showimg) {
                        echo html_writer::start_tag('span', array('class' => 'helper'));
                        echo html_writer::empty_tag('img',
                            array(
                            'src' => $imgurl,
                            'alt' => $sectionname,
                            'role' => 'img',
                            'aria-label' => $sectionname));
                        echo html_writer::end_tag('span');
                        if($specialClass) {
                            echo html_writer::tag('div', '', array('class' => 'block-blur-bg blur', 'data-bg' => $localImageUrl));
                        }

                    } else {
                        echo html_writer::tag('div', '', array('class' => 'img-replace'));
                    }

                    echo html_writer::end_tag('div');

                    echo html_writer::start_tag('div', array('class' => 'format-grid-content'));
                    echo html_writer::tag('h3', $sectionname, array('class' => 'internal'));
                    echo html_writer::end_tag('div');

                    echo html_writer::end_tag('a');

                    if ($editing) {
                        echo html_writer::link(
                            $this->courseformat->grid_moodle_url('editimage.php',
                                array(
                                'sectionid' => $thissection->id,
                                'contextid' => $contextid,
                                'userid' => $USER->id,
                                'role' => 'link',
                                'aria-label' => $streditimagealt)),
                            html_writer::empty_tag('img',
                                array(
                                'src' => $urlpicedit,
                                'alt' => $streditimagealt,
                                'role' => 'img',
                                'aria-label' => $streditimagealt)) . '<span>' . $streditimage . '</span>',
                            array('title' => $streditimagealt, 'class' => 'edit-image'));

                        if ($section == 0) {
                            $strdisplaysummary = get_string('display_summary', 'format_grid');
                            $strdisplaysummaryalt = get_string('display_summary_alt', 'format_grid');

                            echo html_writer::empty_tag('br') . html_writer::link(
                                $this->courseformat->grid_moodle_url('mod_summary.php',
                                    array(
                                    'sesskey' => sesskey(),
                                    'course' => $course->id,
                                    'showsummary' => 1,
                                    'role' => 'link',
                                    'aria-label' => $strdisplaysummaryalt)),
                                html_writer::empty_tag('img',
                                    array(
                                    'src' => $this->output->pix_url('out_of_grid', 'format_grid'),
                                    'alt' => $strdisplaysummaryalt,
                                    'role' => 'img',
                                    'aria-label' => $strdisplaysummaryalt)) . '&nbsp;' . $strdisplaysummary,
                                array('title' => $strdisplaysummaryalt));
                        }
                    }
                    echo html_writer::end_tag('div');
                    echo html_writer::end_tag('div');
                } else {
                    $title = html_writer::tag('p', $sectionname, array('class' => 'icon_content'));

                    if (($this->settings['newactivity'] == 2) && (isset($sectionupdated[$thissection->id]))) {
                        $title .= html_writer::empty_tag('img',
                                array(
                                'class' => 'new_activity',
                                'src' => $url_pic_new_activity,
                                'alt' => ''));
                    }

                    $title .= html_writer::start_tag('div', array('class' => 'image_holder'));

                    $showimg = false;
                    if (is_object($sectionimage) && ($sectionimage->displayedimageindex > 0)) {
                        $imgurl = moodle_url::make_pluginfile_url(
                                $contextid, 'course', 'section', $thissection->id, $gridimagepath,
                                $sectionimage->displayedimageindex . '_' . $sectionimage->image);
                        $showimg = true;
                    } else if ($section == 0) {
                        $imgurl = $this->output->pix_url('info', 'format_grid');
                        $showimg = true;
                    }
                    if ($showimg) {
                        $title .= html_writer::empty_tag('img',
                                array(
                                'src' => $imgurl,
                                'alt' => $sectionname,
                                'role' => 'img',
                                'aria-label' => $sectionname));
                    }

                    $title .= html_writer::end_tag('div');

                    $url = course_get_url($course, $thissection->section);
                    if ($url) {
                        $title = html_writer::link($url, $title,
                                array(
                                'id' => 'gridsection-' . $thissection->section,
                                'role' => 'link',
                                'aria-label' => $sectionname));
                    }
                    echo $title;

                    if ($editing) {
                        echo html_writer::link(
                            $this->courseformat->grid_moodle_url('editimage.php',
                                array(
                                'sectionid' => $thissection->id,
                                'contextid' => $contextid,
                                'userid' => $USER->id,
                                'role' => 'link',
                                'aria-label' => $streditimagealt)),
                            html_writer::empty_tag('img',
                                array(
                                'src' => $urlpicedit,
                                'alt' => $streditimagealt,
                                'role' => 'img',
                                'aria-label' => $streditimagealt)) . '&nbsp;' . $streditimage,
                            array('title' => $streditimagealt));

                        if ($section == 0) {
                            $strdisplaysummary = get_string('display_summary', 'format_grid');
                            $strdisplaysummaryalt = get_string('display_summary_alt', 'format_grid');

                            echo html_writer::empty_tag('br') . html_writer::link(
                                $this->courseformat->grid_moodle_url('mod_summary.php',
                                    array(
                                    'sesskey' => sesskey(),
                                    'course' => $course->id,
                                    'showsummary' => 1,
                                    'role' => 'link',
                                    'aria-label' => $strdisplaysummaryalt)),
                                html_writer::empty_tag('img',
                                    array(
                                    'src' => $this->output->pix_url('out_of_grid', 'format_grid'),
                                    'alt' => $strdisplaysummaryalt,
                                    'role' => 'img',
                                    'aria-label' => $strdisplaysummaryalt)) . '&nbsp;' . $strdisplaysummary,
                                array('title' => $strdisplaysummaryalt));
                        }
                    }
                    echo html_writer::end_tag('div');
                    echo html_writer::end_tag('div');
                }
            } else {
                // We now know the value for the grid shade box shown array.
                $this->shadeboxshownarray[$section] = 1;
            }
             echo html_writer::end_tag('li');
        }

        echo html_writer::end_tag('ul');
    }

    /**
     * If currently moving a file then show the current clipboard.
     */
    protected function make_block_show_clipboard_if_file_moving($course)
    {
        global $USER;

        if (is_object($course) && ismoving($course->id)) {
            $strcancel = get_string('cancel');

            $stractivityclipboard = clean_param(format_string(
                    get_string('activityclipboard', '', $USER->activitycopyname)), PARAM_NOTAGS);
            $stractivityclipboard .= '&nbsp;&nbsp;('
                . html_writer::link(new moodle_url('/mod.php',
                    array(
                    'cancelcopy' => 'true',
                    'sesskey' => sesskey())), $strcancel);

            echo html_writer::tag('li', $stractivityclipboard, array('class' => 'clipboard'));
        }
    }

    /**
     * Makes the list of sections to show.
     */
    protected function make_block_topics($course, $sections, $modinfo, $editing, $hascapvishidsect, $streditsummary,
        $urlpicedit, $onsectionpage)
    {
        $context = context_course::instance($course->id);
        unset($sections[0]);
        for ($section = 1; $section <= $course->numsections; $section++) {
            $thissection = $modinfo->get_section_info($section);

            if (!$hascapvishidsect && !$thissection->visible && $course->hiddensections) {
                unset($sections[$section]);
                continue;
            }

            $sectionstyle = 'section main';
            if (!$thissection->visible) {
                $sectionstyle .= ' hidden';
            }
            if ($this->courseformat->is_section_current($section)) {
                $sectionstyle .= ' current';
            }
            $sectionstyle .= ' grid_section hide_section';

            $sectionname = get_section_name($course, $thissection);
            echo html_writer::start_tag('li',
                array(
                'id' => 'section-' . $section,
                'class' => $sectionstyle,
                'role' => 'region',
                'aria-label' => $sectionname)
            );

            if ($editing) {
                // Note, 'left side' is BEFORE content.
                $leftcontent = $this->section_left_content($thissection, $course, $onsectionpage);
                echo html_writer::tag('div', $leftcontent, array('class' => 'left side'));
                // Note, 'right side' is BEFORE content.
                $rightcontent = $this->section_right_content($thissection, $course, $onsectionpage);
                echo html_writer::tag('div', $rightcontent, array('class' => 'right side'));
            }

            echo html_writer::start_tag('div', array('class' => 'content'));
            if ($hascapvishidsect || ($thissection->visible && $thissection->available)) {
                // If visible.
                echo $this->output->heading($sectionname, 3, 'sectionname');

                echo html_writer::start_tag('div', array('class' => 'summary'));

                echo $this->format_summary_text($thissection);

                if ($editing) {
                    echo html_writer::link(
                        new moodle_url('editsection.php', array('id' => $thissection->id)),
                        html_writer::empty_tag('img',
                            array('src' => $urlpicedit, 'alt' => $streditsummary,
                            'class' => 'iconsmall edit')), array('title' => $streditsummary));
                }
                echo html_writer::end_tag('div');

                echo $this->section_availability_message($thissection,
                    has_capability('moodle/course:viewhiddensections', $context));

                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
            } else {
                echo html_writer::tag('h2', $this->get_title($thissection));
                echo html_writer::tag('p', get_string('hidden_topic', 'format_grid'));

                echo $this->section_availability_message($thissection,
                    has_capability('moodle/course:viewhiddensections', $context));
            }

            echo html_writer::end_tag('div');
            echo html_writer::end_tag('li');

            unset($sections[$section]);
        }

        if ($editing) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $course->numsections or empty($modinfo->sections[$section])) {
                    // This is not stealth section or it is empty.
                    continue;
                }
                echo $this->stealth_section_header($section);
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
            echo html_writer::link($url, $icon . get_accesshide($straddsection), array('class' => 'increase-sections'));

            if ($course->numsections > 0) {
                // Reduce number of sections sections.
                $strremovesection = get_string('reducesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php',
                    array('courseid' => $course->id,
                    'increase' => false,
                    'sesskey' => sesskey()));
                $icon = $this->output->pix_icon('t/switch_minus', $strremovesection);
                echo html_writer::link($url, $icon . get_accesshide($strremovesection),
                    array('class' => 'reduce-sections'));
            }

            echo html_writer::end_tag('div');
        } else {
            echo $this->end_section_list();
        }
    }

    /**
     * Checks whether there has been new activity.
     */
    protected function new_activity($course)
    {
        global $CFG, $USER, $DB;

        $sectionsedited = array();
        if (isset($USER->lastcourseaccess[$course->id])) {
            $course->lastaccess = $USER->lastcourseaccess[$course->id];
        } else {
            $course->lastaccess = 0;
        }

        $sql = "SELECT id, section FROM {$CFG->prefix}course_modules " .
            "WHERE course = :courseid AND added > :lastaccess";

        $params = array(
            'courseid' => $course->id,
            'lastaccess' => $course->lastaccess);

        $activity = $DB->get_records_sql($sql, $params);
        foreach ($activity as $record) {
            $sectionsedited[$record->section] = true;
        }

        return $sectionsedited;
    }

    protected function render_page_action_menu($course, $sections, $onlyMobile=false)
    {
        //Add side jump-navigation
        $menu_items = array();

        if(count($sections)) {
            for($i = 1;$i <= $course->numsections;$i++){
                if($sections[$i]->uservisible && $sections[$i]->visible && $sections[$i]->available ){
                    $menu_items[] = html_writer::link('#section-'.$i, '<span>'.$this->section_title($sections[$i], $course).'</span>',
                        array('class' => 'jumpnavigation-point', 'data-scroll' => '#section-'.$i));
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
        $output .= html_writer::tag('li', html_writer::link('#top', '<i class="icon-me-back-to-top"></i>', array('id' => 'me-back-top', 'data-scroll' => 'top')));
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
