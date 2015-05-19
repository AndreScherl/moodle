<?php

/**
 * Theme Mebis config file.
 *
 * @package theme_mebis
 */
$THEME->name = 'mebis';
$THEME->parents = array('bootstrap');
$THEME->doctype = 'html5';

// awag: This is commented out from trio, as they decided NOT to use recommende approach to
// include CSS sheets (because they want to include contrast css on the fly?),
// Can be changed, when there is a better way to include contrast theme.
// $THEME->sheets = array('mebis');

// awag: user moodle less file to include fonts?
$THEME->lessfile = 'moodle';

// awag: As this theme doesn't use the recommended approach to include sheets, 
// next line should not be necessary.
$THEME->parents_exclude_sheets = array('bootstrap' => array('moodle'));

// awag: removed unnecessate callbacks
// $THEME->lessvariablescallback = 'theme_mebis_less_variables';
// $THEME->extralesscallback = 'theme_mebis_extra_less';

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
        'regions' => array()
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
    
    'report' => array(
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