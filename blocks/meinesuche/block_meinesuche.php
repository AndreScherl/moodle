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
 * Main code for the Meine Schulen block
 *
 * @package   block_meinesuche
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

class block_meinesuche extends block_list {
    public function init() {
        $this->title = get_string('pluginname', 'block_meinesuche');
    }

    public function instance_can_be_docked() {
    	return false;
    }

    public function get_content() {
        global $CFG, $OUTPUT;
        require_once($CFG->dirroot.'/blocks/meinesuche/lib.php');

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();

        $this->content->footer = meinesuche::output_block_search_form();

        $arrowicon = $OUTPUT->pix_icon('i/navigationitem', '');


        return $this->content;
    }
    
    function has_config() {return true;}
}