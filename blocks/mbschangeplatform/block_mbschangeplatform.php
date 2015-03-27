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
 * mbschangeplatform block caps.
 *
 * @package    block_mbschangeplatform
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();
 
class block_mbschangeplatform extends block_base {

	// init(): its purpose is to give values to any class member variables that need instantiating
    public function init() {
		// $this->title is the title displayed in the header of our block
        $this->title = get_string('mbschangeplatform', 'block_mbschangeplatform');
    }
	
    public function get_content() {
        if ($this->content !== null) {
		    return $this->content;
		}
 
		$this->content         = new stdClass();
		$this->content->text   = get_string('link','block_mbschangeplatform');
		return $this->content;
	}
	
	// Enabling Global Configuration
	
	// Means: a blocks/.../settings.php file exists
	function has_config() { return true; }
	
}   // END class block_mbschangeplatform