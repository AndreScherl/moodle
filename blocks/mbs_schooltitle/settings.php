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
 *
 * @package   block_mbs_schooltitle
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('settings', get_string('settings', 'block_mbs_schooltitle'), ''));

    $settings->add(new admin_setting_configtext('block_mbs_schooltitle/imgwidth', get_string('imgwidth', 'block_mbs_schooltitle'),
                    get_string('imgwidthexpl', 'block_mbs_schooltitle'), 400, PARAM_INT));

    $settings->add(new admin_setting_configtext('block_mbs_schooltitle/imgheight', get_string('imgheight', 'block_mbs_schooltitle'),
                    get_string('imgheightexpl', 'block_mbs_schooltitle'), 100, PARAM_INT));
}