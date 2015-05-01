<?php

$knownregionsidepre = $PAGE->blocks->is_known_region('side-pre');
$knownregiontop = $PAGE->blocks->is_known_region('top');
$knownregionsidepost = $PAGE->blocks->is_known_region('side-post');

$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$hastop = $PAGE->blocks->region_has_content('top', $OUTPUT);
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);

//$regions = theme_mebis_bootstrap_grid($hasapps, null);
if ($hassidepre) {
    theme_bootstrap_initialise_zoom($PAGE);
}

// define additional css classes that should be set on the body element. text-center is needed to center the main
// content block
$bodycls = theme_bootstrap_get_zoom();

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
        <script src="<?php echo new moodle_url("/theme/mebis/vendor/modernizr-2.6.2-respond-1.1.0.min.js"); ?>"></script>
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
            <div class="container homepage-container">

                <!-- Breadcrums -->
                <?php echo $OUTPUT->main_breadcrumbs() ?>

                <?php

                if ($knownregiontop) {
                    //echo $OUTPUT->mebis_blocks('top');
                    echo $OUTPUT->blocks('top');
                }
                echo $OUTPUT->raw_block('mbsnewcourse');
                // echo $OUTPUT->course_content_header();
                echo $OUTPUT->main_content();
                // echo $OUTPUT->course_content_footer();
                ?>

                <?php if ($hassidepre || $hassidepost) { ?>
                    <div class="row">

                        <div class="col-lg-12 col-sm-12 margin-bottom-small">
                            <h1><?php echo get_string('my-apps', 'theme_mebis');?></h1>
                        </div>

                    </div>

                    <?php
                        $displayregion = $this->page->apply_theme_region_manipulations('side-pre');
                        $classes = array();
                        $classes[] = 'block-regions';
                        $attributes = array(
                            'id' => 'block-region-' . preg_replace('#[^a-zA-Z0-9_\-]+#', '-', $displayregion),
                            'data-blockregion' => $displayregion,
                            'data-droptarget' => '1'
                        );
                        echo html_writer::start_tag('aside', array('class' => 'row'));

                        if ($knownregionsidepre) {
                            echo $OUTPUT->blocks('side-pre', array('class' => join(' ', $classes)), 'div');
                        }
                        
                        $displayregion = $this->page->apply_theme_region_manipulations('side-post');
                        $classes = array();
                        $classes[] = 'block-regions';
                        $attributes = array(
                            'id' => 'block-region-' . preg_replace('#[^a-zA-Z0-9_\-]+#', '-', $displayregion),
                            'data-blockregion' => $displayregion,
                            'data-droptarget' => '1'
                        );
                        if ($knownregionsidepost) {
                            echo $OUTPUT->blocks('side-post', array('class' => join(' ', $classes)), 'div');
                        }
                        echo html_writer::end_tag('aside');
                    ?>
                <?php } ?>

            </div>

            <div id="root-footer"></div>

            <!-- CONTENT [end] -->
            <?php 
            echo $OUTPUT->mebis_footer();
            ?>

        </div>

        <!-- HOMEPAGE-WRAPPER [end] -->
        <?php echo $OUTPUT->main_footer(); ?>

        <?php echo $OUTPUT->page_action_navigation();?>

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
