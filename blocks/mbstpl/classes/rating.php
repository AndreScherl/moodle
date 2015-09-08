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
* @package block_mbstpl
* @copyright 2015 Yair Spielmann, Synergy Learning for ALP
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace block_mbstpl;

defined('MOODLE_INTERNAL') || die();

class rating {

    /**
     * Save a rating and update the tempate.
     * @param \block_mbstpl\dataobj\template $template
     * @param \block_mbstpl\dataobj\starrating $starrating
     */
    public static function save_userrating($template, $starrating) {
        global $DB;

        $starrating->insertorupdate();
        $average = $DB->get_field_sql("SELECT AVG(rating) FROM {block_mbstpl_starrating} WHERE templateid = ?", array($template->id));
        $template->rating = $average;
        $template->update_notouch();
    }
}