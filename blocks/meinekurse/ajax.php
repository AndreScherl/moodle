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
 * AJAX destination point for meinekurse block
 *
 * @package   block_meinekurse
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', 1);
require_once(dirname(__FILE__).'/../../config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/meinekurse/lib.php');

require_login();
require_sesskey();

$action = required_param('action', PARAM_ALPHA);

switch ($action) {
case 'setschool':
    $schoolid = required_param('schoolid', PARAM_INT);
    $prefs = meinekurse::get_prefs();
    $prefs->school = $schoolid;
    meinekurse::set_prefs($prefs);
    break;
case 'getcourses':
    $page = optional_param('meinekurse_page', 0, PARAM_INT);
    $sortby = optional_param('meinekurse_sortby', null, PARAM_ALPHA);
    $numcourses = optional_param('meinekurse_numcourses', null, PARAM_INT);

    break;
default:
    print_error('invalidaction', 'block_meinekurse');
}
