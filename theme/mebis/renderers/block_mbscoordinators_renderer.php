<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/mbscoordinators/renderer.php');

class theme_mebis_block_mbscoordinators_renderer extends block_mbscoordinators_renderer
{
    /** render all coordinators for a school in a unordered list
     *
     * @global record $OUTPUT
     * @param array $coordinators list of all coordinators for this school.
     * @return type
     */
    public function render_coordinators($coordinators)
    {
        global $OUTPUT;

        $out = html_writer::tag('div', get_string('mebiscoordinators', 'block_mbscoordinators') . ': ');
        $outli='';
        foreach ($coordinators as $coordinator) {

            $messageurl = new moodle_url('/message/index.php', array('id' => $coordinator->id));
            //$messageicon = $OUTPUT->pix_icon('t/email', get_string('sendmessage', 'block_mbscoordinators'));
            $messageicon = html_writer::tag('i', '', array('class' => 'icon-me-email'));

            $li = html_writer::link($messageurl, $messageicon.fullname($coordinator));

            $outli .= html_writer::tag('li', $li);
        }
        $out .= html_writer::nonempty_tag('ul', $outli);

        return html_writer::tag('div', $out, array('class' => 'mbscoordinators'));
    }
}
