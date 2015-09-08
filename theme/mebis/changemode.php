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
 * Class for ajax call
 *
 * @package    theme_mebis
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_sesskey();
require_login();
global $THEME, $PAGE;

$PAGE->set_context(context_system::instance());


//0 - mebis
//1 - mebis-contrast
$change = optional_param('mode', 0, PARAM_BOOL);


if ($change) {
    $PAGE->theme->sheets = array('mebis-contrast'); 
    theme_reset_all_caches();
} else {
     $PAGE->theme->sheets = array('mebis');
    theme_reset_all_caches();
}

/**
 * Simple helper to debug to the console
 *
 * @param  object, array, string $data
 * @return string
 */
function debug_to_console($data) {
    $output = '';
    $output .= 'console.info( \'Debug in Console:\' );';
    $output .= 'console.log(' . json_encode($data) . ');';

    echo '<script>' . $output . '</script>';
}
