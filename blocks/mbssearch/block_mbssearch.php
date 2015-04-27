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
 * Main class for block mbssearch
 *
 * @package   block_search
 * @copyright 2015 Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class block_mbssearch extends block_base {

    public function init() {

        $this->title = get_string('pluginname', 'block_mbssearch');
    }

    public function get_content() {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin()) {
            return $this->content;
        }

        $renderer = $PAGE->get_renderer('block_mbssearch');
        $this->content->text .= $renderer->render_block_content();

        return $this->content;
    }

    public function has_config() {
        return true;
    }
    
    function applicable_formats() {
        // Default case: the block can be used in courses and site index, but not in activities
        // self test of block base class will fail if sum of the format array is zero
        // workaround: set format true for unimportant context
        return array('all' => false, 'site-index' => true);
    }

}