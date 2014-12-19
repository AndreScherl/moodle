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

require_once($CFG->dirroot.'/blocks/mbs_newcourse/locallib.php');

/**
 * mbs_newcourse block rendrer
 *
 * @copyright  2014 Andre Scherl <andre.scherl@isb.bayern.de>
 * @license    todo
 */
class block_mbs_newcourse_renderer extends plugin_renderer_base {

    /**
     * Construct contents of mbs_newcourse block
     *
     * @return string html to be displayed in mbs_newcourse block
     */
    public function mbs_newcourse() {
        $html = '';
        $html .= $this->output_linklist();
        return $html;
    }

    /**
     * Return a formatted list of request and create course link.
     *
     * @return string
     */
    protected function output_linklist() {
        global $OUTPUT, $USER;

        $out = '';
        
        if(mbs_newcourse::can_request_course()) {
            $requestlink = html_writer::link('#', get_string('requestcourse', 'block_mbs_newcourse'));
            $out .= html_writer::tag('li', $requestlink);
        }
        if (mbs_newcourse::can_create_course()) {
            $createlink = html_writer::link('#', get_string('createcourse', 'block_mbs_newcourse'));
            $out .= html_writer::tag('li', $createlink);
        }
        $out = html_writer::tag('ul', $out);

        return $out;
    }
}
