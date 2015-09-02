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
 * Help note renderer.
 *
 * @package   theme_mebis
 * @copyright 2015 ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/lib/outputrenderers.php");

class theme_mebis_help_renderer extends renderer_base {

    private $pageactionnavigation = false;
    private $pagefastaccessnavigation = false;

    public function page_action_navigation() {

        if (!$this->pageactionnavigation) {
            $menu_items = array(
                html_writer::link('#top', '<i class="icon-me-back-to-top"></i>', array('class' => 'me-back-top'))
            );

            $output = html_writer::start_tag('div', array('class' => 'me-in-page-menu'));
            $output .= html_writer::start_tag('ul', array('class' => 'me-in-page-menu-features'));
            foreach ($menu_items as $item) {
                $output .= html_writer::tag('li', $item);
            }
            $output .= html_writer::end_tag('ul');
            $output .= html_writer::end_tag('div');

            $this->pageactionnavigation = true;
            return $output;
        }
    }

    /**
     * Add sidebar fastaccess-jump-navigation
     * 
     * @return string HTML fo the fast access menu.
     * @author Franziska Hübler, franziska.huebler@isb.bayern.de
     */
    public function page_fastaccess_navigation() {
        if (!$this->pagefastaccessnavigation) {
            $output = '';
            $myapps = html_writer::link('#my-apps', '<span>' . get_string('sidebar-apps', 'theme_mebis') . '</span>');
            $mysearch = html_writer::link('#mbssearch_form', '<span>' . get_string('sidebar-search', 'theme_mebis') . '</span>');

            $apps = html_writer::tag('div', '<span>' . $myapps . '</span>');
            $search = html_writer::tag('div', '<span>' . $mysearch . '</span>');

            $output .= html_writer::tag('li', $apps, array('id' => 'me-to-apps'));
            $output .= html_writer::tag('li', $search, array('id' => 'me-to-search'));

            $this->pagefastaccessnavigation = true;
            return $output;
        }
    }

    /**
     * Add sidebar section-jump-navigation
     * 
     * @return string HTML fo the section menu.
     * @author Franziska Hübler, franziska.huebler@isb.bayern.de
     */
    public function page_action_menu() {
        global $PAGE, $DB;
        $course = $PAGE->course;
        //get the number of sections
        $numsections = $DB->get_field('course_format_options', 'value', array('courseid' => $course->id, 'name' => 'numsections'));
        $course->numsections = $numsections;
        // get the course format
        $courseformat = course_get_format($PAGE->course);
        $format = $courseformat->get_format();

        // call the renderer
        $modinfo = get_fast_modinfo($course);
        $sectioninfo = $modinfo->get_section_info_all();
        switch ($format) {
            case 'topics':
                $renderer = $PAGE->get_renderer('theme_mebis', 'format_' . $format);
                return $renderer->render_page_action_menu($course, $sectioninfo);
            case 'topcoll':
                $renderer = $PAGE->get_renderer('theme_mebis', 'format_' . $format);
                return $renderer->render_page_action_menu($course, $sectioninfo);
            case 'onetopic':
                $renderer = $PAGE->get_renderer('theme_mebis', 'format_' . $format);
                return $renderer->render_page_action_menu($course, $sectioninfo, 'simple');
            default:
                break;
        }
    }

    public function get_adminnav_selectbox() {
        $nav = new mebis_admin_nav();
        return $nav->render_as_selectbox();
    }

}

class mebis_admin_nav {

    public $navigation;

    public function __construct() {
        global $PAGE, $CFG, $OUTPUT;

        $this->page = $PAGE;
        $nav = $this->page->settingsnav;
        $this->navigation = $this->get_admin_nav_items($nav);
    }

    public function render_as_selectbox() {
        if ($this->navigation) {
            $select = sprintf('<h3>%s</h3>', get_string('menu-administration-link', 'theme_mebis'));
            $select .= '<select data-change onchange="location = this.options[this.selectedIndex].value;">';
            foreach ($this->navigation as $key => $nav) {
                $select .= $this->render_option($nav);
            }
            $select .= '<select>';

            return $select;
        }
    }

    public function render_option($nav, $lvl = 0) {
        $url = $this->get_item_url($nav->action);
        $title = (gettype($nav->text) === 'string') ? $nav->text : $this->get_item_title($nav->text);
        $output = '';

        if ($title) {

            $title = str_repeat('&nbsp;&nbsp;&nbsp;', $lvl) . $title;

            if ($nav->nodetype == 0) {
                $selected = ($this->current_url() == $url) ? ' selected' : '';
                $output .= sprintf('<option value="%s"%s>%s</option>', $url, $selected, $title);
            } else {
                $childs = '';
                foreach ($nav->children as $child) {
                    $childs .= $this->render_option($child, $lvl + 1);
                }
                $output .= sprintf('<optgroup label="%s">%s</optgroup>', $title, $childs);
            }
        }

        return $output;
    }

    public function current_url() {
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') === FALSE ? 'http' : 'https';
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'];
        $params = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';

        $currentUrl = $protocol . '://' . $host . $script . $params;

        return $currentUrl;
    }

    public function get_item_title($text) {
        $text = (array) $text;
        $title = '';

        foreach ($text as $key => $val) {
            $key = str_replace('*', '', $key);
            $key = strip_tags($key);

            if ($key == 'string') {
                $title = $val;
            }
        }

        return $title;
    }

    public function get_item_url($action) {
        $action = (array) $action;

        $url = '';

        foreach ($action as $key => $val) {
            $key = str_replace('*', '', $key);
            $key = strip_tags($key);
            $params = '';

            if ($key == 'scheme') {
                $val = $val . '://';
            }

            if ($key != 'params') {
                $url .= $val;
            } else {

                $i = 0;
                foreach ($val as $key => $param) {
                    $prefix = (!$i) ? '?' : '&amp;';
                    $params .= sprintf('%s%s=%s', $prefix, $key, $param);
                    $i++;
                }
            }

            $url .= $params;
        }

        return $url;
    }

    public function get_admin_nav_items() {
        global $CFG, $OUTPUT;
        $nav = $this->page->settingsnav;

        foreach ($nav->children as $key => $children) {
            if (!$children->id && $children->key == 'root' && $children->text == get_string('administrationsite')) {
                return $children->children;
            }
        }

        return false;
    }

}
