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
    
    private $pageactionnavigation = false;

    public function page_action_navigation()
    {
        // asch: The next five lines are only a quick fix, because we will refactor the me-in-page-menu in time.
        global $PAGE;
        $noactionpages = array("course-view-topics", "course-view-grid", "course-view-onetopic", "course-view-topcoll");
        if (in_array($PAGE->pagetype, $noactionpages)) {
            return '';
        }
        
        if (!$this->pageactionnavigation) {
            $menu_items = array(
                html_writer::link('#top', '<i class="icon-me-back-to-top"></i>', array('class' => 'me-back-top'))
            );

            $output = html_writer::start_tag('div', array('class' => 'me-in-page-menu'));
            $output .= html_writer::start_tag('ul', array('class' => 'me-in-page-menu-features'));
            foreach($menu_items as $item) {
                $output .= html_writer::tag('li', $item);
            }
            $output .= html_writer::end_tag('ul');
            $output .= html_writer::end_tag('div');
            
            $this->pageactionnavigation = true;
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
