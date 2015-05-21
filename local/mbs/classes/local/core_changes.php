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
 * To store core changes linked to this pluign.
 *
 * @package   local_mbs
 * @copyright 2014 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mbs\local;

use context_system;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

class core_changes {

    public static function check_view_courses() {
        $context = context_system::instance();
        if (!has_capability('local/mbs:viewcourselist', $context)) {
            redirect(new moodle_url('/')); // Redirect to front page.
        }
    }

    /**
     * called from \course\edit_form.php function definition() 
     * @global type $PAGE
     */
    public static function add_shortname_check() {
        global $PAGE;
        $PAGE->requires->yui_module('moodle-local_mbs-shortname', 'M.local_mbs.shortname.init');
    }
}
