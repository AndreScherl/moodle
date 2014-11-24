<?php

/**
 * Theme Mebis settings file.
 *
 * @package theme_mebis
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Support Url setting
    $name = 'theme_mebis/url_support';
    $title = get_string('url-support','theme_mebis');
    $description = get_string('url-support-descr', 'theme_mebis');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW, 255);
    $settings->add($setting);

    // Preferences Url setting
    $name = 'theme_mebis/url_preferences';
    $title = get_string('url-preferences','theme_mebis');
    $description = get_string('url-preferences-descr', 'theme_mebis');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW, 255);
    $settings->add($setting);

    // Personal Preferences Url setting
    $name = 'theme_mebis/url_preferences_personal';
    $title = get_string('url-preferences-personal','theme_mebis');
    $description = get_string('url-preferences-personal-descr', 'theme_mebis');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW, 255);
    $settings->add($setting);
}
