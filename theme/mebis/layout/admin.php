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
 * A layout for the admin pages in mebis theme
 *
 * @package   theme_mebis
 * @copyright 2015 ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Add mbswizzard to page to get its javascript and show the sequence progress bar
$OUTPUT->add_block_mbswizzard_if_needed('admin-navi');
// Check existing regions.
$knownregionadminnavi = $PAGE->blocks->is_known_region('admin-navi');

// Load the javascript modules
$PAGE->requires->js_call_amd('theme_mebis/mbslearningplatform', 'init');

echo $OUTPUT->doctype()
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
    <head>

        <title><?php echo $OUTPUT->page_title(); ?></title>
        <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
        <?php echo $OUTPUT->standard_head_html(); ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <link rel="apple-touch-icon" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-57x57.png', 'mebis'); ?>">
        <link rel="apple-touch-icon" sizes="72x72" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-72x72.png', 'mebis'); ?>">
        <link rel="apple-touch-icon" sizes="114x114" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-114x114.png', 'mebis'); ?>">

        <span data-mode="default"></span>
    </head>

    <body <?php echo $OUTPUT->body_attributes(); ?>>

        <?php echo $OUTPUT->standard_top_of_body_html() ?>

        <div class="me-wrapper wrapper-learning-platform" role="main">

            <?php
            // Print out the top & side navbar containing navigating between subsystems of mebis, fontsize switch, user login etc.
            echo $OUTPUT->main_navbar();
            // Print out the sub menu bar (header) with dropdownmenus.
            echo $OUTPUT->main_header();
            ?>

            <!-- CONTENT -->
            <div class="container homepage-container admin-container">
                
                <!-- Breadcrumbs and page-heading-button -->
                <div id="page-navbar" class="clearfix">
                    <div class="row">
                        <nav class="breadcrumb-nav" role="navigation" aria-label="breadcrumb">
                            <?php echo $OUTPUT->main_breadcrumbs(); ?>
                        </nav>    
                        <div class="breadcrumb-button"><?php echo $OUTPUT->breadcrumb_button(); ?></div>
                    </div>
                </div>

                <div class="row">

                    <div class="col-md-12 hidden-lg">
                        <div class="admin-navigation">
                            <?php echo $OUTPUT->render_adminnav_selectbox(); ?>
                        </div>
                    </div>

                    <div class="col-sm-12">
                        <div class="admin-content">
                            <?php
                            echo $OUTPUT->course_content_header(); ?>
                             <div id="main-content-wrapper">
                            <?php echo $OUTPUT->main_content(); ?>
                             </div>
                            <?php
                            echo $OUTPUT->course_content_footer();
                            ?>
                        </div>
                    </div>
                    <div class="col-lg-12 col-sm-12 no-background visible-lg">
                        <h2 id="my-apps"><?php echo get_string('my-apps', 'theme_mebis'); ?></h2>
                    </div>
                    <div class="col-lg-12 visible-lg">
                        <aside class="row">
                            <?php
                            if ($knownregionadminnavi) {
                                echo $OUTPUT->blocks('admin-navi', array(), 'div');
                            }
                            ?>
                        </aside>
                    </div>
                    
                </div>
            </div>

            <div id="root-footer"></div>

            <?php echo $OUTPUT->mebis_footer(); ?>

        </div>
        <!-- HOMEPAGE-WRAPPER [end] -->

        <?php echo $OUTPUT->main_footer(); ?>
        
        <div class="me-page-action-menu visible-lg">
            <ul class="me-menu-anchor-links">
                <?php  
                echo $OUTPUT->page_fastaccess_navigation();
                ?>
            </ul>
        </div>
        
        <?php 
        echo $OUTPUT->page_action_navigation();
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