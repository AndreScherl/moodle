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

    $settings->add(new admin_setting_heading('custom_header', get_string('custom_header', 'block_custom_category'),''));

    $settings->add(new admin_setting_configtext('custom_header_imgwidth', get_string('custom_header_imgwidth', 'block_custom_category'),
                       get_string('custom_header_imgwidthexpl', 'block_custom_category'), 463, PARAM_INT));

    $settings->add(new admin_setting_configtext('custom_header_imgheight', get_string('custom_header_imgheight', 'block_custom_category'),
                       get_string('custom_header_imgheightexpl', 'block_custom_category'), 95, PARAM_INT));

    $settings->add(new admin_setting_heading('courselistheading', get_string('courselistheading', 'block_custom_category'),''));

    $settings->add(new admin_setting_configtext('custom_category_coursenamelength', get_string('coursenamelength', 'block_custom_category'),
                       get_string('coursenamelengthexpl', 'block_custom_category'), 35, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('custom_category_usecourselinks', get_string('usecourselinks', 'block_custom_category'),
                       get_string('usecourselinksexpl', 'block_custom_category'), 0));

}
?>
