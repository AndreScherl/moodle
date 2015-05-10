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
 * A layout for the login page in mebis theme, note that this page 
 * is normally used by administrators only, so we don't need any
 * block regions or usermenus.
 * 
 * parent theme bootstrap
 *
 * @package   theme_mebis
 * @copyright 2015 ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Allow no popups.
$PAGE->set_popup_notification_allowed(false);

$setzoom = theme_bootstrap_get_zoom();

echo $OUTPUT->doctype()
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html(); ?>
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimal-ui">
    
    <link rel="apple-touch-icon" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-57x57.png','mebis');?>">
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-72x72.png','mebis');?>">
    <link rel="apple-touch-icon" sizes="114x114" href="<?php echo $OUTPUT->pix_url('apple-touch-icon-114x114.png','mebis');?>">

    <link rel="stylesheet" href="<?php echo new moodle_url("/theme/mebis/style/mebis.css");?>" data-mode="default">
    <?php $PAGE->requires->js( new moodle_url("/theme/mebis/vendor/modernizr-2.6.2-respond-1.1.0.min.js")); ?>
</head>

<body <?php echo $OUTPUT->body_attributes($setzoom); ?>>
<?php echo $OUTPUT->standard_top_of_body_html() ?>

<header class="moodleheader">
    <div class="container-fluid">
    <a href="<?php echo $CFG->wwwroot ?>" class="logo"></a>
    <?php echo $OUTPUT->page_heading(); ?>
    </div>
</header>

<div id="page" class="container-fluid">
   
    <div id="page-content" class="row">
        <div id="region-main">
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

         ?>
         <div class="container"> 
            <footer id="page-footer">
                <?php  echo $OUTPUT->standard_footer_html(); ?>
            </footer>
        </div>
    
    <?php
        echo $OUTPUT->standard_end_of_body_html();
    ?>
</div>
</body>
</html>