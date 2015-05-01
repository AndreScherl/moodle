<?php
$knownregionpre = $PAGE->blocks->is_known_region('side-pre');
$knownregionpost = $PAGE->blocks->is_known_region('side-post');
$knownregionadminnavi = $PAGE->blocks->is_known_region('admin-navi');

$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);
$hastop = $PAGE->blocks->region_has_content('top', $OUTPUT);

$regions = theme_mebis_bootstrap_grid($hassidepre, $hassidepost);
if ($knownregionpre || $knownregionpost) {
    theme_bootstrap_initialise_zoom($PAGE);
}

// define additional css classes that should be set on the body element. text-center is needed to center the main
// content block
$bodycls = theme_bootstrap_get_zoom();
$htmlattrib = $OUTPUT->htmlattributes() . ' class="admin-layout"';
echo $OUTPUT->doctype()
?>
<html <?php echo $htmlattrib; ?>>
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

    <body <?php echo $OUTPUT->body_attributes($bodycls); ?>>
    <?php echo $OUTPUT->standard_top_of_body_html() ?>

        <!-- HOMEPAGE-WRAPPER [start] -->
        <div class="me-wrapper wrapper-learning-platform" role="main">

            <!-- Top-Navigation [start] -->
            <?php echo $OUTPUT->main_navbar(); ?>

            <!-- Top-Navigation [end] -->

            <!-- Side-Navigation [start] -->
            <?php echo $OUTPUT->main_sidebar(); ?>
            <!-- Side-Navigation [end] -->

            <!-- PAGE HEADER [start] -->
            <?php echo $OUTPUT->main_header(); ?>
            <!-- PAGE HEADER [end] -->

            <!-- CONTENT -->

            <div class="container">
                <!-- Breadcrums -->
                <?php echo $OUTPUT->main_breadcrumbs() ?>
            </div>

            <div class="container homepage-container admin-container">

                <div class="row">

                    <div class="col-md-12 hidden-lg">
                        <div class="admin-navigation">
                            <?php echo $OUTPUT->render_adminnav_selectbox();?>
                        </div>
                    </div>

                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <div class="admin-content">
                            <?php
                            // echo $OUTPUT->course_content_header();
                                echo $OUTPUT->main_content();
                            // echo $OUTPUT->course_content_footer();
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="root-footer"></div>
            <!-- CONTENT [end] -->

        </div>
        <!-- HOMEPAGE-WRAPPER [end] -->
        <?php echo $OUTPUT->main_footer(); ?>

        <a href="#top" id="me-back-top">
            <i class="fa fa-chevron-up"></i>
        </a>

        <?php
            $PAGE->requires->js( new moodle_url("/theme/mebis/vendor/jquery-1.11.0.min.js"));
            $PAGE->requires->js( new moodle_url("/theme/mebis/javascripts/vendor.min.js"));
            $PAGE->requires->js( new moodle_url("/theme/mebis/javascripts/mebis.js"));
            $PAGE->requires->js( new moodle_url("/theme/mebis/javascripts/mebis.learning-platform.js"));

            echo $OUTPUT->standard_footer_html();
            echo $OUTPUT->standard_end_of_body_html();
        ?>
    </body>
</html>
