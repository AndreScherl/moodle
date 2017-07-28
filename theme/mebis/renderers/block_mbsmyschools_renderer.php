<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/mbsmyschools/renderer.php');

class theme_mebis_block_mbsmyschools_renderer extends block_mbsmyschools_renderer {

    /**
     * Construct list of users schools
     *
     * @return string html to be displayed
     */
    public function schoollist($userschools) {
        $output = html_writer::start_tag('div', array('class' => 'col-md-12'));
        $output .= html_writer::start_tag("ul", array("class" => "block-grid-xs-1 block-grid-xc-2 block-grid-md-3 list_myschools"));
        foreach ($userschools as $key => $value) {
            $output .= html_writer::start_tag("li", array('class' => 'schoolbox'));
            $output .= html_writer::start_div('schoolbox-meta');
            $output .= html_writer::start_div('row');
            $output .= html_writer::start_div('col-xs-12 box-type text-right');
            $output .= html_writer::tag('i', '', array('class' => 'icon-me-schule'));
            $output .= html_writer::end_div();
            $output .= html_writer::end_div();
            $output .= html_writer::end_div();

            $output .= html_writer::start_div('schoolbox-inner');
            $link = new moodle_url("/course/index.php?categoryid=".$value->id);
            $output .= html_writer::start_tag('a', array('class' => 'schoolbox-link', 'href' => $link));
            $output .= html_writer::tag('span', $value->name, array('class' => 'schoolname internal'));
            $output .= html_writer::end_tag('a');
            $output .= html_writer::end_div();
            $output .= html_writer::end_tag("li");
        }
        $output .= html_writer::end_tag("ul");
        $output .= html_writer::end_tag('div');
        return $output;
    }

}