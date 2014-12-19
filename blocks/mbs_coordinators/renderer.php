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
 * mbs_coordinators block rendrer
 *
 * @package    block_mbs_coordinators
 * @copyright  2014 Andre Scherl <andre.scherl@isb.bayern.de>
 * @license    todo
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/mbs_coordinators/classes/mbs_coordinators.php');

/**
 * mbs_my_courses block rendrer
 *
 * @copyright  2014 Andre Scherl <andre.scherl@isb.bayern.de>
 * @license    todo
 */
class block_mbs_coordinators_renderer extends plugin_renderer_base {

    /**
     * Construct contents of mbs_coordinators block
     *
     * @return string html to be displayed in mbs_coordinators block
     */
    public function mbs_coordinators() {
        $html = '';
        $html .= $this->output_coordinators();
        return $html;
    }

    /**
     * Return a formatted list of school coordinators.
     *
     * @return string
     */
    protected function output_coordinators() {
        global $OUTPUT, $USER;

        $out = '';

        //! only fake output to make styling possible

        // $coordinators = mbs_coordinators::get_coordinators();
        // foreach ($coordinators as $coordinator) {
        //     $messageurl = new moodle_url('/message/index.php', array('id' => $coordinator->id));
        //     $messageicon = $OUTPUT->pix_icon('t/email', get_string('sendmessage', 'block_meineschulen'));
        //     $messagelink = html_writer::link($messageurl, $messageicon);
        //     $context = context_coursecat::instance($this->page->id);
        //     if (has_capability('moodle/user:viewdetails', $context, $USER->id)) {
        //         $profileurl = new moodle_url('/user/profile.php', array('id' => $coordinator->id));
        //     } else {
        //         $profileurl = $messageurl;
        //     }
        //     $coordlink = $messagelink.' '.html_writer::link($profileurl, fullname($coordinator));
        //     $out .= html_writer::tag('li', $coordlink);
        // }
        // $out = html_writer::nonempty_tag('ul', $out);
        
        $messageicon = $OUTPUT->pix_icon('t/email', get_string('sendmessage', 'block_meineschulen'));
        $messagelink = html_writer::link('#', $messageicon);
        $coordlink1 = $messagelink.' '.html_writer::link('#', 'Koordinator 1');
        $out .= html_writer::tag('li', $coordlink1);
        $coordlink2 = $messagelink.' '.html_writer::link('#', 'Koordinator 2');
        $out .= html_writer::tag('li', $coordlink2);
        
        $out = html_writer::tag('ul', $out);

        return $out;
    }
}
