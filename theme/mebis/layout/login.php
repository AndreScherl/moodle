<?php

$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);

$knownregionpre = $PAGE->blocks->is_known_region('side-pre');
$knownregionpost = $PAGE->blocks->is_known_region('side-post');

$regions = theme_mebis_bootstrap_grid($hassidepre, $hassidepost);
$PAGE->set_popup_notification_allowed(false);
if ($knownregionpre || $knownregionpost) {
    theme_bootstrap_initialise_zoom($PAGE);
}
$setzoom = theme_bootstrap_get_zoom();

echo $OUTPUT->doctype()
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimal-ui">
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <link rel="apple-touch-icon" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-57x57.png','mebis');?>">
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-72x72.png','mebis');?>">
    <link rel="apple-touch-icon" sizes="114x114" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-114x114.png','mebis');?>">

    <?php echo $OUTPUT->standard_head_html(); ?>

    <link rel="stylesheet" href="<?php echo new moodle_url("/theme/mebis/style/mebis.css");?>" data-mode="default">
    <?php $PAGE->requires->js( new moodle_url("/theme/mebis/vendor/modernizr-2.6.2-respond-1.1.0.min.js")); ?>
</head>

<body <?php echo $OUTPUT->body_attributes($setzoom); ?>>
<?php echo $OUTPUT->standard_top_of_body_html() ?>

<nav role="navigation" class="navbar navbar-default">
    <div class="container-fluid">
    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#moodle-navbar">
            <span class="sr-only"><?php echo get_string('toggle-navigation', 'theme_mebis');?></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="<?php echo $CFG->wwwroot;?>"><?php echo $SITE->shortname; ?></a>
    </div>

    <div id="moodle-navbar" class="navbar-collapse collapse">
        <?php echo $OUTPUT->custom_menu(); ?>
        <?php echo $OUTPUT->user_menu(); ?>
        <ul class="nav pull-right">
            <li><?php echo $OUTPUT->page_heading_menu(); ?></li>
        </ul>
    </div>
    </div>
</nav>
<header class="moodleheader">
    <div class="container-fluid">
    <a href="<?php echo $CFG->wwwroot ?>" class="logo"></a>
    <?php echo $OUTPUT->page_heading(); ?>
    </div>
</header>

<div id="page" class="container-fluid">
    <header id="page-header" class="clearfix">
        <div id="page-navbar" class="clearfix">
            <nav class="breadcrumb-nav" role="navigation" aria-label="breadcrumb"><?php echo $OUTPUT->navbar(); ?></nav>
            <div class="breadcrumb-button"><?php echo $OUTPUT->page_heading_button(); ?></div>
            <?php if ($knownregionpre || $knownregionpost) { ?>
                <div class="breadcrumb-button"> <?php echo $OUTPUT->content_zoom(); ?></div>
            <?php } ?>
        </div>

        <div id="course-header">
            <?php echo $OUTPUT->course_header(); ?>
        </div>
    </header>

    <div id="page-content" class="row">
        <div id="region-main" class="<?php echo $regions['content']; ?>">
            <?php
            echo $OUTPUT->course_content_header();

            echo $OUTPUT->main_content();
            echo $OUTPUT->course_content_footer();
            ?>
        </div>
    </div>

    <?php
        $PAGE->requires->js( new moodle_url("/theme/mebis/vendor/jquery-1.11.0.min.js"));
        $PAGE->requires->js( new moodle_url("/theme/mebis/javascripts/vendor.min.js"));
        $PAGE->requires->js( new moodle_url("/theme/mebis/javascripts/mebis.js"));
        $PAGE->requires->js( new moodle_url("/theme/mebis/javascripts/mebis.learning-platform.js"));

        echo $OUTPUT->standard_footer_html();
        echo $OUTPUT->standard_end_of_body_html();
    ?>
</div>
</body>
</html>
