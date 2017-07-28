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

class block_mbsgettingstarted_renderer extends plugin_renderer_base {
    
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
        $output .= html_writer::link('#', get_string('helpnoteremovepermanent', 'block_mbsgettingstarted').' ', array('id' => 'me-help-box-closeforever', 'data-close' => 'me-help-box', 'data-close-type' => 'forever'));
        $output .= html_writer::link('#', get_string('helpnoteclose', 'block_mbsgettingstarted'), array('id' => 'me-help-box-close', 'data-close' => 'me-help-box', 'data-close-type' => 'simple'));
        $output .= html_writer::end_div();
        return $output;       
    }
    
    public function content(){
        $output = '';
        $wizzard = '';
        $aid = '';
        $video = '';
        
        $wizzard .= html_writer::start_tag('li');
        $wizzard .= html_writer::link(new moodle_url('/my'), get_string('sequence_course_create', 'block_mbswizzard'), array('id' => 'link_assistant_course_create','class' => 'btn btn-lg link_assistant'));
        $wizzard .= html_writer::end_tag('li'); $wizzard .= html_writer::start_tag('li');
        $wizzard .= html_writer::link(new moodle_url('/my'),  get_string('sequence_course_setup', 'block_mbswizzard'), array('class' => 'btn'));
        $wizzard .= html_writer::end_tag('li'); $wizzard .= html_writer::start_tag('li');
        $wizzard .= html_writer::link(new moodle_url('/my'),  get_string('sequence_first_learningsequence', 'block_mbswizzard'), array('class' => 'btn'));
        $wizzard .= html_writer::end_tag('li');
        $wizzards = html_writer::tag('ul', $wizzard);
        $wizzards = html_writer::tag('div', $wizzards, array('class' => 'col-md-4'));
        
        $aid .= html_writer::start_tag('li');
        $aid .= html_writer::link(new moodle_url("/my"),  get_string('tutoriallink', 'block_mbsgettingstarted'), array('class' => 'btn'));
        $aid .= html_writer::end_tag('li'); $aid .= html_writer::start_tag('li');
        $aid .= html_writer::link(new moodle_url("/my"),  get_string('traininglink', 'block_mbsgettingstarted'), array('class' => 'btn'));
        $aid .= html_writer::end_tag('li'); $aid .= html_writer::start_tag('li');
        $aid .= html_writer::link(new moodle_url("/my"),  get_string('contactlink', 'block_mbsgettingstarted'), array('class' => 'btn'));
        $aid .= html_writer::end_tag('li');
        $support = html_writer::tag('ul', $aid);
        $support = html_writer::tag('div', $support, array('class' => 'col-md-3'));
        
        /*$video .= html_writer::empty_tag('iframe', array(
			'src' => new moodle_url('https://www.youtube.com/embed/c-ysQD2enLg'),
			'alt' => get_string('videoalttext', 'block_mbsgettingstarted')));*/

        $output .= $wizzards;
        $output .= $support;
        $output .= html_writer::tag('div', $video, array('class' => 'col-md-5')); 
        return $output;
    }
    
    public function all(){
        $welcome = $this->welcome();
        $close = $this->close();
        $content = $this->content();
        
        $output = html_writer::tag('div', $close . $welcome . $content, array('class' => 'me-help-note-container clearfix'));
        
        $output = html_writer::tag('div', $output, array('class' => 'row me-help-note', 'id' => 'me-help-box'));
        return $output;  
    }
}