<?php
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    global $DB;

    $choices = array();

    $roles = get_roles_with_capability('moodle/course:create');
    $assignroles = get_roles_with_capability('moodle/role:assign');
    $managerroles = get_roles_with_capability('moodle/category:manage');
    
    //securitycheck only lower roles
    $roles = array_diff_key($roles, $assignroles, $managerroles);
    
    $choices[0] = get_string('no_choice', 'block_meinesuche');

    foreach($roles as $role) {
        $choices[$role->id] = $role->name;
    }

    $settings->add(new admin_setting_configselect('ms_coursecreatorrole', get_string('ms_coursecreatorrole', 'block_meinesuche'),
            get_string('ms_coursecreatorrole_expl', 'block_meinesuche'), 0, $choices));

    
}
