<?php

$knownregionsidepre = $PAGE->blocks->is_known_region('side-pre');
$knownregiontop = $PAGE->blocks->is_known_region('top');
$knownregionsidepost = $PAGE->blocks->is_known_region('side-post');

if($knownregiontop) {
    $help_renderer = new theme_mebis_help_renderer($PAGE, 'top');
    $fakeBlock = new block_contents();
    $fakeBlock->content = $help_renderer->helpnote();
    $PAGE->blocks->add_fake_block($fakeBlock, 'top');
}

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
                    echo $OUTPUT->blocks('top');
                }

                // echo $OUTPUT->course_content_header();
                echo $OUTPUT->main_content();
                // echo $OUTPUT->course_content_footer();
                ?>
                <div class="row">
                    <?php
                    if ($hassidepre) {
                        ?>

                        <div class="col-lg-12 col-sm-12">
                            <h1>Meine Apps</h1>
                        </div>

                        <div class="col-lg-12 col-sm-12">
                        <?php
                            $displayregion = $this->page->apply_theme_region_manipulations('side-pre');
                            $classes = array();
                            $classes[] = 'row block-region';
                            $attributes = array(
                                'id' => 'block-region-' . preg_replace('#[^a-zA-Z0-9_\-]+#', '-', $displayregion),
                                'data-blockregion' => $displayregion,
                                'data-droptarget' => '1'
                            );
                            echo html_writer::start_tag('aside', $attributes);
                            echo html_writer::start_tag('div', array('class' => join(' ', $classes)));

                            if ($knownregionsidepre) {
                                echo $OUTPUT->blocks('side-pre');
                            }

                            echo html_writer::end_div();
                            echo html_writer::end_tag('aside');

                    ?>
                    </div>
                    <?php
                    }
                    ?>

                        <?php
                        if ($knownregionsidepost && $hassidepost) {
                        ?>
                        <div class="col-lg-12 col-sm-12">
                            <div class="row">
                                <div class="col-lg-12 col-sm-12">
                                    <h1 class="pull-left">Meine Schulen</h1>
                                </div>
                                <?php

                                    echo $OUTPUT->blocks('side-post');
                                ?>
                            </div>
                        </div>
                        <?php
                        }
                        ?>
                </div>
            </div>

            <div id="root-footer"></div>

            <!-- CONTENT [end] -->
            <?php echo $OUTPUT->main_searchbar(); ?>

            <?php echo $OUTPUT->main_eventfooter(); ?>

        </div>

        <!-- HOMEPAGE-WRAPPER [end] -->
        <?php echo $OUTPUT->main_footer(); ?>

        <?php echo $OUTPUT->page_action_navigation();?>

        <?php
            $PAGE->requires->js( new moodle_url("/theme/mebis/vendor/jquery-1.11.0.min.js"));
            $PAGE->requires->js( new moodle_url("/theme/mebis/javascripts/vendor.min.js"));
            $PAGE->requires->js( new moodle_url("/theme/mebis/javascripts/mebis.js"));
            $PAGE->requires->js( new moodle_url("/theme/mebis/javascripts/mebis.learning-platform.js"));

            echo $OUTPUT->standard_end_of_body_html();
        ?>

    </body>
</html>
