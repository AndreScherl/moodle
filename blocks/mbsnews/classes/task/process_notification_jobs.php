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
 * socialwall course format, Tasks
 *
 * @package format_socialwall
 * @copyright 2014 Andreas Wagner, Synergy Learning
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbsnews\task;

class process_notification_jobs extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('processnotificationjobs', 'block_mbsnews');
    }

    public function execute() {
        
        \block_mbsnews\local\newshelper::delete_confirmed_messages();
        
        \block_mbsnews\local\newshelper::delete_expired_messages();
        
        \block_mbsnews\local\newshelper::process_notification_jobs();
    }
}