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
 * Block mbslicenseinfo
 *
 * @package   block_mbslicenseinfo
 * @copyright Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_mbslicenseinfo extends block_base {

    function init() {
        $this->title = get_string('displayname', 'block_mbslicenseinfo');
    }

    function get_content() {
        global $CFG, $OUTPUT, $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        if (! empty($this->config->text)) {
            $this->content->text .= $this->config->text;
        }
        
        $formurl = new moodle_url('/blocks/mbslicenseinfo/editlicenses.php', array('course' => $COURSE->id));
        $editbutton = html_writer::tag('button', get_string('editlicensesdescr', 'block_mbslicenseinfo'));
        $editlink = html_writer::link($formurl, $editbutton);
        $this->content->text .= $editlink;

        return $this->content;
    }

    // my moodle can only have SITEID and it's redundant here, so take it away
    public function applicable_formats() {
        return array('all' => false,
                     'site' => true,
                     'site-index' => true,
                     'course-view' => true, 
                     'course-view-social' => false,
                     'mod' => true, 
                     'mod-quiz' => false);
    }

    function has_config() {return true;}
    
}
