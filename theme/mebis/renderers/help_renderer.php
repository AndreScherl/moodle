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

    public function get_adminnav_selectbox()
    {
        $nav = new mebis_admin_nav();
        return $nav->render_as_selectbox();
    }

}

class mebis_admin_nav
{
    public $navigation;

    public function __construct()
    {
        global $PAGE, $CFG, $OUTPUT;

        $this->page = $PAGE;
        $nav = $this->page->settingsnav;
        $this->navigation = $this->get_admin_nav_items($nav);
    }

    public function render_as_selectbox()
    {
        if($this->navigation) {
            $select = sprintf('<h3>%s</h3>', get_string('menu-administration-link', 'theme_mebis'));
            $select .= '<select data-change>';
            foreach($this->navigation as $key => $nav) {
                $select .= $this->render_option($nav);
            }
            $select .= '<select>';

            return $select;
        }
    }

    public function render_option( $nav, $lvl = 0 )
    {
        $url = $this->get_item_url($nav->action);
        $title = (gettype($nav->text) === 'string') ? $nav->text : $this->get_item_title($nav->text);
        $output = '';

        if($title) {

            $title = str_repeat('&nbsp;&nbsp;&nbsp;', $lvl) . $title;

            if($nav->nodetype == 0) {
                $selected = ($this->current_url() == $url) ? ' selected' : '';
                $output .= sprintf('<option value="%s"%s>%s</option>', $url, $selected, $title);
            } else {
                $childs = '';
                foreach($nav->children as $child) {
                    $childs .= $this->render_option( $child, $lvl + 1  );
                }
                $output .= sprintf('<optgroup label="%s">%s</optgroup>', $title, $childs);
            }

        }

        return $output;
    }

    public function current_url()
    {
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https')
                        === FALSE ? 'http' : 'https';
        $host     = $_SERVER['HTTP_HOST'];
        $script   = $_SERVER['SCRIPT_NAME'];
        $params   = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';

        $currentUrl = $protocol . '://' . $host . $script . $params;

        return $currentUrl;
    }

    public function get_item_title( $text )
    {
        $text = (array) $text;
        $title = '';

        foreach($text as $key => $val) {
            $key = str_replace('*', '', $key);
            $key = strip_tags($key);

            if($key == 'string') {
                $title = $val;
            }
        }

        return $title;
    }

    public function get_item_url( $action )
    {
        $action = (array) $action;

        $url = '';

        foreach($action as $key => $val) {
            $key = str_replace('*', '', $key);
            $key = strip_tags($key);
            $params = '';

            if($key == 'scheme') {
                $val = $val . '://';
            }

            if($key != 'params') {
                $url .= $val;
            } else {

                $i = 0;
                foreach($val as $key => $param) {
                    $prefix = (!$i) ? '?' : '&amp;';
                    $params .= sprintf('%s%s=%s', $prefix, $key, $param);
                    $i++;
                }
            }

            $url .= $params;
        }

        return $url;
    }


    public function get_admin_nav_items()
    {
        global $CFG, $OUTPUT;
        $nav = $this->page->settingsnav;

        foreach($nav->children as $key => $children) {
            if(!$children->id && $children->key == 'root' && $children->text == get_string('administrationsite')) {
                return $children->children;
            }
        }

        return false;
    }
}
