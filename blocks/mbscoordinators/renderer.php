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
 * Renderer for Block mbscoordinators
 *
 * @package    block_mbscoordinators
 * @copyright  Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_mbscoordinators_renderer extends plugin_renderer_base {

    /** render all coordinators for a school in a unordered list
     * 
     * @global record $OUTPUT
     * @param array $coordinators list of all coordinators for this school.
     * @return type
     */
    public function render_coordinators($coordinators) {
        global $OUTPUT;

        $out = html_writer::tag('div', get_string('mebiscoordinators', 'block_mbscoordinators')).': ';

        foreach ($coordinators as $coordinator) {

            $messageurl = new moodle_url('/message/index.php', array('id' => $coordinator->id));
            $messageicon = $OUTPUT->pix_icon('t/email', get_string('sendmessage', 'block_mbscoordinators'));

            $li  = html_writer::link($messageurl, $messageicon);
            $li .= html_writer::link($messageurl, fullname($coordinator));

            $out .= html_writer::tag('li', $li);
        }
        $out = html_writer::nonempty_tag('ul', $out);

        return html_writer::tag('div', $out, array('class' => 'mbscoordinators'));
    }
    
    public function render_categoryheader($category) {
        return html_writer::tag('h2', $category->name);
    }

}