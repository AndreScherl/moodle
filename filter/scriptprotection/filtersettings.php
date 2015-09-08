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

    $roles = $DB->get_records('role');
    $rolenames = role_fix_names($roles);
    
    $choices = array();
    foreach($rolenames as $role) {
        if ($role->id != 1) $choices[$role->id] = $role->localname;
    }

    $settings->add(new admin_setting_configmulticheckbox('filter_sp_rolestosupport', get_string('sp_rolestosupport', 'filter_scriptprotection'),
            get_string('sp_rolestosupportexpl', 'filter_scriptprotection'), array(9 => 1), $choices));
}
