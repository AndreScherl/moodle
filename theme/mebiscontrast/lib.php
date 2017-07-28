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
 * library for mebiscontrast theme
 *
 * @package   theme_mebiscontrast
 * @copyright 2015 ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//function theme_mebis_process_css($css, $theme) {
//    // run compass compile during the css processing phase...
//    $curDir = __DIR__;
//    // exec("cd ${curDir} && compass compile -c compass.rb");
//
//    return $css;
//}

function theme_mebiscontrast_page_init(moodle_page $page) {
    $page->requires->jquery();
    $page->requires->jquery_plugin('mebis-mebis', 'theme_mebis');
    // Andre Scherl: the following line differs from the moodle standard to include jquery modules. This is needed because
    // we have to put the script into a global mebis code sharing directory (mbsglobaldesign)
    $page->requires->js(new moodle_url("/theme/mebis/mbsglobaldesign/javascripts/jquery.mebis.js"));
}

