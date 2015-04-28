<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/mbsschooltitle/renderer.php');

class theme_mebis_block_mbsschooltitle_renderer extends block_mbsschooltitle_renderer
{
    public function render_content($titledata)
    {
        global $OUTPUT, $PAGE;

        $o = '';

        // Link to School.
        if (!empty($titledata->usersschoolid)) {
            $schoolurl = new moodle_url('/course/index.php', array('categoryid' => $titledata->usersschoolid));
            $schoollink = html_writer::link($schoolurl, get_string('toschoolcategory', 'block_mbsschooltitle'));
            $o = html_writer::tag('div', $schoollink);
        }

        // Headline of page.
        $headline = (!empty($titledata->headline)) ? $titledata->headline : $PAGE->heading;
        $headlinetag = html_writer::tag('h1', $headline);
        $o .= html_writer::tag('div', $headlinetag, array('class' => 'mbs-schooltitle-headline'));

//        // Image.
//        if (!empty($titledata->imageurl)) {
//            $imagetag = html_writer::empty_tag('img', array('src' => $titledata->imageurl, 'alt' => get_string('imageofcategory', 'block_mbsschooltitle')));
//            $o .= html_writer::tag('div', $imagetag, array('class' => 'mbs-schooltitle-image'));
//        }
//
//        // Editlink, capability is already checked.
//        if (!empty($titledata->editurl)) {
//            $editlink = $OUTPUT->action_icon($titledata->editurl, new pix_icon('t/edit', get_string('edit')));
//            $o .= html_writer::tag('div', $editlink, array('class' => 'mbs-schooltitle-editlink'));
//        }

        return html_writer::tag('div', $o, array('id' => 'mbs-schooltitle'));
    }
}
