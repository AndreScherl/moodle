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
 * mbsgettingstarted block caps.
 *
 * @package    block_mbsgettingstarted
 * @copyright  Andre Scherl <andre.scherl@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_mbsgettingstarted extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_mbsgettingstarted');
        $this->defaultweight = -100;
    }

   function get_required_javascript() {
        parent::get_required_javascript();
 
        $this->page->requires->jquery();
        $this->page->requires->jquery_plugin('ui');
        $this->page->requires->jquery_plugin('ui-css');
        $this->page->requires->js(new moodle_url('/blocks/mbsgettingstarted/js/wizzard/wizzard.js'));
    }

    function get_content() {
        if ($this->content !== null) {
		    return $this->content;
		}        

	$this->content = new stdClass();
        $this->content->text = '';
        
        $renderer = $this->page->get_renderer('block_mbsgettingstarted');
        $this->content->text .= $renderer->all();
       // $this->content->text .= "<a id=\"link_assistant_course_create\" class=\"link_assistant\" href=\"".new moodle_url("/my")."\">Assistent zum Kurs anlegen starten</a>";        

        return $this->content;
    }

    public function applicable_formats() {
        return array('my-index' => true);
    }
    
    public function instance_can_be_docked() {
        return false;
    }
    
    /**
     * Default return is false - header will be shown
     * @return boolean
     */
    function hide_header() {
        return true;
    }
}
