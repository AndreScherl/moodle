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
 * class for mbs_coordinators block
 *
 * @package    block_mbs_coordinators
 * @copyright  2012 Adam Olley <adam.olley@netspot.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mbs_coordinators {

    public static function can_see_coordinators() {
        // todo

        return true;
    }

    /**
     * Return a list of all the users who are 'coordinators' for this school.
     *
     * @return object[]
     */
    // public static function get_coordinators() {
    //     global $PAGE;
    //     $context = context_coursecat::instance($this->page->id);
    //     $fields = 'u.id, '.get_all_user_name_fields(true, 'u');
    //     return get_users_by_capability($context, 'moodle/category:manage', $fields, 'lastname ASC, firstname ASC');
    // }
}
