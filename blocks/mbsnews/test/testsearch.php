<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(__DIR__ . '/../../../config.php');

$params = array();
$params['contextlevel'] = optional_param('contextlevel', 40, PARAM_INT);
$params['roleid'] = optional_param('roleid', 2, PARAM_INT);
$params['instanceidsselected'] = optional_param('instanceidsselected', '', PARAM_TEXT);
print_r(\block_mbsnews\local\newshelper::search_recipients($params));
