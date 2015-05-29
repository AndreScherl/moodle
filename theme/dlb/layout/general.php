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

$hasheading = ($PAGE->heading);
$hasnavbar = (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar());

// Add block mbschangeplatform to block region side-pre for betausers
if (isset($USER->isBetauser) && $USER->isBetauser){
    if (!$PAGE->blocks->is_block_present('mbschangeplatform')) {
        if ($knownregionsidepre) {
            $PAGE->blocks->add_block('mbschangeplatform', 'side-pre', 1000, false);
        }
    }
}

$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);
$showsidepre = $hassidepre && !$PAGE->blocks->region_completely_docked('side-pre', $OUTPUT);
$showsidepost = $hassidepost && !$PAGE->blocks->region_completely_docked('side-post', $OUTPUT);

$custommenu = $OUTPUT->toolbar_settings_menu();
$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) && !empty($custommenu));

$bodyclasses = array();
if ($showsidepre && !$showsidepost) {
    $bodyclasses[] = 'side-pre-only';
} else if ($showsidepost && !$showsidepre) {
    $bodyclasses[] = 'side-post-only';
} else if (!$showsidepost && !$showsidepre) {
    $bodyclasses[] = 'content-only';
}
if ($hascustommenu) {
    $bodyclasses[] = 'has_custom_menu';
}

if (!isloggedin() or isguestuser()) {
    $bodyclasses[] = 'notloggedin';
}

if (!is_siteadmin($USER)) {
    $bodyclasses[] = 'noadmin';
}

echo $OUTPUT->doctype()
?>
<html <?php echo $OUTPUT->htmlattributes() ?>>
    <head>
        <title><?php echo $PAGE->title ?></title>
        <link rel="shortcut icon" href="<?php echo $OUTPUT->pix_url('favicon', 'theme') ?>" />
        <meta name="description" content="<?php p(strip_tags(format_text($SITE->summary, FORMAT_HTML))) ?>" />
        <?php echo $OUTPUT->standard_head_html() ?>
    </head>
    <body id="<?php p($PAGE->bodyid) ?>" class="<?php p($PAGE->bodyclasses . ' ' . join(' ', $bodyclasses)) ?>">

        <div id="toolbar-wrapper">
            <div id="toolbar">

                <?php echo $OUTPUT->toolbar_content(); ?>
                <?php echo $OUTPUT->support_button(); ?>
                <?php echo $OUTPUT->toolbar_loginbutton(); ?>
                <?php if ($hascustommenu) { ?>
                    <div id="custommenu"><?php echo $custommenu; ?></div>
                <?php } ?>
                <?php echo $OUTPUT->login_info(false); ?>

                <div class="headermenu">
                    <?php
                    echo $PAGE->headingmenu;
                    ?>
                </div>
                <div style="clear:both"></div>
            </div>
        </div>

        <?php echo $OUTPUT->generalheader(); ?>

        <div id="page-wrapper">
            <?php echo $OUTPUT->standard_top_of_body_html() ?>

            <!---awag mebis-header--->
            <!---awag --->
            <div id="page">
                <div id="page-intend">
                    <!-- END OF HEADER -->
                    <div id="page-content-bg-top">
                        <div id="page-content-bg-top-left"></div>
                        <div id="page-content-bg-top-right"></div>
                        <div style="clear:both"></div>
                    </div>

                    <div id="page-content-bg">
                        <div id="page-header"></div>
                        <div style="clear:both"></div>
                        <div id="page-content">
                            <div id="page-bg-content-left">
                                <div id="region-main-box">
                                    <div id="region-post-box">

                                        <div id="region-main-wrap">
                                            <div id="region-main">
                                                <?php if ($hasnavbar) { ?>
                                                    <div class="navbar clearfix">
                                                        <div class="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>
                                                        <div class="navbutton"> <?php echo $PAGE->button; ?></div>
                                                    </div>
                                                <?php } ?>
                                                <div class="region-content">
                                                    <?php if ($hasheading) { ?>
                                                        <h1 class="page-heading"><?php echo $PAGE->heading; ?></h1>
                                                    <?php } ?>
                                                    <?php echo $OUTPUT->main_content() ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($hassidepre) { ?>
                                            <div id="region-pre" class="block-region">
                                                <div class="region-content">

                                                    <?php echo $OUTPUT->blocks_for_region('side-pre') ?>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <?php if ($hassidepost) { ?>
                                            <div id="region-post" class="block-region">
                                                <div class="region-content">
                                                    <?php echo $OUTPUT->blocks_for_region('side-post') ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <div style="clear:both"></div>
                                    </div>
                                    <div style="clear:both"></div>
                                </div>
                                <div style="clear:both"></div>
                            </div>
                            <div style="clear:both"></div>
                        </div>
                        <div style="clear:both"></div>
                    </div>
                    <div style="clear:both"></div>
                    <div id="page-content-bg-bottom">
                        <div id="page-content-bg-bottom-left"></div>
                        <div id="page-content-bg-bottom-right"></div>
                    </div>
                    <div style="clear:both"></div>

                    <!-- START OF FOOTER -->
                    <div id="page-footer">
                        <p class="helplink">
                            <?php echo page_doc_link(get_string('moodledocslink')) ?>
                        </p>

                        <?php
                        echo $OUTPUT->home_link();
                        echo $OUTPUT->standard_footer_html();
                        ?>
                    </div>
                </div>
                <?php 
                    
                    global $SESSION;
                    if (!empty($CFG->debugfixsortorder)) {
                        if (!empty($SESSION->profilefixsortorder)) {
                            echo $SESSION->profilefixsortorder;
                            unset($SESSION->profilefixsortorder);
                        }
                    }
                    echo $OUTPUT->standard_end_of_body_html();
                ?>
            </div>
        </div>
    </body>
</html>