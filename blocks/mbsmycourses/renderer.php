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
 * Renderer for block_mbsmycourses (based on block course_overview)
 *
 * @package    block_mbsmycourses
 * @copyright  2015 Andreas Wagner <andreas.wagner@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/blocks/mbsmycourses/locallib.php');

class block_mbsmycourses_renderer extends plugin_renderer_base {

    /** render the list of courses displaying in listview
     * 
     * @param record $coursesinfo data of courses (i. e. attribute groupedcourses of this
     *               record contains the courses grouped by schools.
     * @param array $overviews 'news' from the activities within a course, if there are some
     * @return string HTML for the courses list (grouped by school).
     */
    public function mbsmycourses_list($coursesinfo, $overviews) {

        $o = '';
        $userediting = false;
        $ismovingcategory = false;
        $categoryordernumber = 0;

        if ($this->page->user_is_editing() && (count($coursesinfo->groupedcourses) > 1)) {

            $userediting = true;

            // Check if course is moving.
            $ismovingcategory = optional_param('movecategory', false, PARAM_BOOL);
            $movingcategoryid = optional_param('categoryid', 0, PARAM_INT);
        }

        // Render first movehere icon.
        if ($ismovingcategory) {

            // Remove movecourse param from url.
            $this->page->ensure_param_not_in_url('movecategory');

            // Show moving category notice, so user knows what is being moved.
            $o .= $this->output->box_start('notice');
            $a = new stdClass();
            $a->fullname = $coursesinfo->groupedcourses[$movingcategoryid]->category->name;
            $a->cancellink = html_writer::link($this->page->url, get_string('cancel'));
            $o .= get_string('movecategory', 'block_mbsmycourses', $a);
            $o .= $this->output->box_end();

            $moveurl = new moodle_url('/blocks/mbsmycourses/movecategory.php',
                            array('sesskey' => sesskey(), 'moveto' => 0, 'categoryid' => $movingcategoryid));
            // Create move icon, so it can be used.
            $movetofirsticon = html_writer::empty_tag('img', array('src' => $this->output->pix_url('movehere'),
                        'alt' => get_string('movetofirst', 'block_mbsmycourses', $coursesinfo->groupedcourses[$movingcategoryid]->category->name),
                        'title' => get_string('movehere')));
            $moveurl = html_writer::link($moveurl, $movetofirsticon);
            $o .= html_writer::tag('div', $moveurl, array('class' => 'movehere'));
        }

        foreach ($coursesinfo->groupedcourses as $catid => $categoryinfo) {

            // If moving category, then don't show category which needs to be moved.
            if ($ismovingcategory && ($catid == $movingcategoryid)) {
                continue;
            }

            $header = '';
            // If user is editing, then add move icons.
            if ($userediting && !$ismovingcategory) {
                $moveicon = html_writer::empty_tag('img', array('src' => $this->pix_url('t/move')->out(false),
                            'alt' => get_string('movecategory', 'block_mbsmycourses', $categoryinfo->category->name),
                            'title' => get_string('move')));
                $moveurl = new moodle_url($this->page->url, array('sesskey' => sesskey(), 'movecategory' => 1, 'categoryid' => $catid));
                $moveurl = html_writer::link($moveurl, $moveicon);
                $header .= html_writer::tag('div', $moveurl, array('class' => 'move'));
            }

            $caturl = new moodle_url('/course/index.php', array('categoryid' => $catid));
            $header .= html_writer::link($caturl, $categoryinfo->category->name);

            $c = '';
            $newcount = 0;
            foreach ($categoryinfo->courses as $course) {

                $url = new moodle_url('/course/view.php?', array('id' => $course->id));
                $courselink = html_writer::link($url, $course->fullname);
                $name = html_writer::tag('h3', $courselink, array('class' => 'coursename'));

                // If user is moving categories, then down't show overview.
                $moreinfo = '';
                $content = '';
                if (isset($overviews[$course->id]) && !$ismovingcategory) {

                    $new = html_writer::tag('a', get_string('new', 'block_mbsmycourses'), array('id' => 'mbsmycourses-new-' . $course->id));
                    $moreinfo = html_writer::tag('div', $new, array('class' => 'mbsmycourses-new'));

                    $content = $this->activity_display($course, $overviews[$course->id]);
                    $newcount++;
                }

                $name .= html_writer::tag('div', $moreinfo, array('class' => 'moreinfo'));
                $name .= html_writer::tag('div', '', array('class' => 'clearfix'));
                $info = html_writer::tag('div', $name, array('class' => 'info'));

                $c .= html_writer::tag('div', $info . $content, array('class' => 'col-lg-12 coursebox'));
            }

            if ($newcount > 0) {
                $header .= html_writer::tag('span', get_string('new', 'block_mbsmycourses') . " (" . $newcount . ")", array('class' => 'mbsmycourses-newinfo'));
            }
            $o .= $this->collapsible_region($c, 'col-lg-12 category-box', 'category-box_' . $catid, $header, 'mbscourse-catcoll_' . $catid);

            $categoryordernumber++;
            if ($ismovingcategory) {

                $moveurl = new moodle_url('/blocks/mbsmycourses/movecategory.php',
                                array('sesskey' => sesskey(), 'moveto' => $categoryordernumber, 'categoryid' => $movingcategoryid));
                $a = new stdClass();
                $a->movingcategoryname = $coursesinfo->groupedcourses[$movingcategoryid]->category->name;
                $a->currentcategoryname = $categoryinfo->category->name;
                $movehereicon = html_writer::empty_tag('img', array('src' => $this->output->pix_url('movehere'),
                            'alt' => get_string('movecatafterhere', 'block_mbsmycourses', $a),
                            'title' => get_string('movehere')));
                $moveurl = html_writer::link($moveurl, $movehereicon);
                $o .= html_writer::tag('div', $moveurl, array('class' => 'movehere'));
            }
        }

        $o = html_writer::tag('div', $o, array('class' => 'mbsmycourses-list'));
        return $o;
    }

