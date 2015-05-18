<?php

/**
 * Theme mebis lib.
 *
 * @package theme_mebis
 */

function theme_mebis_page_init(moodle_page $page) {
    $page->requires->jquery();
}

function theme_mebis_process_css($css, $theme)
{
    // run compass compile during the css processing phase...
    $curDir = __DIR__;
    // exec("cd ${curDir} && compass compile -c compass.rb");

    return $css;
}

function theme_mebis_bootstrap_grid($hassidepre, $hassidepost)
{
    if ($hassidepre && $hassidepost) {
        $regions = array('content' => 'col-sm-12 col-lg-12 col-md-12');
        $regions['pre'] = 'col-sm-12 col-lg-12 col-md-12';
        $regions['post'] = 'col-sm-12 col-lg-12 col-md-12';
    } else if ($hassidepre && !$hassidepost) {
        $regions = array('content' => 'col-sm-9 col-lg-10');
        $regions['pre'] = 'col-sm-12 col-lg-12 col-md-12';
        $regions['post'] = 'emtpy';
    } else if (!$hassidepre && $hassidepost) {
        $regions = array('content' => 'col-sm-9 col-lg-10');
        $regions['pre'] = 'empty';
        $regions['post'] = 'col-sm-12 col-lg-12 col-md-12';
    } else if (!$hassidepre && !$hassidepost) {
        $regions = array('content' => 'col-md-12');
        $regions['pre'] = 'empty';
        $regions['post'] = 'empty';
    }

    if ('rtl' === get_string('thisdirection', 'langconfig')) {
        if ($hassidepre && $hassidepost) {
            $regions['pre'] = 'col-sm-12 col-lg-12 col-md-12 ';
            $regions['post'] = 'col-sm-12 col-lg-12 col-md-12 ';
        } else if ($hassidepre && !$hassidepost) {
            $regions = array('content' => 'col-sm-9 col-sm-push-3 col-lg-10 col-lg-push-2');
            $regions['pre'] = 'col-sm-12 col-lg-12 col-md-12 ';
            $regions['post'] = 'emtpy';
        } else if (!$hassidepre && $hassidepost) {
            $regions = array('content' => 'col-sm-9 col-lg-10');
            $regions['pre'] = 'empty';
            $regions['post'] = 'col-sm-12 col-lg-12 col-md-12 ';
        }
    }
    return $regions;
}

function theme_mebis_get_footer_links()
{
    global $PAGE;
    $links = $PAGE->theme->settings->footer_links;
    $links = explode("\n", $links);

    $footer_links = array();

    foreach($links as $link) {
        $footer_link = explode('|', $link);
        $footer_links[$footer_link[0]] = $footer_link[1];
    }

    return $footer_links;
}