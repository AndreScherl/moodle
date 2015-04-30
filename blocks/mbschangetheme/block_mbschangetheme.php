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
 * main class for block coordinators.
 *
 * @package    block_mbschangetheme
 * @copyright  Andreas Wagner <andreas.wagner@isb.bayern.de>
 * @license    todo
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/mbschangetheme/renderer.php');

class block_mbschangetheme extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_mbschangetheme');
    }

    public function get_content() {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $renderer = $PAGE->get_renderer('block_mbschangetheme');
        $this->content->text .= $renderer->render_content();

        return $this->content;
    }

    public function has_config() {
        return true;
    }

    public function applicable_formats() {

        return array('my' => true);
    }

}