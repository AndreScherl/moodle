<?php
// This page prints a particular instance of slideshow.

require_once(dirname(__FILE__)."/../../config.php");
global $CFG, $PAGE, $OUTPUT, $DB;
require_once("lib.php");

$id = optional_param('id', 0, PARAM_INT);
$a = optional_param('a', 0, PARAM_INT);
$autoshow = optional_param('autoshow', 0, PARAM_INT);
$imgnum = optional_param('img_num', 0, PARAM_INT);
$recompress = optional_param('recompress', 0, PARAM_INT);
$pause = optional_param('pause', 0, PARAM_INT);

if ($a) { // Two ways to specify the module.
    $slideshow = $DB->get_record('slideshow', array('id' => $a), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('slideshow', $slideshow->id, $slideshow->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('slideshow', $id, 0, false, MUST_EXIST);
    $slideshow = $DB->get_record('slideshow', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
if ($imgnum == 0) { // Qualifies add_to_log, otherwise every slide view increments log.
    $params = array(
        'context' => $context,
        'objectid' => $slideshow->id
    );
    $event = \mod_slideshow\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('slideshow', $slideshow);
    $event->trigger();
}

// Print header.
$autodelay = 0;
$PAGE->set_url('/mod/slideshow/view.php', array('id' => $cm->id));
$PAGE->set_button($OUTPUT->update_module_button($cm->id, 'slideshow'));
$PAGE->set_heading(format_string($slideshow->name));
$PAGE->set_title(format_string($slideshow->name));

if ($autoshow) { // Auto progress of images, no crumb trail.
    $slideshow->layout = 9; // Layout 9 prevents thumbnails being created.
    if (!$pause) {
        if (!($autodelay = $slideshow->delaytime) > 0) { // Set seconds wait for auto popup progress.
            $pause = true; // If time 0 then pause...
        }
    }
    if ($slideshow->autobgcolor) { // Include style to make background black in popup ...
        echo '<STYLE type="text/css">body {
                background-color:black ! important; background-image : none ! important; color : #ccc ! important}
                p,td,h1,h2,h3,h4,h5,h6 { color: #ccc ! important ; background-color: #000 ! important;}
                A:link, A:visited{color : #06c}A:hover{color : #0c3}
                </STYLE>';
    }
} else { // Normal page header.
    echo $OUTPUT->header();
}
// Print the main part of the page.
slideshow_secure_script($CFG->slideshow_securepix); // Prints javascript ("open image in new window" also conditional on $CFG->slideshow_securepix).
$conditions = array('contextid' => $context->id, 'component' => 'mod_slideshow', 'filearea' => 'content', 'itemid' => 0);
$filerecords = $DB->get_records('files', $conditions);
$fs = get_file_storage();
/** @var stored_file[] $files */
$files = array();
$thumbs = array();
$resized = array();
$showdir = '/';
foreach ($filerecords as $filerecord) {
    // Check only image files.
    if (preg_match("|\.jpe?g$|", $filerecord->filename) || preg_match("|\.gif$|", $filerecord->filename) ||
        preg_match("|\.png$|", $filerecord->filename)) {
        $showdir = $filerecord->filepath;
        if (preg_match("|^thumb_|", $filerecord->filename)) {
            $filename = str_replace('thumb_', '', $filerecord->filename);
            $thumbs[$filename] = $filename;
            continue;
        }
        if (preg_match("|^resized_|", $filerecord->filename)) {
            $filename = str_replace('resized_', '', $filerecord->filename);
            $resized[$filename] = $filename;
            continue;
        }
        $files[$filerecord->filename] = new stored_file($fs, $filerecord, 'content');
    }
}

$imgcount = 0;
$maxwidth = $CFG->slideshow_maxwidth;
$maxheight = $CFG->slideshow_maxheight;
$urlroot = $CFG->wwwroot.'/pluginfile.php/'.$context->id.'/mod_slideshow/content/0';
$baseurl = $urlroot.$showdir;
$filearray = array();
$error = '';
foreach ($files as $filename => $file) {
    // OK, let's look at the pictures in the folder ...
    // iterate and process images.
    if (in_array($filename, $thumbs) || in_array($filename, $resized)) {
        continue; // Done those already.
    }
    $filearray[$filename] = $filename;
    // Create thumbnail if non existant.
    $tfilerecord = array(
        'contextid' => $file->get_contextid(), 'filearea' => $file->get_filearea(),
        'component' => $file->get_component(), 'itemid' => $file->get_itemid(), 'filepath' => $file->get_filepath(),
        'filename' => 'thumb_'.$file->get_filename(), 'userid' => $file->get_userid()
    );
    try {
        // This may fail for various reasons.
        $fs->convert_image($tfilerecord, $file, 80, 60, true);
    } catch (Exception $e) {
        // Oops!
        $imgcount = 0;
        $error = '<p><b>'.$e->getMessage().'</b> '.$filename.'</p>';
        break;
    }
    // Create resized image if non existant.
    $tfilerecord = array(
        'contextid' => $file->get_contextid(), 'filearea' => $file->get_filearea(),
        'component' => $file->get_component(), 'itemid' => $file->get_itemid(), 'filepath' => $file->get_filepath(),
        'filename' => 'resized_'.$file->get_filename(), 'userid' => $file->get_userid()
    );
    try {
        // This may fail for various reasons.
        $fs->convert_image($tfilerecord, $file, $maxwidth, $maxheight, true);
    } catch (Exception $e) {
        // Oops!
        $imgcount = 0;
        $error = '<p><b>'.$e->getMessage().'</b> '.$filename.'</p>';
        break;
    }
    if (!$slideshow->keeporiginals) {
        $file->delete(); // Dump the original.
    }
    $imgcount++;
}
if ($imgcount == 0 and count($resized) > 0) {
    $filearray = $resized;
    $imgcount = count($filearray);
} else if ($imgcount < count($resized)) {
    $filearray = array_merge($filearray, $resized);
    $imgcount = count($filearray);
}

sort($filearray);
if ($slideshow->centred) {
    echo '<div align="center">';
}
if ($imgcount) {
    // $slideshow->layout defines thumbnail position - 1 is on top, 2 is bottom.
    // $slideshow->filename defines the position of captions. 1 is on top, 2 is bottom, 3 is on the right.
    if ($slideshow->layout == 1) {
        // Print thumbnail row.
        slideshow_display_thumbs($filearray, $baseurl, $cm, $imgnum);
    }
    // Process caption text.
    $currentimage = slideshow_filetidy($filearray[$imgnum]);
    $captionarray[$currentimage] = slideshow_caption_array($slideshow->id, $currentimage);

    if (isset($captionarray[$currentimage])) {
        $captionstring = $captionarray[$currentimage]['caption'];
        $titlestring = $captionarray[$currentimage]['title'];
    } else {
        $captionstring = $currentimage;
        $titlestring = '';
    }

    // If there is a title, show it!
    if ($titlestring) {
        echo format_text('<h1>'.$titlestring.'</h1>');
    }
    echo '<p style="margin-left : 5px">';
    if ($slideshow->filename == 1) {
        echo '<p>'.$captionstring.'<p>';
    } else if ($slideshow->filename == 3) {
        echo '<table cellpadding="5"><tr><td valign="top">';
    }
    // Display main picture, with link to next page and plain text for alt and title tags.
    echo '<a name="pic" href="?id='.($cm->id).'&img_num='.fmod($imgnum + 1, $imgcount).'&autoshow='.$autoshow.'">';
    echo '<img src="'.$baseurl.'resized_'.$filearray[$imgnum].'" alt="'.$filearray[$imgnum]
        .'" title="'.$filearray[$imgnum].'">';
    echo "</a><br />";
    if ($slideshow->filename == 2) {
        echo '<p>'.$captionstring.'<p>';
    } else if ($slideshow->filename == 3) {
        echo '</td><td valign="top"><p>'.$captionstring.'</td></tr></table>';
    }

    if ($slideshow->layout == 2) {
        // Print thumbnail row.
        slideshow_display_thumbs($filearray, $baseurl, $cm, $imgnum);
    }

    if (!$autoshow) {
        // Set up regular navigation options (autopoup, image in new window, teacher options).
        $popheight = $CFG->slideshow_maxheight + 100;
        $popwidth = $CFG->slideshow_maxwidth + 100;
        echo '<p style="width:50%;text-align:right;"><a target="popup" href="?id='
            .($cm->id)."&autoshow=1\" onclick=\"return openpopup('/mod/slideshow/view.php?id="
            .($cm->id)."&autoshow=1', 'popup', 'menubar=0,location=0,scrollbars,resizable,width=$popwidth,height=$popheight', 0);\">"
            .get_string('autopopup', 'slideshow')."</a>";
        if (!$CFG->slideshow_securepix) {
            if (isset($slideshow->keeporiginals) and
                $DB->record_exists('files', array(
                    'contextid' => $context->id, 'filepath' => $showdir, 'filename' => $filearray[$imgnum]
                ))
            ) {
                echo '<br /><a href="'.$baseurl.$filearray[$imgnum].'" target="_blank">'.
                    get_string('open_new', 'slideshow').'</a>';
            } else {
                echo '<br /><a href="'.$baseurl.'resized_'.$filearray[$imgnum].'" target="_blank">'.
                    get_string('open_new', 'slideshow').'</a>';
            }
        }
        if (has_capability('moodle/course:update', $context)) {
            echo '<br /><a href="captions.php?id='.$cm->id.'">'.get_string('edit_captions', 'slideshow').'</a></p>';
        }
    } else {
        // Set up autoplay navigation (< || >).
        echo '<p align="center"><a href="?id='.($cm->id).'&img_num='.fmod($imgcount + $imgnum - 1, $imgcount).
            '&autoshow='.$autoshow."\">&lt;&lt;</a>";
        if (!$pause) {
            echo '<a href="?id='.($cm->id).'&img_num='.$imgnum.'&autoshow='.$autoshow."&pause=1\">||</a>";
            echo '<meta http-equiv="Refresh" content="'.$autodelay.'; url=?id='
                .($cm->id).'&img_num='.fmod($imgnum + 1, $imgcount)."&autoshow=1\">";
        } else {
            echo "||";
        }
        echo '<a href="?id='.($cm->id).'&img_num='.fmod($imgnum + 1, $imgcount).'&autoshow='.$autoshow."\">&gt;&gt;</a></p>";
    }
} else {
    echo '<p>'.get_string('none_found', 'slideshow').' <b>'.$showdir.'</b></p>';
    echo '<p><b>'.$error.'</b></p>';
}
if ($slideshow->centred) {
    echo '</div>';
}
// Finish the page.
if ($autoshow) {
    echo '</body></html>';
} else {
    echo $OUTPUT->footer($course);
}
