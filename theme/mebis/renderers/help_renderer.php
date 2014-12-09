<?php

/**
 * Help note renderer.
 *
 * @package theme_mebis
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/lib/outputrenderers.php");

class theme_mebis_help_renderer extends renderer_base
{
    public function helpnote()
    {
        global $USER;

        $username = '';
        if (isloggedin()) {
            $username = ' ' . fullname($USER);
        }

        $output = html_writer::start_div('row me-help-note', array('id' => 'me-help-box'));
        $output .= html_writer::start_div('col-md-12');
        $output .= html_writer::start_div('me-help-note-container clearfix');

        $output .= html_writer::start_div('col-md-12 text-right');
        $output .= '<a href="#" id="me-help-box-closeforever" data-close="me-help-box" data-close-type="forever"><i class="fa fa-ban"></i> ' . get_string('help-note-remove-permanent', 'theme_mebis') . '</a>';
        $output .= '<a href="#" id="me-help-box-close" data-close="me-help-box" data-close-type="simple"><i class="fa fa-close"></i> ' . get_string('help-note-close', 'theme_mebis') . '</a>';
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('col-md-7 text-left');
        $output .= '<h3>' . sprintf(get_string('help-note-welcome', 'theme_mebis'), $username) . '</h3>';
        $output .= '<p>' . get_string('help-note-content', 'theme_mebis') . '</p>';
        $output .= '<a href="" class="btn btn-secondary">' . get_string('help-note-tutorial-link', 'theme_mebis') . '</a>';
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('col-md-5');
        $output .= html_writer::end_div();

        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        return $output;
    }

    public function page_action_navigation()
    {

        if(!defined('PAGE_MENU_SET')) {
            $menu_items = array(
                html_writer::link('#top', '<i class="icon-me-back-to-top"></i>', array('id' => 'me-back-top'))
            );

            $output = html_writer::start_tag('div', array('class' => 'me-in-page-menu'));
            $output .= html_writer::start_tag('ul', array('class' => 'me-in-page-menu-features'));
            foreach($menu_items as $item) {
                $output .= html_writer::tag('li', $item);
            }
            $output .= html_writer::end_tag('ul');
            $output .= html_writer::end_tag('div');
            return $output;
        }
    }
}
