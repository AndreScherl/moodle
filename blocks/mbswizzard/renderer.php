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
    public function render_title() {
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
     * @global object $PAGE
     * @global object $OUTPUT
     * @return string HTML of block content.
     */
    public function render_content() {
        global $USER, $PAGE, $OUTPUT;
        $htmlstring = '';
        
        $sequences = \block_mbswizzard\local\mbswizzard::sequencefiles();
        
        $htmlstring .= html_writer::start_tag('ul');
        foreach ($sequences as $sequence) {
            $htmlstring .= html_writer::start_tag('li');
            $htmlstring .= html_writer::link('#', get_string('sequence_'.$sequence, 'block_mbswizzard'), array('class' => 'link_wizzard', 'data-wizzard' => 'course_create'));
            $htmlstring .= html_writer::end_tag('li');
        }
        $htmlstring .= html_writer::end_tag('ul');
        
        return $htmlstring;
    }
}

