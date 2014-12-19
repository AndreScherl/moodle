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
 * mbs_coordinators block caps.
 *
 * @package    block_mbs_coordinators
 * @copyright  Andre Scherl <andre.scherl@isb.bayern.de>
 * @license    todo
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/blocks/mbs_coordinators/renderer.php');

class block_mbs_coordinators extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_mbs_coordinators');
    }

    function get_content() {
        global $CFG, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $renderer = $this->page->get_renderer('block_mbs_coordinators');
        $this->content->text .= $renderer->mbs_coordinators();

        return $this->content;
    }

    function hide_header() {
        return true;
    }
}
