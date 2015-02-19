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

    // Login Url setting
    $name = 'theme_mebis/url_login';
    $title = get_string('url-login','theme_mebis');
    $description = get_string('url-login-descr', 'theme_mebis');
    $default = new moodle_url('/local/dlb/login.php');
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW, 255);
    $settings->add($setting);

    // Logout Url setting
    $name = 'theme_mebis/url_logout';
    $title = get_string('url-logout','theme_mebis');
    $description = get_string('url-logout-descr', 'theme_mebis');
    $default = new moodle_url('/login/logout.php', array('sesskey' => sesskey(), 'alt' => 'logout'));
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW, 255);
    $settings->add($setting);


    // Footer Links
    $name = 'theme_mebis/footer_links';
    $title = get_string('footer-links', 'theme_mebis');
    $description = get_string('footer-links-descr', 'theme_mebis');
    $default = 'Titel|URL';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $settings->add($setting);

}
