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
 * Main class for block mbsmyschools
 *
 * @package    block_mbsmyschools
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/user/profile/lib.php'); //required for profile_load_data and other

require_sesskey();
require_login();

$forever = optional_param('forever', 0, PARAM_BOOL);

if ($forever) {
    echo render_notification();
    update_profile_field();
}

function render_notification() {
    $o = html_writer::tag('p', get_string('closealertexpl', 'block_mbsmyschools'));
    $b = html_writer::tag('button', get_string('closealert', 'block_mbsmyschools'), array('id' => 'closealert'));
    $o .= html_writer::tag('div', $b);
    return html_writer::tag('div', $o, array('id' => 'closealertoverlay'));
}

/**
 * Change profile_field_mbsmyschoolsshow to false, means the block mbsmyschools will not be shown
 * @global type $USER
 */
function update_profile_field() {
    global $USER;
    $theuser = clone($USER);
    profile_load_data($theuser);
    $theuser->profile_field_mbsmyschoolsshow = 0;
    profile_save_data($theuser);
}
