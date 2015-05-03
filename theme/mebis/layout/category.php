<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A layout for the category pages in  mebis theme
 *
 * @package   theme_mebis
 * @copyright 2015 ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$knownregionsidepre = $PAGE->blocks->is_known_region('side-pre');
$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);

if ($hassidepre) {
    theme_bootstrap_initialise_zoom($PAGE);
}

// Define additional css classes that should be set on the body element. 
// text-center is needed to center the main content block
$bodycls = theme_bootstrap_get_zoom();

echo $OUTPUT->doctype()
?>

<html <?php echo $OUTPUT->htmlattributes(); ?>>
    <head>
        <title><?php echo $OUTPUT->page_title(); ?></title>

        <meta name="viewport" content="width=device-width, initial-scale=1.0, minimal-ui">
        <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
        <link rel="apple-touch-icon" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-57x57.png', 'mebis'); ?>">
        <link rel="apple-touch-icon" sizes="72x72" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-72x72.png', 'mebis'); ?>">
        <link rel="apple-touch-icon" sizes="114x114" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-114x114.png', 'mebis'); ?>">

        <?php echo $OUTPUT->standard_head_html(); ?>

        <link rel="stylesheet" href="<?php echo new moodle_url("/theme/mebis/style/mebis.css"); ?>" data-mode="default">
        <script src="<?php echo new moodle_url("/theme/mebis/vendor/modernizr-2.6.2-respond-1.1.0.min.js"); ?>"></script>
    </head>

    <body <?php echo $OUTPUT->body_attributes($bodycls); ?>>
        
        <?php echo $OUTPUT->standard_top_of_body_html() ?>

        <!-- HOMEPAGE-WRAPPER [start] -->
        <div class="me-wrapper wrapper-learning-platform" role="main">

            <?php
            echo $OUTPUT->main_navbar();
            echo $OUTPUT->main_sidebar();
            echo $OUTPUT->main_header();
            ?>

            <div class="container homepage-container">

                <?php
                echo $OUTPUT->main_breadcrumbs();

                echo $OUTPUT->raw_block('mbscoordinators');
                echo $OUTPUT->raw_block('mbsnewcourse');
                
                echo $OUTPUT->course_content_header();
                echo $OUTPUT->main_content();
                echo $OUTPUT->course_content_footer();
                ?>

                <?php if ($hassidepre) : ?>
                
                    <div class="row">

                        <div class="col-lg-12 col-sm-12 margin-bottom-small">
                            <h1><?php echo get_string('my-apps', 'theme_mebis'); ?></h1>
                        </div>

                    </div>

                    <?php
                    $displayregion = $this->page->apply_theme_region_manipulations('side-pre');
                    $classes = array();
                    $classes[] = 'row block-regions';
                    $attributes = array(
                        'id' => 'block-region-' . preg_replace('#[^a-zA-Z0-9_\-]+#', '-', $displayregion),
                        'data-blockregion' => $displayregion,
                        'data-droptarget' => '1'
                    );
                    echo html_writer::start_tag('aside', $attributes);
                    echo html_writer::start_tag('div', array('class' => join(' ', $classes)));

                    if ($knownregionsidepre) {
                        echo $OUTPUT->mebis_blocks('side-pre');
                    }

                    echo html_writer::end_div();
                    echo html_writer::end_tag('aside');
                    
                    // awag: Alternative mit standard Methode?
                    // echo html_writer::tag('div', $OUTPUT->blocks('side-pre'), array('class' => 'row'));
                    ?>
                <?php endif; ?>
            </div>

            <div id="root-footer"></div>
            <?php 
            echo $OUTPUT->mebis_footer();
            ?>
        </div>

        <?php
        echo $OUTPUT->main_footer();
        echo $OUTPUT->page_action_navigation();

        $PAGE->requires->js(new moodle_url("/theme/mebis/vendor/jquery-1.11.0.min.js"));
        $PAGE->requires->js(new moodle_url("/theme/mebis/javascripts/vendor.min.js"));
        $PAGE->requires->js(new moodle_url("/theme/mebis/javascripts/mebis.js"));
        $PAGE->requires->js( new moodle_url("/theme/mebis/javascripts/mebis.learning-platform.js"));
        ?>
        
        <div class="container"> 
            <footer id="page-footer">
                <div id="course-footer"><?php echo $OUTPUT->course_footer(); ?></div>
                <?php
                echo $OUTPUT->standard_footer_html();
                ?>
            </footer>
        </div>

        <?php
        echo $OUTPUT->standard_end_of_body_html();
        ?>

    </body>
</html>