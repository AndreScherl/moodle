<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/mbsnewcourse/renderer.php');

class theme_mebis_block_mbsnewcourse_renderer extends block_mbsnewcourse_renderer
{
    /** render all coordinators for a school in a unordered list
     *
     * @global record $OUTPUT
     * @param array $coordinators list of all coordinators for this school.
     * @return type
     */
    public function render_block_content($categoryid) {

        $out = '';

        if (\block_mbsnewcourse\local\mbs_course_request::can_request_course($categoryid)) {

            $url = new moodle_url('/blocks/mbsnewcourse/request.php', array('category' => $categoryid));

            $requesticon = html_writer::tag('i', '', array('class' => 'icon-me-kurs-anfordern'));
            $requestlink = html_writer::link($url, $requesticon . get_string('requestcourse', 'block_mbsnewcourse'),
                    array('id' => 'requestcourse'));
            $out .= html_writer::tag('li', $requestlink);
        }

        if (\block_mbsnewcourse\local\mbsnewcourse::can_create_course($categoryid)) {

            $url = new moodle_url('/course/edit.php', array('category' => $categoryid, 'returnto' => 'category'));
            $createicon = html_writer::tag('i', '', array('class' => 'icon-me-kurs-erstellen'));
            $createlink = html_writer::link($url, $createicon . get_string('createcourse', 'block_mbsnewcourse'), 
                    array('id' => 'createcourse'));
            $out .= html_writer::tag('li', $createlink);
        }

        $out = html_writer::tag('ul', $out, array('class' => 'mbsnewcourse-actionlinks'));
        
        if (\block_mbsnewcourse\local\mbs_course_request::can_approve_course($categoryid)) {
            $out .= $this->render_request_list();
        }

        $out = html_writer::div($out, 'mbsnewcourse');

        return $out;
    }
}
