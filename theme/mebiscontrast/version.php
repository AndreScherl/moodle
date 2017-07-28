<?php

/**
 * Theme Mebis version file.
 *
 * @package theme_mebis
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2015102800;
$plugin->requires  = 2014051200;
$plugin->release  = 2015102000;
$plugin->maturity  = MATURITY_BETA;
$plugin->component = 'theme_mebiscontrast';
$plugin->dependencies = array(
    'theme_bootstrap'  => 2014051300,
    'theme_mebis' => 2015101300
);
