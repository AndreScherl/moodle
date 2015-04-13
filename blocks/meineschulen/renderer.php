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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/meineschulen/lib.php');

/**
 * meineschulen block rendrer
 *
 * @copyright  2014 Andre Scherl <andre.scherl@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_meineschulen_renderer extends plugin_renderer_base {

    /**
     * Construct contents of meineschulen block
     *
     * @return string html to be displayed in meineschulen block
     */
    public function meineschulen() {
        $output = "";
        $output .= $this->schoollist();
        return $output;
    }

    /**
     * Construct list of users schools
     *
     * @return string html to be displayed
     */
    public function schoollist() {
        $output = "";
        $output .= html_writer::start_tag("ul", array("class" => "list_myschools"))."\n";
        $schools = meineschulen::get_my_schools();
        foreach ($schools as $key => $value) {
            $output .= html_writer::start_tag("li");
            $output .= html_writer::link(new moodle_url("/course/index.php?categoryid=".$value->id), $value->name);
            $output .= html_writer::end_tag("li")."\n";
        }
        $output .= html_writer::end_tag("ul");
        return $output;
    }
}
