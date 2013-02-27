<?php

/*
 #########################################################################
 #                       DLB-Bayern
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 #
 # Copyright 2012 Andreas Wagner. All Rights Reserved.
 # This file may not be redistributed in whole or significant part.
 # Content of this file is Protected By International Copyright Laws.
 #
 # ~~~~~~~~~~~~~~~~~~ THIS CODE IS NOT FREE SOFTWARE ~~~~~~~~~~~~~~~~~~~~
 #
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 # @author Andreas Wagner, DLB	andreas.wagner@alp.dillingen.de
 #########################################################################
*/

defined('MOODLE_INTERNAL') || die;



if ($ADMIN->fulltree) {

    global $DB;

    $settings->add(new admin_setting_heading('lowbarrierthemeheading', get_string('lowbarrierthemeheading', 'block_dlb'),''));

    $choices = array();
    $themenames = array_keys(get_plugin_list('theme'));

    $choices[0] = get_string('select');
    foreach ($themenames as $themename) {
        $theme = theme_config::load($themename);
        $choices[$themename] = $theme->name;
    }

    $settings->add(new admin_setting_configselect('block_dlb_lowbarriertheme', get_string('lowbarriertheme', 'block_dlb'),
            get_string('lowbarrierthemeexpl', 'block_dlb'), 'dlbarr', $choices));

    $settings->add(new admin_setting_configtext('block_dlb_addcss', get_string('addcss', 'block_dlb'),
            get_string('addcssexpl', 'block_dlb'), 'css0,css1,css2,css3', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('block_dlb_addacss', get_string('addacss', 'block_dlb'),
            get_string('addacssexpl', 'block_dlb'), 'css0,css1,css2,css3', PARAM_TEXT));

    $settings->add(new admin_setting_configcheckbox('block_dlb_toolbaronfrontpage', get_string('toolbaronfrontpage', 'block_dlb'),
            get_string('toolbaronfrontpageexpl', 'block_dlb'), 1));

    $settings->add(new admin_setting_heading('supporturlheading', get_string('supporturlheading', 'block_dlb'),''));

    $settings->add(new admin_setting_configtext('block_dlb_supporturl', get_string('supporurl', 'block_dlb'),
            get_string('supporturlexpl', 'block_dlb'), '', PARAM_TEXT));

    $choices = array();

    $roles = $DB->get_records('role');

    foreach($roles as $role) {
        $choices[$role->id] = $role->name;
    }

    $settings->add(new admin_setting_configmulticheckbox('block_dlb_rolestosupport', get_string('rolestosupport', 'block_dlb'),
            get_string('rolestosupportexpl', 'block_dlb'), array(3 => 1), $choices));

    $settings->add(new admin_setting_heading('rolesformycategoriesheading', get_string('rolesformycategoriesheading', 'block_dlb'),''));

    $settings->add(new admin_setting_configmulticheckbox('block_dlb_rolesformycategories', get_string('rolesformycategories', 'block_dlb'),
            get_string('rolesformycategoriesexpl', 'block_dlb'), array(3 => 1, 4 => 1), $choices));

    $settings->add(new admin_setting_heading('contentfooterleftheading', get_string('contentfooterleftheading', 'block_dlb'),''));

    $settings->add(new admin_setting_configtextarea('block_dlb_contentfooterleft',
            get_string('contentfooterleft', 'block_dlb'),
            get_string('contentfooterleftexpl', 'block_dlb'),
            '<a href="https://lernplattform.mebis.bayern.de/mod/page/view.php?id=588" target="_blank">
            Impressum
        </a>
        <a href="https://lernplattform.mebis.bayern.de/mod/page/view.php?id=591" target="_blank">
            Datenschutz
        </a>
        <a href="https://lernplattform.mebis.bayern.de/mod/page/view.php?id=585" target="_blank">
            Nutzungsbedingungen
        </a>
        <a href="https://lernplattform.mebis.bayern.de/mod/page/view.php?id=594" target="_blank">
            Kontakt
        </a>
        <a href="https://lernplattform.mebis.bayern.de/mod/page/view.php?id=597" target="_blank">
            Über Mebis
        </a>', PARAM_RAW, '120', '16'));
}
?>
