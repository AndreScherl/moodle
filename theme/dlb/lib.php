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
 * Library functions for the dlb theme
 *
 * @package   theme_dlb
 * @copyright 2011 Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * dlb theme post process function for CSS
 * @param string $css Incoming CSS to process
 * @param stdClass $theme The theme object
 * @return string The processed CSS
 */
function dlb_process_css($css, $theme) {

    global $CFG, $PAGE;

    $css = preg_replace('/\[\[fonts:([^\|]+)\|(.*?)\]\]/i', $CFG->wwwroot.'/theme/dlb/fonts/\1.\2', $css);

    return $css;
}



