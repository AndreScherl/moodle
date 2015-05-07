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
 * Renderer for block_mbsgettingstarted
 *
 * @package    theme_mebis
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/mbsgettingstarted/renderer.php');
require_once($CFG->libdir . '/blocklib.php');

class theme_mebis_block_mbsgettingstarted_renderer extends block_mbsgettingstarted_renderer {
    
    public function welcome(){
        global $USER;
        $output = '';
        $username = '';
        if (isloggedin()) {
            $username .= fullname($USER);
        }
        $message = '<h3>' . sprintf(get_string('helpnotewelcome', 'block_mbsgettingstarted'), $username) . '</h3>';
        $output .= html_writer::tag('div', $message, array('class' => 'col-md-12 text-left'));
        return $output;
    }
        
    public function close(){
        $output = '';
        $output .= html_writer::start_div('col-md-12 text-right', array('id' => 'me-help-box'));
        $output .= html_writer::link('#', '<i class="fa fa-ban"></i> ' . get_string('helpnoteremovepermanent', 'block_mbsgettingstarted'), array('id' => 'mbsgettingstarted_closeforever'));
        $output .= html_writer::link('#', '<i class="fa fa-close"></i> ' . get_string('helpnoteclose', 'block_mbsgettingstarted'), array('id' => 'mbsgettingstarted_closeforsession'));
        $output .= html_writer::end_div();
        return $output;       
    }
    
    public function content(){
        $output = '';
        $wizzard = '';
        $aid = '';
        $aidlinks = '';
        $video = '';
        
        $wizzard .= html_writer::start_tag('li');
        $wizzard .= html_writer::link(new moodle_url("/my"), get_string('wizzardcoursecreate', 'block_mbsgettingstarted'), array('data-wizzard' => 'course_create', 'class' => 'btn btn-secondary btn-lg link_wizzard'));
        $wizzard .= html_writer::end_tag('li'); $wizzard .= html_writer::start_tag('li');
        $wizzard .= html_writer::link(new moodle_url("/my"),  get_string('wizzardcoursesetup', 'block_mbsgettingstarted'), array('data-wizzard' => 'course_setup', 'class' => 'btn btn-secondary link_wizzard'));
        $wizzard .= html_writer::end_tag('li'); $wizzard .= html_writer::start_tag('li');
        $wizzard .= html_writer::link(new moodle_url("/my"),  get_string('wizzardfirstlearningsequenz', 'block_mbsgettingstarted'), array('data-wizzard' => 'first_learningsequenz', 'class' => 'btn btn-secondary link_wizzard'));
        $wizzard .= html_writer::end_tag('li');
        $wizzards = html_writer::tag('ul', $wizzard, array('class' => 'text-right text-mobile-left'));
        $wizzards = html_writer::tag('div', $wizzards, array('class' => 'col-md-4 col-xs-6 wizzardlinks'));
        
        $aidlinks = array('tutoriallink', 'traininglink', 'contactlink');
        foreach($aidlinks as $link ){
            $aid .= html_writer::start_tag('li');
            $aid .= html_writer::link(new moodle_url("/my"),  get_string($link, 'block_mbsgettingstarted'), array('class' => 'btn btn-secondary'));
            $aid .= html_writer::end_tag('li');
        }                
        $support = html_writer::tag('ul', $aid, array('class' => 'text-left'));
        $support = html_writer::tag('div', $support, array('class' => 'col-md-3 col-xs-6 aidlinks'));
        
        /*$video .= html_writer::empty_tag('iframe', array(
			'src' => new moodle_url('https://www.youtube.com/embed/c-ysQD2enLg'),
			'alt' => get_string('videoalttext', 'block_mbsgettingstarted')));*/

        $output .= $wizzards;
        $output .= $support;
        
        
        $output .= html_writer::tag('div', $video, array('class' => 'col-md-5 col-xs-12')); 
        return $output;
    }
    
    public function all(){
        $welcome = $this->welcome();
        $close = $this->close();
        $content = $this->content();
        
        $output = html_writer::tag('div', $close . $welcome . $content, array('class' => 'me-help-note-container container-fluid'));
        
        $output = html_writer::tag('div', $output, array('class' => 'row me-help-note', 'id' => 'me-help-box'));
        return $output;  
    }

}