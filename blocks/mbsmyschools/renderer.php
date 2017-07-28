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
 * renderer for block_mbsmyschools.
 *
 * @package    block_mbsmyschools
 * @copyright  2015 Andreas Wagner <andreas.wagener@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_mbsmyschools_renderer extends plugin_renderer_base {

    /**
     * render list of users schools
     *
     * @return string html to be displayed
     */
    public function schoollist($usersschools) {

        $output = '';
        foreach ($usersschools as $school) {

            $url = new moodle_url("/course/index.php?categoryid=".$school->id);
            $link = html_writer::link($url, $school->name);
            $output .= html_writer::tag('li', $link);

        }

        return html_writer::tag('ul', $output);
    }

}