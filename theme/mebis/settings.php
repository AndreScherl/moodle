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

    // mebis contrast theme
    $choices = array();
    $themenames = array_keys(get_plugin_list('theme'));
    $choices[0] = get_string('select');
    foreach ($themenames as $themename) {
        $theme = theme_config::load($themename);
        $choices[$themename] = $theme->name;
    }
    $name = 'theme_mebis/contrast_theme';
    $title = get_string('contrasttheme', 'theme_mebis');
    $description = get_string('contrasttheme-descr', 'theme_mebis');
    $default = 'mebiscontrast';
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $settings->add($setting);

    // Footer Links
    $name = 'theme_mebis/footer_links';
    $title = get_string('footer-links', 'theme_mebis');
    $description = get_string('footer-links-descr', 'theme_mebis');
    $default = 'Titel|URL';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $settings->add($setting);
    
    // Footer Link - Newsletter
    $name = 'theme_mebis/footer_url_newsletter';
    $title = get_string('footer-url-newsletter','theme_mebis');
    $description = get_string('footer-url-newsletter-descr', 'theme_mebis');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW, 255);
    $settings->add($setting);
    
    // Footer Link - Ueber mebis
    $name = 'theme_mebis/footer_url_about';
    $title = get_string('footer-url-about','theme_mebis');
    $description = get_string('footer-url-about-descr', 'theme_mebis');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW, 255);
    $settings->add($setting);
    
    // Footer Link - Kontakt
    $name = 'theme_mebis/footer_url_contact';
    $title = get_string('footer-url-contact','theme_mebis');
    $description = get_string('footer-url-contact-descr', 'theme_mebis');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW, 255);
    $settings->add($setting);
}
