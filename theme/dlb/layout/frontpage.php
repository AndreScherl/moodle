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

$hasnavbar = (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar());
$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);
$showsidepre = $hassidepre && !$PAGE->blocks->region_completely_docked('side-pre', $OUTPUT);
$showsidepost = $hassidepost && !$PAGE->blocks->region_completely_docked('side-post', $OUTPUT);

$custommenu = $OUTPUT->toolbar_settings_menu();
$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) && !empty($custommenu));

$bodyclasses = array();
//if (true) { //awag: nur side-pre-only
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

if ($CFG->block_dlb_toolbaronfrontpage == 0) {
    $bodyclasses[] = 'no_toolbar';
}

if (!isloggedin() or isguestuser()) {
    $bodyclasses[] = 'notloggedin';
}

echo $OUTPUT->doctype();
?>
<html id="frontpage" <?php echo $OUTPUT->htmlattributes(); ?>>
    
    <head>
        <title><?php echo $PAGE->title ?></title>
        <link rel="shortcut icon" href="<?php echo $OUTPUT->pix_url('favicon', 'theme') ?>" />
        <meta name="description" content="<?php p(strip_tags(format_text($SITE->summary, FORMAT_HTML))) ?>" />
        <?php echo $OUTPUT->standard_head_html(); ?>
    </head>
    
    <body id="<?php p($PAGE->bodyid) ?>" class="<?php p($PAGE->bodyclasses . ' ' . join(' ', $bodyclasses)) ?>">
        <div id="page-wrapper">
            <?php echo $OUTPUT->standard_top_of_body_html() ?>

            <!---awag mebis-header--->
            <div id ="mebis-header">
                <a href="https://mebis.bayern.de/">
                    <div id="menuItemMebis" class="mainMenuMebis"> </div>
                </a>
                <a href="https://mediathek.mebis.bayern.de/">
                    <div id="menuItemMediathek" class="mainMenuMediathek"> </div>
                </a>
                <a href="https://lernplattform.mebis.bayern.de">
                    <div id="menuItemMoodle" class="mainMenuLMSActive"> </div>
                </a>
                <a href="https://mediathek.mebis.bayern.de/archiv.php">
                    <div id="menuItemArch" class="mainMenuArchive"> </div>
                </a>
            </div>
            <div id="toolbar-wrapper">
                <div id="toolbar">

                    <div class="headermenu">
                        <?php
                        echo $OUTPUT->lang_menu();
                        echo $PAGE->headingmenu;
                        ?>
                    </div>
                    <?php echo $OUTPUT->toolbar_content(); ?>
                    <?php echo $OUTPUT->toolbar_loginbutton(); ?>
                    <?php echo $OUTPUT->login_info(false); ?>
                    <div>
                        <?php echo $OUTPUT->support_button(); ?>
                    </div>
                </div>
            </div>

            <!---awag --->
            <div id="page">

                <!-- END OF HEADER -->

                <div id="page-content-bg-top" class="clearfix"></div>
                <div id="page-content-bg">
                    <div id="page-content-bg-bottomfade">
                        <div id="page-content-bg-topfade">
                            <div id="page-header" class="clearfix">
                                <?php if ($hascustommenu) { ?>
                                    <div id="custommenu"><?php echo $custommenu; ?></div>
                                <?php } ?>

                            </div>
                            <div style="clear:both"></div>
                            <div id="page-content">
                                <div id="region-main-box">
                                    <div id="region-post-box">
                                        <div id="region-main-wrap">
                                            <div id="region-main">
                                                <div class="region-content">
                                                    <?php if ($hasnavbar) { ?>
                                                        <div class="navbar clearfix">
                                                            <div class="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>
                                                            <div class="navbutton"> <?php echo $PAGE->button; ?></div>
                                                        </div>
                                                        <?php
                                                    }
                                                    echo $OUTPUT->main_content()
                                                    ?>
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

                                    </div>
                                    <div style="clear:both"></div>
                                </div>
                                <div style="clear:both"></div>
                            </div>
                            <?php $OUTPUT->pagecontent_footer(); ?>
                            <div style="clear:both"></div>
                        </div>
                        <div style="clear:both"></div>
                    </div>
                    <div style="clear:both"></div>
                </div>
                <div style="clear:both"></div>
                <div id="page-content-bg-bottom"></div>
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

            <script type="text/javascript">

<?php if (!$PAGE->user_is_editing()) { ?>
        //Variable, um das Dock für die Startseite zu deaktivieren....
        var nodock = "true";
<?php } ?>

    function onCategorySearch(e) {


        var inputelement =  e.target;

        if (inputelement.value.length < 4) {
            document.getElementById('categorysearch-result').innerHTML = "";
            document.getElementById('categorysearch-result').visibility = "hide";
            return;
        }

        var sUrl = "<?php echo $CFG->wwwroot . "/local/categorysearch.php" ?>";
        var callback = {
            success: function(o) {
                document.getElementById('categorysearch-result').innerHTML =  o.responseText;
                document.getElementById('categorysearch-result').visibility = "show";
            },
            failure: function(o) {
                alert("AJAX doesn't work"); //FAILURE
            }
        }

        YAHOO.util.Connect.asyncRequest('POST', sUrl, callback, "searchterm="+inputelement.value);
    }

    function onCategorySearchBlur(e) {
        document.getElementById('coursesearchbox').focus();
        document.getElementById('categorysearch-result').innerHTML = "";
        document.getElementById('categorysearch-result').visibility = "hide";
    }

    YAHOO.util.Event.addListener("categorysearch-input", "keyup", onCategorySearch);
    YAHOO.util.Event.addListener("coursesearchbox", "focus", onCategorySearchBlur);
    YAHOO.util.Event.addListener("categorysearch-input", "focus", onCategorySearch);
    document.getElementById('categorysearch-result').visibility = "hide";
    //YAHOO.util.KeyListener ( attachTo , keyData , handler , event )
    var kpl1 = new YAHOO.util.KeyListener(document.getElementById('categorysearch-input'), { keys:[27] }, { fn:onCategorySearchBlur } );
    kpl1.enable();

            </script>
            <?php echo $OUTPUT->standard_end_of_body_html() ?>
            <div style="clear:both"></div>
        </div>
    </body>
</html>