    /**
     * render contents of mbsmycourses block
     *
     * @param array $courses list of courses in sorted order
     * @param array $overviews list of course overviews
     * @return string html to be displayed in mbsmycourses block
     */
    public function mbsmycourses_grid($coursesinfo, $overviews) {

        $courses = $coursesinfo->sitecourses;
        $schoolcategories = $coursesinfo->schoolcategories;

        $html = '';
        $config = get_config('block_mbsmycourses');
        $ismovingcourse = false;
        $courseordernumber = 0;
        $maxcourses = count($courses);
        $userediting = false;
        // Intialise string/icon etc if user is editing and courses > 1
        if ($this->page->user_is_editing() && (count($courses) > 1)) {
            $userediting = true;
            $this->page->requires->js_init_call('M.block_mbsmycourses.add_handles');

            // Check if course is moving
            $ismovingcourse = optional_param('movecourse', false, PARAM_BOOL);
            $movingcourseid = optional_param('courseid', 0, PARAM_INT);
        }

        // Render first movehere icon.
        if ($ismovingcourse) {
            // Remove movecourse param from url.
            $this->page->ensure_param_not_in_url('movecourse');

            // Show moving course notice, so user knows what is being moved.
            $html .= $this->output->box_start('notice');
            $a = new stdClass();
            $a->fullname = $courses[$movingcourseid]->fullname;
            $a->cancellink = html_writer::link($this->page->url, get_string('cancel'));
            $html .= get_string('movingcourse', 'block_mbsmycourses', $a);
            $html .= $this->output->box_end();

            $moveurl = new moodle_url('/blocks/mbsmycourses/move.php',
                            array('sesskey' => sesskey(), 'moveto' => 0, 'courseid' => $movingcourseid));
            // Create move icon, so it can be used.
            $movetofirsticon = html_writer::empty_tag('img', array('src' => $this->output->pix_url('movehere'),
                        'alt' => get_string('movetofirst', 'block_mbsmycourses', $courses[$movingcourseid]->fullname),
                        'title' => get_string('movehere')));
            $moveurl = html_writer::link($moveurl, $movetofirsticon);
            $html .= html_writer::tag('div', $moveurl, array('class' => 'movehere'));
        }

        foreach ($courses as $key => $course) {
            // If moving course, then don't show course which needs to be moved.
            if ($ismovingcourse && ($course->id == $movingcourseid)) {
                continue;
            }
            $html .= $this->output->box_start('coursebox', "course-{$course->id}");

            // If user is moving courses, then down't show overview.
            if (isset($overviews[$course->id]) && !$ismovingcourse) {

                $new = html_writer::tag('a', get_string('new', 'block_mbsmycourses'), array('id' => 'mbsmycourses-new-' . $course->id));
                $html .= html_writer::tag('div', $new, array('class' => 'mbsmycourses-new'));
                $html .= $this->activity_display($course, $overviews[$course->id]);
            }

            $html .= html_writer::start_tag('div', array('class' => 'course_title'));
            // If user is editing, then add move icons.
            if ($userediting && !$ismovingcourse) {
                $moveicon = html_writer::empty_tag('img', array('src' => $this->pix_url('t/move')->out(false),
                            'alt' => get_string('movecourse', 'block_mbsmycourses', $course->fullname),
                            'title' => get_string('move')));
                $moveurl = new moodle_url($this->page->url, array('sesskey' => sesskey(), 'movecourse' => 1, 'courseid' => $course->id));
                $moveurl = html_writer::link($moveurl, $moveicon);
                $html .= html_writer::tag('div', $moveurl, array('class' => 'move'));
            }

            // No need to pass title through s() here as it will be done automatically by html_writer.
            $attributes = array('title' => $course->fullname);
            if ($course->id > 0) {
                if (empty($course->visible)) {
                    $attributes['class'] = 'dimmed';
                }
                $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
                $coursefullname = format_string(get_course_display_name_for_list($course), true, $course->id);
                $link = html_writer::link($courseurl, $coursefullname, $attributes);
                $html .= $this->output->heading($link, 2, 'title');
            } else {
                $url = new moodle_url('/auth/mnet/jump.php', array('hostid' => $course->hostid, 'wantsurl' => '/course/view.php?id=' . $course->remoteid));
                $link = html_writer::link($url, format_string($course->shortname, true), $attributes);
                $html .= $this->output->heading($link . ' (' . format_string($course->hostname) . ')', 2, 'title');
            }

            $html .= $this->output->box('', 'flush');
            $html .= html_writer::end_tag('div');
            if (isset($schoolcategories[$course->category]->name)) {
                $html .= html_writer::tag('div', $schoolcategories[$course->category]->name, array('class' => 'schoolcategory'));
            }

            if (!empty($config->showchildren) && ($course->id > 0)) {
                // List children here.
                if ($children = mbsmycourses::get_child_shortnames($course->id)) {
                    $html .= html_writer::tag('span', $children, array('class' => 'coursechildren'));
                }
            }

            $html .= $this->output->box('', 'flush');
            $html .= $this->output->box_end();
            $courseordernumber++;
            if ($ismovingcourse) {
                $moveurl = new moodle_url('/blocks/mbsmycourses/move.php',
                                array('sesskey' => sesskey(), 'moveto' => $courseordernumber, 'courseid' => $movingcourseid));
                $a = new stdClass();
                $a->movingcoursename = $courses[$movingcourseid]->fullname;
                $a->currentcoursename = $course->fullname;
                $movehereicon = html_writer::empty_tag('img', array('src' => $this->output->pix_url('movehere'),
                            'alt' => get_string('moveafterhere', 'block_mbsmycourses', $a),
                            'title' => get_string('movehere')));
                $moveurl = html_writer::link($moveurl, $movehereicon);
                $html .= html_writer::tag('div', $moveurl, array('class' => 'movehere'));
            }
        }
        $html .= html_writer::tag('div', '', array('class' => 'clearfix'));
        // Wrap course list in a div and return.
        return html_writer::tag('div', $html, array('class' => 'course_list mbsmycourses-grid'));
    }

