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
 * A layout for the default pages in mebis theme based on default.php in
 * parent theme bootstrap
 *
 * @package   theme_mebis
 * @copyright 2015 ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/user/profile/lib.php'); //required for profile_load_data

// Check existing regions.
$knownregiontop = $PAGE->blocks->is_known_region('top');
$knownregionsidepre = $PAGE->blocks->is_known_region('side-pre');
$knownregionsidepost = $PAGE->blocks->is_known_region('side-post');

$ismydashboard = ($PAGE->pagetype == 'my-index');

// Add mbsgettingstarted to my dashboard?
if (!isset($USER->mbsgettingstartedhide)){
    $hidembsgettingstarted = false;
} else if (isset($USER->mbsgettingstartedhide) && !$USER->mbsgettingstartedhide) {
    $hidembsgettingstarted = false;
} else {
    $hidembsgettingstarted = true;
}

$theuser = clone($USER); 
profile_load_data($theuser);
        
$showmbsgettingstarted = ($ismydashboard 
        and (!isset($theuser->profile_field_mbsgettingstartedshow) || $theuser->profile_field_mbsgettingstartedshow)
        and !$hidembsgettingstarted 
        and $knownregiontop);

if ($showmbsgettingstarted) {
    $attributes['data-block'] = 'mbsgettingstarted';
    $attributes['class'] = 'block_mbsgettingstarted';
    $attributes['id'] = 'block_mbsgettingstarted';
    $OUTPUT->add_fake_block('mbsgettingstarted', 'top', $attributes);
}

// Add mbswizzard to my dashboard if mbsgettingstarted is visible (because the user can click wizzard link within this block)
$OUTPUT->add_block_mbswizzard_if_needed('side-pre', $showmbsgettingstarted);

// Allow popup - notification for my dashboard.
$PAGE->set_popup_notification_allowed($ismydashboard);

// Check whether regions has content.
$hastop = $PAGE->blocks->region_has_content('top', $OUTPUT);
$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);

// TODO: discuss this line - deprecated?
// $regions = theme_mebis_bootstrap_grid($hasapps, null);

if ($hassidepre) {
    theme_bootstrap_initialise_zoom($PAGE);
}

// Define additional css classes that should be set on the body element. 
// text-center is needed to center the main content block
$setzoom = theme_bootstrap_get_zoom();
echo $OUTPUT->doctype()
?>

<html <?php echo $OUTPUT->htmlattributes(); ?>>
    <head>
        <title><?php echo $OUTPUT->page_title(); ?></title>
        <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
        <?php echo $OUTPUT->standard_head_html(); ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, minimal-ui">

        <link rel="apple-touch-icon" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-57x57.png', 'mebis'); ?>">
        <link rel="apple-touch-icon" sizes="72x72" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-72x72.png', 'mebis'); ?>">
        <link rel="apple-touch-icon" sizes="114x114" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-114x114.png', 'mebis'); ?>">

        <link rel="stylesheet" href="<?php echo new moodle_url("/theme/mebis/style/mebis.css"); ?>" data-mode="default">
        <script src="<?php echo new moodle_url("/theme/mebis/vendor/modernizr-2.6.2-respond-1.1.0.min.js"); ?>"></script>
    </head>

    <body <?php echo $OUTPUT->body_attributes($setzoom); ?>>

        <?php echo $OUTPUT->standard_top_of_body_html() ?>

        <div class="me-wrapper wrapper-learning-platform" role="main">

            <?php
            // Print out the top navbar containing fontsize switch, user login etc.
            echo $OUTPUT->main_navbar();
            // Print out the side navbar to navigate between subsystems of mebis.
            echo $OUTPUT->main_sidebar();
            // Print out the sub menu bar (header) with dropdownmenus.
            echo $OUTPUT->main_header();
            ?>

            <!-- CONTENT -->
            <div class="container homepage-container">

                <!-- Breadcrumbs and page-heading-button -->
                <div id="page-navbar" class="clearfix">
                    <div class="row">
                        <nav class="breadcrumb-nav" role="navigation" aria-label="breadcrumb">
                            <?php echo $OUTPUT->main_breadcrumbs(); ?>
                        </nav>    
                        <div class="breadcrumb-button"><?php echo $OUTPUT->page_heading_button(); ?></div>
                    </div>
                </div>

                <?php
                if ($knownregiontop) {
                    echo $OUTPUT->mebis_blocks('top', array(), 'aside', '0');
                }

                echo $OUTPUT->course_content_header();
                ?>
                <div id="main-content-wrapper">
                        <?php echo $OUTPUT->main_content(); ?>
                    
                </div>
                <?php
                echo $OUTPUT->course_content_footer();
                ?>

                <?php if ($hassidepre || $hassidepost) { ?>
                
                    <div class="row">
                        <div class="col-lg-12 col-sm-12 no-background">
                            <h2><?php echo get_string('my-apps', 'theme_mebis'); ?></h2>
                        </div>
                    </div>

                    <?php
                    echo html_writer::start_tag('aside', array('class' => 'row'));

                    if ($knownregionsidepre) {
                        echo $OUTPUT->blocks('side-pre', array(), 'div');
                    }

                    if ($knownregionsidepost) {
                        echo $OUTPUT->blocks('side-post', array(), 'div');
                    }
                    echo html_writer::end_tag('aside');
                    ?>
                <?php } ?>

            </div>

            <div id="root-footer"></div>

            <!-- CONTENT [end] -->
            <?php echo $OUTPUT->mebis_footer(); ?>

        </div>

        <?php
        echo $OUTPUT->main_footer();
        echo $OUTPUT->page_action_navigation();

        $PAGE->requires->js(new moodle_url("/theme/mebis/javascripts/vendor.min.js"));
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
