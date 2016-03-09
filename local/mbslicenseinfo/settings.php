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
 * mbslicenseinfo local caps.
 *
 * @package    mbslicenseinfo
 * @author     Andre Scherl <andre.scherl@isb.bayern.de>
 * @copyright  2015 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    $settings = new admin_settingpage('local_mbslicense', get_string('pluginname', 'local_mbslicenseinfo'));

    $ADMIN->add('localplugins', $settings);

    $choices = \local_mbslicenseinfo\local\mbslicenseinfo::get_grouped_mimetypes_menu();

    $settings->add(new admin_setting_configmulticheckbox(
            'local_mbslicenseinfo/mimewhitelist',
            new lang_string('mimewhitelist', 'local_mbslicenseinfo'),
            new lang_string('mimewhitelistdesc', 'local_mbslicenseinfo'),
            '', $choices));


    $settings->add(new admin_setting_configtext('local_mbslicenseinfo/filesperpage',
            get_string('filesperpage', 'local_mbslicenseinfo'),
            get_string('filesperpage_expl', 'local_mbslicenseinfo'), 10, PARAM_INT));
}
