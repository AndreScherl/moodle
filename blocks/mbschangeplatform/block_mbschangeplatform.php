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

		$this->content = new stdClass();		
		
		$changeurl = get_config('block_mbschangeplatform', 'changeurl');
		$linktext = get_config('block_mbschangeplatform', 'linktext');
		$imgpath = get_config('block_mbschangeplatform', 'imgpath');
		if(empty($changeurl) && empty($linktext) && empty($imgpath)){ //default-settings
			$img = $this->block_mbschangeplatform_set_imgtag(get_string('imgpathdefault','block_mbschangeplatform'));
			$this->block_mbschangeplatform_set_link(get_string('linkdefault','block_mbschangeplatform'), $img);
		} else if(empty($changeurl) && empty($imgpath)) { //new link text
			$this->block_mbschangeplatform_set_link(get_string('linkdefault','block_mbschangeplatform'), $linktext);
		} else if(empty($changeurl) && empty($linktext)) { //new img-path
			$this->block_mbschangeplatform_set_link(get_string('linkdefault','block_mbschangeplatform'), $this->block_mbschangeplatform_set_imgtag($imgpath));
		} else if(empty($imgpath) && empty($linktext)) { //new url
			$img = $this->block_mbschangeplatform_set_imgtag(get_string('imgpathdefault','block_mbschangeplatform'));
			$this->block_mbschangeplatform_set_link($changeurl, $img);
		} else if(empty($linktext)) { //new img-path and new url
			$this->block_mbschangeplatform_set_link($changeurl, $this->block_mbschangeplatform_set_imgtag($imgpath));
		} else if(empty($imgpath)) { //new link text and new url
			$this->block_mbschangeplatform_set_link($changeurl, $linktext);
		}			
		
		return $this->content;
	}
	
	// Enabling Global Configuration
	
	 /**
     * Allow the block to have a configuration page
     * Means: a blocks/.../settings.php file exists
	 *
     * @return boolean
     */
	public function has_config() { return true; }
	
	 /**
     * Set a hyperlink  as content text
	 *
     * @param string $link - destination address
	 * @param string $text - link text
     */
	public function block_mbschangeplatform_set_link($link, $text){
		$this->content->text = html_writer::link($link, $text);
	}
	
	 /**
     * Set a image html tag
	 *
     * @param string $img - Path to image. The path name must be specified realtiv to moodle_url.
	 * @return string $imgtag - A html <img> tag.
     */
	public function block_mbschangeplatform_set_imgtag($img){
		$imgtag = html_writer::empty_tag('img', array(
			'src' => new moodle_url($img),
			'alt' => get_string('imgalttext', 'block_mbschangeplatform')));
		return $imgtag;
	}
	
}   // END class block_mbschangeplatform