    /**
     * Coustuct activities overview for a course
     *
     * @param int $cid course id
     * @param array $overview overview of activities in course
     * @return string html of activities overview
     */
    protected function activity_display($course, $overview) {

        $output = html_writer::start_tag('div', array('id' => "mbsmycourses-overlay-" . $course->id, 'class' => 'yui3-overlay-loading'));

        $closebutton = html_writer::tag('a', 'X', array('class' => 'mbscourses-hide-overlay', 'href' => '#'));
        $output .= html_writer::tag('div', $closebutton . $course->fullname, array('class' => 'yui3-widget-hd'));
        
        foreach (array_keys($overview) as $module) {

            $url = new moodle_url("/mod/$module/index.php", array('id' => $course->id));
            $modulename = get_string('modulename', $module);
            $icontext = html_writer::link($url, $this->output->pix_icon('icon', $modulename, 'mod_' . $module, array('class' => 'iconlarge')));
            if (get_string_manager()->string_exists("activityoverview", $module)) {
                $icontext .= get_string("activityoverview", $module);
            } else {
                $icontext .= get_string("activityoverview", 'block_mbsmycourses', $modulename);
            }
            
            $output .= html_writer::tag('div', $icontext . $overview[$module], array('class' => 'yui3-widget-bd'));
            $output .= html_writer::tag('div', '', array('class' => 'yui3-widget-ft', 'style' => 'display:none'));
        }
        $output .= html_writer::end_tag('div');

        $output = html_writer::tag('div', $output, array('id' => 'mbsmycourses-overlay-position-' . $course->id));

        $this->page->requires->js_init_call('M.block_mbsmycourses.add_overlay', array($course->id));
        return $output;
    }

