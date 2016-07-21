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
 * mbswizzard block caps.
 *
 * @package    block_mbswizzard
 * @copyright  Andre Scherl <andre.scherl@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_mbswizzard extends block_base {

    function init() {
        $this->title = get_string('displayname', 'block_mbswizzard');
    }
    
    function get_required_javascript() {
        global $PAGE;
        parent::get_required_javascript();

        $PAGE->requires->js_call_amd('block_mbswizzard/mbswizzard', 'initialize');
    }

    function get_content() {
        global $CFG, $OUTPUT, $PAGE;
        
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';
        
        $renderer = $PAGE->get_renderer('block_mbswizzard');
        
        if($PAGE->theme->name != ('mebis' || 'mebiscontrast')) {
            $this->content->text = get_string('onlymebistheme', 'block_mbswizzard');
            return $this->content;
        }
        
        if($this->instance === null) {
            $PAGE->requires->js_call_amd('block_mbswizzard/mbswizzard', 'initialize');
            $this->content->text .= $renderer->title();
        }
        $this->content->text .= $renderer->content();

        return $this->content;
    }

    // my moodle can only have SITEID and it's redundant here, so take it away
    public function applicable_formats() {
        return array('my' => true);
    }
}
