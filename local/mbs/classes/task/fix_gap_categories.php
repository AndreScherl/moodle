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
 * @copyright 2014 Andreas Wagner, mebis Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mbs\task;

class fix_gap_categories extends \core\task\scheduled_task {      
    
    public function get_name() {
        // Shown in admin screens
        return get_string('fixgapcategories', 'local_mbs');
    }
                                                                     
    public function execute() {       
        \local_mbs\performance\fix_course_sortorder::cron();
    }                                                                                                                               
} 