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
 * This page has currently no functionality!
 * 
 * @package   block_mbsschooltitle
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('settings', get_string('settings', 'block_mbsschooltitle'), ''));

    $settings->add(new admin_setting_configtext('block_mbsschooltitle/imgwidth', get_string('imgwidth', 'block_mbsschooltitle'),
                    get_string('imgwidthexpl', 'block_mbsschooltitle'), 400, PARAM_INT));

    $settings->add(new admin_setting_configtext('block_mbsschooltitle/imgheight', get_string('imgheight', 'block_mbsschooltitle'),
                    get_string('imgheightexpl', 'block_mbsschooltitle'), 100, PARAM_INT));
}