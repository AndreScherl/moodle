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
 * @package    block_mbsmyschools
 * @copyright  2015 Andreas Wagner <andreas.wagener@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_mbsmyschools extends block_base {

    /**
     * Block initialization
     */
    public function init() {
        $this->title = get_string('displayname', 'block_mbsmyschools');
        $this->defaultweight = 100;
    }

    function get_required_javascript() {
        global $PAGE;
        parent::get_required_javascript();

        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('ui');
        $PAGE->requires->jquery_plugin('ui-css');
        $PAGE->requires->js(new moodle_url('/blocks/mbsmyschools/js/blockvisibility/blockvisibility.js'));
    }
    
    /**
     * Return contents of mbsmyschools block
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        global $USER, $CFG, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $renderer = $this->page->get_renderer('block_mbsmyschools');

        $usersschools = \local_mbs\local\schoolcategory::get_users_schools();
        $this->content->text .= $renderer->schoollist($usersschools);
        
        if ((!get_user_preferences('mbsmyschoolsnotshow', false)) || get_user_preferences('mbsmyschoolsnotshow') == 1) {
            user_preference_allow_ajax_update('mbsmyschoolsnotshow', PARAM_BOOL);
            $this->get_required_javascript();
        }

        return $this->content;
    }

    /**
     * Locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my-index' => true);
    }
    
    public function instance_can_be_collapsed() {
        return false;
    }
}