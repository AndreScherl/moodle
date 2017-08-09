<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtext('filter_bavarikon/bavarikonurl',
                   get_string('bavarikonurl','filter_bavarikon'),
                   get_string('bavarikonurldesc','filter_bavarikon'), 'bavarikon.de'));
}
