<?php
/**
 * This file may not be redistributed in whole or significant part.
 * Content of this file is Protected by International Copyright Laws.
 *
 * ~~~~~~~~~ This Plugin IS NOT FREE SOFTWARE ~~~~~~~~~~~~~~~~
 * 
 * @package   local_dlb
 * @copyright 2013 Andreas Wagner. All Rights reserved.
 */
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    $settings = new admin_settingpage('local_dlb',
                    get_string('pluginname', 'local_dlb'));

    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext('local_dlb_home', 
            get_string('local_dlb_home', 'local_dlb'),
            get_string('local_dlb_home_expl', 'local_dlb'),
            get_string('local_dlb_home_default', 'local_dlb'), PARAM_TEXT));

            

    $settings->add(
            new admin_setting_configtextarea('local_dlb_mebis_sites',
                    get_string('local_dlb_mebis_sites', 'local_dlb'),
                    get_string('local_dlb_mebis_sites_expl', 'local_dlb'),
                    get_string('local_dlb_mebis_sites_default', 'local_dlb'),PARAM_RAW, '80', '8'));
}