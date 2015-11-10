<?php
/**
 * footer renderer.
 *
 * @package theme_mebis
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/lib/outputrenderers.php");

class theme_mebis_footer_renderer extends renderer_base {

    /**
     * Renders the main footer
     * @return String Html string of the sidebar
     */
    public function main_footer() {
        global $CFG, $PAGE;
        
        $url_newsletter = get_config("theme_mebis", "footer_url_newsletter") ? get_config("theme_mebis", "footer_url_newsletter") : '#';
        $url_about = get_config("theme_mebis", "footer_url_about") ? get_config("theme_mebis", "footer_url_about") : '#';
        $url_contact = get_config("theme_mebis", "footer_url_contact") ? get_config("theme_mebis", "footer_url_contact") : '#';
        
        $output = '';

        $output .= html_writer::start_tag('footer', array('id' => 'footer-copyright'));
        $output .= html_writer::start_div('footer-copyright');
        $output .= html_writer::start_div('container');
        $output .= html_writer::start_div('row');

        $output .= html_writer::start_div('col-xs-12 col-md-6');
        $output .= html_writer::start_tag('ul', array('class' => 'footer-nav clearfix'));

        $output .= html_writer::start_tag('li', array('class' => 'newsletter'));
        $output .= html_writer::start_tag('a', array('href' => $url_newsletter));
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-email'));
        $output .= html_writer::tag('span', get_string('footer-newsletter', 'theme_mebis'));
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');

        $output .= html_writer::start_tag('li');
        $output .= html_writer::tag('a', get_string('footer-about', 'theme_mebis'), 
                array('href' => $url_about, 'class' => 'internal'));
        $output .= html_writer::end_tag('li');

        $output .= html_writer::start_tag('li');
        $output .= html_writer::tag('a', get_string('footer-contact', 'theme_mebis'), 
                array('href' => $url_contact, 'class' => 'internal'));
        $output .= html_writer::end_tag('li');

        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('col-xs-12 col-md-6');
        $output .= html_writer::start_div('footer-logos text-right');
        $output .= html_writer::tag('a', '', 
                array('href' => 'http://www.km.bayern.de/', 'target' => '_blank', 'class' => 'logo-first'));
        $output .= html_writer::tag('a', '', 
                array('href' => 'https://www.isb.bayern.de/', 'target' => '_blank', 'class' => 'logo-second'));
        $output .= html_writer::tag('a', '', 
                array('href' => 'https://alp.dillingen.de/', 'target' => '_blank', 'class' => 'logo-third'));
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

        $output .= html_writer::end_div();
        $output .= html_writer::start_div('row');

        $output .= html_writer::start_div('col-xs-12 col-md-6 footer-mebis-logo hidden-xs');
        $output .= html_writer::tag('img', '', array('class' => 'img-responsive', 'src' => $CFG->wwwroot . '/theme/mebis/pix/mebis-logo.png',
                    'alt' => 'mebis footer-logo', 'width' => '250', 'height' => '42'));
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('col-xs-12 col-md-6 copyright text-right');

        $footer_links = $this->get_footer_links();

        $i = 0;
        foreach ($footer_links as $title => $url) {
            $output .= html_writer::tag('a', $title, array('href' => $url, 'class' => 'internal'));
            if ($i < (count($footer_links) - 1)) {
                $output .= html_writer::tag('span', '|', array('class' => 'space'));
            }
            $i++;
        }
        $output .= html_writer::end_div();

        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_tag('footer');

        return $output;
    }

    /**
     * Renders the event footer
     * @return String Html structure of the event footer
     */
    public function main_eventfooter() {
        $output = '';

        $output .= html_writer::start_div('me-event-footer');
        $output .= html_writer::start_div('container');
        $output .= html_writer::start_div('row');

        $output .= html_writer::start_div('col-md-4');
        $output .= html_writer::tag('a', get_string('footer-my-school', 'theme_mebis'), array('href' => '', 'class' => 'btn btn-full'));
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('col-md-7 pull-right text-right');
        $output .= html_writer::tag('img', '', array('src' => '/theme/mebis/pix/logo-ministerium.png', 'alt' => ''));
        $output .= html_writer::end_div();

        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

        return $output;
    }

    /**
     * Renders the footer searchbar. Is to be replaced by a block.
     * @return String Html structure of the footer searchbar
     */
    public function main_searchbar() {
        $output = '';

        $output .= html_writer::start_div('me-media-search me-search-box');
        $output .= html_writer::start_div('container');
        $output .= html_writer::start_div('row');
        $output .= html_writer::start_tag('form', array('action' => new moodle_url('/course/search.php'),
                    'id' => 'coursesearchnavbar', 'role' => 'form', 'class' => 'form-horizontal'));

        $output .= html_writer::start_div('col-md-4');
        $output .= html_writer::tag('label', get_string('footer-search-course_or_school', 'theme_mebis'), array('for' => 'navsearchbox'));
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('col-md-8');
        $output .= html_writer::start_div('input-group');

        $output .= html_writer::tag('input', '', array('name' => 'search', 'placeholder' => get_string('footer-search-media', 'theme_mebis'),
                    'id' => 'navsearchbox', 'class' => 'form-control', 'type' => 'text'));
        $output .= html_writer::start_tag('span', array('class' => 'input-group-btn'));
        $output .= html_writer::tag('button', html_writer::tag('i', '', array('class' => 'fa fa-search')), array('type' => 'submit', 'class' => 'btn btn-primary'));
        $output .= html_writer::end_tag('span');

        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

        $output .= html_writer::end_tag('form');
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

        return $output;
    }

    protected function get_footer_links() {
        global $PAGE;
        $links = get_config("theme_mebis", "footer_links");
        $links = explode("\n", $links);

        $footer_links = array();

        foreach ($links as $link) {
            $footer_link = explode('|', $link);
            if (!empty($footer_link[1]) && $footer_link[1] !== '') {
                $footer_links[$footer_link[0]] = $footer_link[1];
            }
        }

        return $footer_links;
    }

}
