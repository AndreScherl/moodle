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
 * library for mebis theme
 *
 * @package   theme_mebis
 * @copyright 2015 ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function theme_mebis_process_css($css, $theme) {
    // run compass compile during the css processing phase...
    $curDir = __DIR__;
    // exec("cd ${curDir} && compass compile -c compass.rb");

    return $css;
}

/*function theme_mebis_bootstrap_grid($hassidepre, $hassidepost)
{
    if ($hassidepre && $hassidepost) {
        $regions = array('content' => 'col-sm-12 col-lg-12 col-md-12');
        $regions['pre'] = 'col-sm-12 col-lg-12 col-md-12';
        $regions['post'] = 'col-sm-12 col-lg-12 col-md-12';
    } else if ($hassidepre && !$hassidepost) {
        $regions = array('content' => 'col-sm-9 col-lg-10');
        $regions['pre'] = 'col-sm-12 col-lg-12 col-md-12';
        $regions['post'] = 'emtpy';
    } else if (!$hassidepre && $hassidepost) {
        $regions = array('content' => 'col-sm-9 col-lg-10');
        $regions['pre'] = 'empty';
        $regions['post'] = 'col-sm-12 col-lg-12 col-md-12';
    } else if (!$hassidepre && !$hassidepost) {
        $regions = array('content' => 'col-md-12');
        $regions['pre'] = 'empty';
        $regions['post'] = 'empty';
    }

    if ('rtl' === get_string('thisdirection', 'langconfig')) {
        if ($hassidepre && $hassidepost) {
            $regions['pre'] = 'col-sm-12 col-lg-12 col-md-12 ';
            $regions['post'] = 'col-sm-12 col-lg-12 col-md-12 ';
        } else if ($hassidepre && !$hassidepost) {
            $regions = array('content' => 'col-sm-9 col-sm-push-3 col-lg-10 col-lg-push-2');
            $regions['pre'] = 'col-sm-12 col-lg-12 col-md-12 ';
            $regions['post'] = 'emtpy';
        } else if (!$hassidepre && $hassidepost) {
            $regions = array('content' => 'col-sm-9 col-lg-10');
            $regions['pre'] = 'empty';
            $regions['post'] = 'col-sm-12 col-lg-12 col-md-12 ';
        }
    }
    return $regions;
}*/
