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

/** settings for block_mbs_search
 *
 * @package   block_mbs_search
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('settings', get_string('settings', 'block_mbs_search'), ''));

    $settings->add(new admin_setting_configtext('block_mbs_search/moreresultscount',
                    get_string('moreresultscount', 'block_mbs_search'),
                    get_string('moreresultscountexpl', 'block_mbs_search'), 9, PARAM_INT));

    $settings->add(new admin_setting_configtext('block_mbs_search/lookupcount',
                    get_string('lookupcount', 'block_mbs_search'),
                    get_string('lookupcountexpl', 'block_mbs_search'), 5, PARAM_INT));
}