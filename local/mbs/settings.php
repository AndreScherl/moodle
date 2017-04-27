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
 * Unit tests for mbs
 *
 * @package   local_mbs
 * @copyright 2015 Franziska Hübler, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    $settings = new admin_settingpage('local_mbs',
                    get_string('pluginname', 'local_mbs'));

    $ADMIN->add('localplugins', $settings);

    $settings->add(
            new admin_setting_configtextarea('local_mbs_mebis_sites',
                    get_string('local_mbs_mebis_sites', 'local_mbs'),
                    get_string('local_mbs_mebis_sites_expl', 'local_mbs'),
                    get_string('local_mbs_mebis_sites_default', 'local_mbs'),PARAM_RAW, '80', '8'));
}
