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
 * @package   block_mbsnews
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $authplugins = core_component::get_plugin_list('auth');

    $choices = array();
    foreach ($authplugins as $authtype => $plugin) {
        $choices[$authtype] = get_string('pluginname', 'auth_'.$authtype);
    }

    $settings->add(new admin_setting_configmulticheckbox('block_mbsnews/includeauth',
                    new lang_string('includeauth', 'block_mbsnews'),
                    new lang_string('includeauthdesc', 'block_mbsnews'), array('shibboleth'), $choices));

    $settings->add(new admin_setting_configtext('block_mbsnews/maxmessages',
             new lang_string('maxmessages', 'block_mbsnews'),
             new lang_string('maxmessagesdesc', 'block_mbsnews'), 200, PARAM_INT));

    $settings->add(new admin_setting_configtext('block_mbsnews/recipientdisplaylimit',
             new lang_string('recipientdisplaylimit', 'block_mbsnews'),
             new lang_string('recipientdisplaylimitdesc', 'block_mbsnews'), 10, PARAM_INT));

}