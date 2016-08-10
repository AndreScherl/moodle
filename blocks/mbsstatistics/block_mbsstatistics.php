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
 * Main class
 *
 * @package    block_mbsstatistics
 * @copyright  René Egger <rene.egger@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_mbsstatistics\summary;

class block_mbsstatistics extends block_base {

    /**
     * Block initialization
     */
    public function init() {
        $this->title = get_string('displayname', 'block_mbsstatistics');
        $this->defaultweight = 100;
    }
    
    public function get_content() {
        global $PAGE;
        
        if ($this->content !== null) {
            return $this->content;
        }

        $summary = new summary();
        if (!$summary->has_content()) {
            return $this->content;
        }

        $this->content = new stdClass;
        $renderer = $PAGE->get_renderer('block_mbsstatistics');
        $this->content->text = $renderer->render($summary);
        $this->content->footer = '';

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
    
    public function specialization() {
        if (isset($this->config)) {
            if (empty($this->config->title)) {
                $this->title = get_string('defaulttitle', 'block_simplehtml');            
            } else {
                $this->title = $this->config->title;
            }

            if (empty($this->config->text)) {
                $this->config->text = get_string('defaulttext', 'block_simplehtml');
            }    
        }
    }
}
