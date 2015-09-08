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
    
    /** 
     * Render the welcome title of the block
     * 
     * @global object $USER
     * @return string HTML of block content.
     */
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
       
    /** 
     * Render the closing section of the block
     * 
     * @return string HTML of block content.
     */
    public function close(){
        $output = '';
        $output .= html_writer::start_div('col-md-12 text-right', array('id' => 'me-help-box'));
        $output .= html_writer::link('#', '<i class="fa fa-ban"></i> ' . get_string('helpnoteremovepermanent', 'block_mbsgettingstarted'), array('id' => 'mbsgettingstarted_closeforever'));
        $output .= html_writer::link('#', '<i class="fa fa-close"></i> ' . get_string('helpnoteclose', 'block_mbsgettingstarted'), array('id' => 'mbsgettingstarted_closeforsession'));
        $output .= html_writer::end_div();
        return $output;       
    }
    
    /** 
     * Render the link section and the video
     *      
     * @return string HTML of block content.
     */
    public function content(){
        $output = '';
        $wizzard = '';
        $aid = '';
        $video = '';
        
        //buttons
        $wizzard .= html_writer::start_tag('li');
        $wizzard .= html_writer::link('#', get_string('sequence_course_create', 'block_mbswizzard'), array('data-wizzard' => 'course_create', 'class' => 'btn btn-secondary btn-lg link_wizzard', 'id' => 'mbswizard_course_create'));
        $wizzard .= html_writer::end_tag('li'); $wizzard .= html_writer::start_tag('li');
        $wizzard .= html_writer::link('#',  get_string('sequence_course_setup', 'block_mbswizzard'), array('data-wizzard' => 'course_setup', 'class' => 'btn btn-secondary link_wizzard', 'id' => 'mbswizard_course_setup'));
        $wizzard .= html_writer::end_tag('li'); $wizzard .= html_writer::start_tag('li');
        $wizzard .= html_writer::link('#',  get_string('sequence_first_learningsequence', 'block_mbswizzard'), array('data-wizzard' => 'first_learningsequence', 'class' => 'btn btn-secondary link_wizzard', 'id' => 'mbswizard_first_learningsequence'));
        $wizzard .= html_writer::end_tag('li');
        $wizzards = html_writer::tag('ul', $wizzard);
        $wizzards = html_writer::tag('div', $wizzards, array('class' => 'col-lg-4 col-md-6 col-xs-12 wizzardlinks'));
        
        $aidlinks = array('tutorial', 'training', 'contact');
        foreach ($aidlinks as $link ) {
            $url = get_config('block_mbsgettingstarted', $link.'url');
            if (empty($url)) {
                $url = get_string($link.'link', 'block_mbsgettingstarted');
            }   
            $aid .= html_writer::start_tag('li');
            $aid .= html_writer::link($url,  get_string($link, 'block_mbsgettingstarted'), array('class' => 'btn btn-secondary', 'id' => 'mbswizzard_'.$link));
            $aid .= html_writer::end_tag('li');
        }                
        $support = html_writer::tag('ul', $aid);
        $support = html_writer::tag('div', $support, array('class' => 'col-lg-4 col-md-6 col-xs-12 aidlinks'));
        
        //video
        $videourl = get_config('block_mbsgettingstarted', 'videourl');
        if (empty($videourl)) {
            $videourl = get_string('video', 'block_mbsgettingstarted');
        }        
        $video .= html_writer::start_div('video-container',  array('id' => 'mydashboardvideo'));
            $video .= html_writer::start_tag('video', array(
                'width' => '100%',
                'controls' => 'controls'
            ));  
                $video .= html_writer::empty_tag('source',  array(
                    'src' => new moodle_url($videourl),
                    'type' => 'video/mp4'
                ));
                $video .= get_string('videoalttext', 'block_mbsgettingstarted');
            $video .= html_writer::end_tag('video');          
        $video .= html_writer::end_div();

        $output .= $wizzards;
        $output .= $support;        
        
        $output .= html_writer::tag('div', $video, array('class' => 'col-lg-4 col-md-12 col-xs-12')); 
        return $output;
    }
    
    /** 
     * Render the content of the block
     * 
     * @return string HTML of block content.
     */
    public function all(){
        $welcome = $this->welcome();
        $close = $this->close();
        $content = $this->content();
        
        $output = html_writer::tag('div', $close . $welcome . $content, array('class' => 'me-help-note-container container-fluid'));
        
        $output = html_writer::tag('div', $output, array('class' => 'row me-help-note', 'id' => 'me-help-box'));
        return $output;  
    }
    
}