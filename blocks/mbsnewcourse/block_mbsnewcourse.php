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
 * main class of block_mbsnewcourse
 *
 * @package   block_mbsnewcourse
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/coursecatlib.php');

class block_mbsnewcourse extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_mbsnewcourse');
    }

    public function get_content() {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // ...check context of current page and get categoryid.
        $context = $PAGE->context;

        $categoryid = 0;
        if ($context->contextlevel == CONTEXT_COURSECAT) {

            $categoryid = $context->instanceid;
            $category = coursecat::get($categoryid, MUST_EXIST);

            // ... display no content above Schoolcategories.
            if ($category->depth < \local_mbs\local\schoolcategory::$schoolcatdepth) {
                return $this->content;
            }
        }
        //need CONTEXT_SYSTEM for Default Dashboard page
        if ($context->contextlevel == CONTEXT_USER || $context->contextlevel == CONTEXT_SYSTEM) {
            // ... display warning, when user has no schoolcategory.
            if (!$categoryid = \local_mbs\local\schoolcategory::get_users_schoolcatid()) {
                $this->content->text = get_string('missinginstitutionid', 'block_mbsnewcourse');
                return $this->content;
            }
        }

        if (!empty($categoryid)) {
            $renderer = $PAGE->get_renderer('block_mbsnewcourse');
            $this->content->text .= $renderer->render_block_content($categoryid);
        }
        return $this->content;
    }

    public function hide_header() {
        return true;
    }

    public function has_config() {
        return true;
    }

    public function applicable_formats() {
        // block should not be set on sites with fix rendered version of this block
        return array('all' => true, 'my' => false, 'course-index-category' => false);
    }

}