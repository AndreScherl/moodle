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
 * Main class for block mbsgettingstarted
 *
 * @package    block_mbsgettingstarted
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class block_mbsgettingstarted extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_mbsgettingstarted');
    }

    function get_required_javascript() {
        global $PAGE;
        parent::get_required_javascript();

        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('ui');
        $PAGE->requires->jquery_plugin('ui-css');
        $PAGE->requires->js(new moodle_url('/blocks/mbsgettingstarted/js/blockvisibility/blockvisibility.js'));
}

    function get_content() {
        global $PAGE;
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        $renderer = $PAGE->get_renderer('block_mbsgettingstarted');
        $this->content->text .= $renderer->all();

        if ((!get_user_preferences('mbsgettingstartednotshow', false)) || get_user_preferences('mbsgettingstartednotshow') == 1) {
            user_preference_allow_ajax_update('mbsgettingstartednotshow', PARAM_BOOL);
            $this->get_required_javascript();
        }
                
        return $this->content;
    }

    public function applicable_formats() {
        return array('my' => true);
    }

    public function instance_can_be_docked() {
        return false;
    }

  

}