    /**
     * Constructs header in editing mode
     *
     * @param int $max maximum number of courses
     * @return string html of header bar.
     */
    public function editing_bar_head($max = 0) {
        $output = $this->output->box_start('notice');

        $options = array('0' => get_string('alwaysshowall', 'block_mbsmycourses'));
        for ($i = 1; $i <= $max; $i++) {
            $options[$i] = $i;
        }
        $url = new moodle_url('/my/index.php');
        $select = new single_select($url, 'mynumber', $options, mbsmycourses::get_max_user_courses(), array());
        $select->set_label(get_string('numtodisplay', 'block_mbsmycourses'));
        $output .= $this->output->render($select);

        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Show hidden courses count
     *
     * @param int $total count of hidden courses
     * @return string html
     */
    public function hidden_courses($total) {
        if ($total <= 0) {
            return;
        }
        $output = $this->output->box_start('notice');
        $plural = $total > 1 ? 'plural' : '';
        $config = get_config('block_mbsmycourses');
        // Show view all course link to user if forcedefaultmaxcourses is not empty.
        if (!empty($config->forcedefaultmaxcourses)) {
            $output .= get_string('hiddencoursecount' . $plural, 'block_mbsmycourses', $total);
        } else {
            $a = new stdClass();
            $a->coursecount = $total;
            $a->showalllink = html_writer::link(new moodle_url('/my/index.php', array('mynumber' => 0)), get_string('showallcourses'));
            $output .= get_string('hiddencoursecountwithshowall' . $plural, 'block_mbsmycourses', $a);
        }

        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Creates collapsable region
     *
     * @param string $contents existing contents
     * @param string $classes class names added to the div that is output.
     * @param string $id id added to the div that is output. Must not be blank.
     * @param string $caption text displayed at the top. Clicking on this will cause the region to expand or contract.
     * @param string $userpref the name of the user preference that stores the user's preferred default state.
     *      (May be blank if you do not wish the state to be persisted.
     * @param bool $default Initial collapsed state to use if the user_preference it not set.
     * @return bool if true, return the HTML as a string, rather than printing it.
     */
    protected function collapsible_region($contents, $classes, $id, $caption,
                                          $userpref = '', $default = false) {
        $output = $this->collapsible_region_start($classes, $id, $caption, $userpref, $default);
        $output .= $contents;
        $output .= $this->collapsible_region_end();

        return $output;
    }

    /**
     * Print (or return) the start of a collapsible region, that has a caption that can
     * be clicked to expand or collapse the region. If JavaScript is off, then the region
     * will always be expanded.
     *
     * @param string $classes class names added to the div that is output.
     * @param string $id id added to the div that is output. Must not be blank.
     * @param string $caption text displayed at the top. Clicking on this will cause the region to expand or contract.
     * @param string $userpref the name of the user preference that stores the user's preferred default state.
     *      (May be blank if you do not wish the state to be persisted.
     * @param bool $default Initial collapsed state to use if the user_preference it not set.
     * @return bool if true, return the HTML as a string, rather than printing it.
     */
    protected function collapsible_region_start($classes, $id, $caption,
                                                $userpref = '', $default = false) {
        // Work out the initial state.
        if (!empty($userpref) and is_string($userpref)) {
            user_preference_allow_ajax_update($userpref, PARAM_BOOL);
            $collapsed = get_user_preferences($userpref, $default);
        } else {
            $collapsed = $default;
            $userpref = false;
        }

        if ($collapsed) {
            $classes .= ' collapsed';
        }

        $output = '';
        $output .= '<div id="' . $id . '" class="collapsibleregion ' . $classes . '">';
        $output .= '<div id="' . $id . '_sizer">';
        $output .= '<div id="' . $id . '_caption" class="collapsibleregioncaption category-title"></div>';
        $output .= '<div>' . $caption . '</div>';
        $output .= '<div id="' . $id . '_inner" class="collapsibleregioninner">';
        $this->page->requires->js_init_call('M.block_mbsmycourses.collapsible', array($id, $userpref, get_string('clicktohideshow')));

        return $output;
    }

    /**
     * Close a region started with print_collapsible_region_start.
     *
     * @return string return the HTML as a string, rather than printing it.
     */
    protected function collapsible_region_end() {
        $output = '</div></div></div>';
        return $output;
    }

    /** render form to filter courses
     * 
     * @global type $CFG
     * @param type $userschools
     * @param type $filteroption
     * @return type
     */
    public function filter_form($content, $usersschools, $selectedschool,
                                $sortorder, $viewtype) {

        $form = '';

        // Render schoolmenu.
        $select = html_writer::select($usersschools, 'filter_school', $selectedschool, 
                array('' => get_string('selectschool', 'block_mbsmycourses')),
                array('id' => 'mbsmycourses_filterschool'));
        $form .= html_writer::tag('div', $select);

        // Render sortmenu.
        $choices = mbsmycourses::get_coursesortorder_menu();
        $select = html_writer::select($choices, 'sort_type', $sortorder, '', array('id' => 'mbsmycourses_sorttype'));
        $form .= html_writer::tag('div', $select);

        // Render radio switch.
        $radiogroup = '';

        foreach (array('list', 'grid') as $type) {

            $label = html_writer::tag('label', get_string($type, 'block_mbsmycourses'));
            $params = array('type' => 'radio', 'name' => 'switch_view', 'value' => $type);

            if ($type == $viewtype) {
                $params['checked'] = 'checked';
            }
            $radiogroup .= html_writer::tag('input', $label, $params);
        }

        $form .= html_writer::tag('div', $radiogroup, array('id' => 'mbsmycourses_viewtype'));
        $form .= $content;

        $output = html_writer::tag('form', $form, array('id' => 'filter_form', 'action' => new moodle_url('/my/index.php')));
        return $output;
    }

    /**
     * render button to load more results
     *
     * @return string return the HTML as a string, rather than printing it.
     */
    public function load_more_button() {
        $output = '';
        $output .= html_writer::empty_tag('input', array('type' => 'submit',
                    'name' => 'showallcourses',
                    'class' => 'btn_load_more_results',
                    'value' => get_string('load_more_results', 'block_mbsmycourses')));
        return $output;
    }

    /** render the the courses content area as list or grid
     * 
     * @param record $courses data of courses depending on viewtype mode.
     * @param string $viewtype 'list' or 'grid'
     * @param string $overviews 'news' grouped by courses, if there are some. 
     * @return string html for the courses list of grid list.
     */
    public function render_courses_content($courses, $viewtype, $overviews) {
        $content = '';

        if (empty($courses->sortedcourses)) {

            return get_string('nocourses', 'my');
        }

        if ($viewtype == 'grid') {
            $content .= $this->mbsmycourses_grid($courses, $overviews);
            $content .= $this->hidden_courses($courses->total - count($courses->sortedcourses));

            if ($courses->total > count($courses->sortedcourses)) {
                $content .= $this->load_more_button();
            }
        } else {

            $content .= $this->mbsmycourses_list($courses, $overviews);
        }

        return $content;
    }

}