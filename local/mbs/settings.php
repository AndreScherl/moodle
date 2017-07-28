<?php
/**
 * This file may not be redistributed in whole or significant part.
 * Content of this file is Protected by International Copyright Laws.
 *
 * ~~~~~~~~~ This Plugin IS NOT FREE SOFTWARE ~~~~~~~~~~~~~~~~
 * 
 * @package    local_mbs
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
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
