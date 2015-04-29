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
 * mebis my courses block (based on course overview block)
 *
 * @package    block_mbsmycourses
 * @copyright  2015 Andreas Wagner <andreas.wagener@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/blocks/mbsmycourses/locallib.php');

class block_mbsmycourses extends block_base {
    /**
     * If this is passed as mynumber then showallcourses, irrespective of limit by user.
     */

    const SHOW_ALL_COURSES = -2;

    /**
     * Block initialization
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_mbsmycourses');
    }

    /**
     * Return contents of mbsmycourses block
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        global $USER, $CFG, $PAGE;

        require_once($CFG->dirroot . '/user/profile/lib.php');

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Number of visible listed courses in grid view.
        $updatemynumber = optional_param('mynumber', -1, PARAM_INT);
        if ($updatemynumber >= 0) {
            mbsmycourses::update_mynumber($updatemynumber);
        }

        profile_load_custom_fields($USER);

        // ...filter by schoolid.
        $selectedschool = $this->load_page_params('filter_school', 0, PARAM_INT);

        // ...order by sort_type.
        $sortorder = $this->load_page_params('sort_type', 'manual', PARAM_ALPHA);

        // ...need group by school, if type is list.
        $viewtype = $this->load_page_params('switch_view', 'grid', PARAM_ALPHA);

        // ...check, whether to limit courses.
        $showallcourses = (isset($_REQUEST['showallcourses']));

        if ($viewtype == 'grid') {
            $courses = mbsmycourses::get_sorted_courses($sortorder, $selectedschool, $showallcourses);
        } else {
            $courses = mbsmycourses::get_sorted_courses_group_by_school($sortorder, $selectedschool);
        }

        $renderer = $this->page->get_renderer('block_mbsmycourses');

        // Number of sites to display.
        $config = get_config('block_mbsmycourses');
        if ($this->page->user_is_editing() && empty($config->forcedefaultmaxcourses)) {
            $this->content->text .= $renderer->editing_bar_head($courses->total);
        }

        // For each course, build category cache.
        $overviews = mbsmycourses::get_overviews($courses->sitecourses);
        $content = $renderer->render_courses_content($courses, $viewtype, $overviews);

        $usersschools = mbsmycourses::get_users_school_menu();
        $this->content->text .= $renderer->filter_form($content, $usersschools, $selectedschool, $sortorder, $viewtype);

        $opts = array();
        $PAGE->requires->yui_module('moodle-block_mbsmycourses-searchform', 'M.block_mbsmycourses.searchform', array($opts));

        return $this->content;
    }

    /** load all the search params from request or userprefs
     * 
     */
    private function load_page_params($name, $default, $type) {
        global $USER;

        if (!isset($USER->preference['block_mbsmycourses' . $name])) {
            $USER->preference['block_mbsmycourses' . $name] = get_user_preferences('block_mbsmycourses' . $name, $default);
        }

        $value = optional_param($name, $USER->preference['block_mbsmycourses' . $name], $type);

        if ($value <> $USER->preference['block_mbsmycourses' . $name]) {

            $USER->preference['block_mbsmycourses' . $name] = $value;
            set_user_preference('block_mbsmycourses' . $name, $value);
        }

        return $USER->preference['block_mbsmycourses' . $name];
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my-index' => true);
    }

    /**
     * Sets block header to be hidden or visible
     *
     * @return bool if true then header will be visible.
     */
    public function hide_header() {

        // Hide header if welcome area is show.
        $config = get_config('block_mbsmycourses');
        return !empty($config->showwelcomearea);
    }

    /**
     * The block will not be collapsible.
     *
     * @return bool if false the block will not be collapsible.
     */
    public function instance_can_be_collapsed() {
        return false;
    }
}