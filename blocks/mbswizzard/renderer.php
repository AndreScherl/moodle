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
 * Renderer for Block mbswizzard
 *
 * @package    block_mbswizzard
 * @copyright  Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_mbswizzard_renderer extends plugin_renderer_base {

    /** render the title of the block if its a fake block (without title)
     * 
     * @global object $OUTPUT
     * @return string HTML of block content.
     */
    public function title() {
        global $OUTPUT;
        $htmlstring = '';
        
        // Make title because fake block only has content
        $htmlstring .= html_writer::start_div('header');
        //$htmlstring .= html_writer::start_div('title');
        $htmlstring .= html_writer::tag('h2', get_string('displayname', 'block_mbswizzard'));
        //$htmlstring .= html_writer::end_div();
        $htmlstring .= html_writer::end_div();
        
        return $htmlstring;
    }
    
    /** render the content of the block 
     * 
     * @global record $USER
     * @return string HTML of block content.
     */
    public function content() {
        global $USER;
        $htmlstring = '';
        if(isset($USER->mbswizzard_activesequence) && ($USER->mbswizzard_activesequence != false)) {
            $sequencename = get_string(explode('wizzard_', $USER->mbswizzard_activesequence)[1], 'block_mbswizzard');
            $htmlstring .= html_writer::tag('p', get_string('activewizzard', 'block_mbswizzard').': "'.$sequencename.'"');
            $htmlstring .= $this->progressbar();
            $htmlstring .= $this->stepcounter();
            $htmlstring .= $this->abortbutton();
        } else {
            $htmlstring .= $this->sequencelist();
        }
        
        return $htmlstring;
    }
    
    /**
     * renders the list of links to start the wizzards (sequences)
     * 
     * @return string htmlstring of sequence list links
     */
    public function sequencelist() {
        $htmlstring = '';
        
        $sequences = \block_mbswizzard\local\mbswizzard::sequencefiles();
        
        $htmlstring .= html_writer::start_tag('ul');
        foreach ($sequences as $sequence) {
            $htmlstring .= html_writer::start_tag('li');
            $htmlstring .= html_writer::link('#', get_string('sequence_'.$sequence, 'block_mbswizzard'),
                    array('class' => 'link_wizzard', 'data-wizzard' => $sequence));
            $helpicon = new help_icon('sequence_'.$sequence, 'block_mbswizzard');
            $htmlstring .= ' '.$this->render($helpicon);
            $htmlstring .= html_writer::end_tag('li');
        }
        $htmlstring .= html_writer::end_tag('ul');
        
        return $htmlstring;
    }
    
    /**
     * renders the progress bar of the current wizzard (sequence)
     * 
     * @return string htmlsstring to display the sequence progress bar
     */
    public function progressbar() {
        $htmlstring = '';
        
        $htmlstring .= html_writer::start_div('progress'); //outer div
            $attr = array(
                'role' => 'progressbar',
                'aria-valuenow' => '0', // current sequence step
                'aria-valuemin' => '0', // first sequence step
                'aria-valuemax' => '10', // last sequence step
                'style' => 'width: 0%' // width of filled bar
            );
            $htmlstring .= html_writer::start_div('progress-bar progress-bar-striped active', $attr); // inner div
                $htmlstring .= html_writer::span('0% Complete', 'sr-only');
            $htmlstring .= html_writer::end_div(); //inner div
        $htmlstring .= html_writer::end_div(); //outer div
        
        return $htmlstring;
    }
    
    /**
     * renders html to display the curent position of a runnning wizzard
     * 
     * @return string - htmlstring of stepcounter
     */
    public function stepcounter() {
        $htmlstring = '';
        
        $htmlstring .= html_writer::start_div('stepcounter');
            $htmlstring .= get_string('step', 'block_mbswizzard').' ';
            $htmlstring .= html_writer::span('?', 'currentstepnumber').' ';
            $htmlstring .= get_string('of', 'block_mbswizzard').' ';
            $htmlstring .= html_writer::span('?', 'maxstepnumber').'.';
        $htmlstring .= html_writer::end_div();
        
        return $htmlstring;
    }
    
    /**
     * renders a button to abort an active wizzard sequence
     * 
     * return string - htmlstring
     */
    public function abortbutton() {
        $htmlstring = '';
        
        $htmlstring .= html_writer::link(new moodle_url('/my'), get_string('cancel', 'block_mbswizzard'),
                array('class' => 'cancel btn btn-cancel'));
        
        return $htmlstring;
    }
}

