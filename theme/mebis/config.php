<?php

/**
 * Theme Mebis config file.
 *
 * @package theme_mebis
 */
$THEME->name = 'mebis';
$THEME->parents = array('bootstrap');

$THEME->doctype = 'html5';
// $THEME->sheets = array('mebis');
$THEME->lessfile = 'moodle';
$THEME->parents_exclude_sheets = array('bootstrap' => array('moodle'));
$THEME->lessvariablescallback = 'theme_mebis_less_variables';
$THEME->extralesscallback = 'theme_mebis_extra_less';
$THEME->supportscssoptimisation = false;
$THEME->yuicssmodules = array();
$THEME->enable_dock = false;
$THEME->editor_sheets = array();

$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->csspostprocess = 'theme_mebis_process_css';

$THEME->layouts = array(
    // Most backwards compatible layout without the blocks - this is the layout used by default.
    'base' => array(
        'file' => 'default.php',
        'regions' => array()
    ),
    'mydashboard' => array(
        'file' => 'default.php',
        'regions' => array('side-pre', 'top', 'side-post'),
        'defaultregion' => 'side-pre'
    ),
    'login' => array(
        'file' => 'login.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array('langmenu' => false, 'nonavbar' => true),
    ),
    'course' => array(
        'file' => 'default.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre'
    ),
    'coursecategory' => array(
        'file' => 'category.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    'incourse' => array(
        'file' => 'default.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre'
    ),
    'admin' => array(
        'file' => 'admin.php',
        'regions' => array('admin-navi'),
        'defaultregion' => 'admin-navi'
    ),
    'maintenance' => array(
        'file' => 'maintenance.php',
        'regions' => array(),
        'renderer' => 'theme_mebis_core_renderer'
    )
);