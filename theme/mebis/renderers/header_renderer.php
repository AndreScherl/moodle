<?php

/**
 * Header renderer.
 *
 * @package theme_mebis
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/lib/outputrenderers.php");

class theme_mebis_header_renderer extends renderer_base
{
    /**
     * Renders the main navbar. Is to be replaced by an ajax javascript version
     *
     * @global type $USER
     * @global type $PAGE
     * @global type $OUTPUT
     * @return String Html string of the navbar
     */
    public function main_navbar()
    {
        global $USER, $PAGE, $OUTPUT;
        $output = '';
        $userBar = '';

        $url_support = isset($PAGE->theme->settings->url_support) ? $PAGE->theme->settings->url_support : '#';
        $url_login = isset($PAGE->theme->settings->url_login) ? $PAGE->theme->settings->url_login : '#';
        $url_logout = isset($PAGE->theme->settings->url_logout) ? $PAGE->theme->settings->url_logout : '#';
        $url_preferences = '#';

        if (isloggedin()) {
            $userBar .= html_writer::tag('li', '', array('class' => 'divider-vertical divider-profile-left'));

            $userBar .= html_writer::start_tag('li', array('class' => 'profile'));
            $userBar .= html_writer::start_tag('a',
                array('href' => new moodle_url('/user/profile.php', array('id' => $USER->id)))
            );
            $userBar .= html_writer::start_tag('span', array('class' => 'me-username'));
            $userBar .= html_writer::tag('span', fullname($USER));
            $userBar .= html_writer::end_tag('span');
            $userBar .= $OUTPUT->user_picture($USER, array('size' => '40', 'class' => 'user-avatar', 'link' => false));
            $userBar .= html_writer::end_tag('a');
            $userBar .= html_writer::end_tag('li');

            $userBar .= html_writer::tag('li', '', array('class' => 'divider-vertical divider-profile-right'));

            $userBar .= html_writer::start_tag('li');
            $userBar .= html_writer::start_tag('a',
                array(
                    'href' => $url_logout
                )
            );
            $userBar .= html_writer::tag('span', get_string('nav-logout', 'theme_mebis'));
            $userBar .= html_writer::end_tag('a');
            $userBar .= html_writer::end_tag('li');

            $userBar .= html_writer::tag('li', '', array('class' => 'divider-vertical'));

            $url_preferences = isset($PAGE->theme->settings->url_preferences) ? $PAGE->theme->settings->url_preferences : '#';
        } else {

            $userBar .= html_writer::tag('li', '', array('class' => 'divider-vertical'));

            $userBar .= html_writer::start_tag('li');
            $userBar .= html_writer::start_tag('a', array('href' => $url_login));
            $userBar .= html_writer::tag('span', get_string('nav-login', 'theme_mebis'));
            $userBar .= html_writer::end_tag('a');
            $userBar .= html_writer::end_tag('li');

            $userBar .= html_writer::tag('li', '', array('class' => 'divider-vertical'));

            $url_preferences = isset($PAGE->theme->settings->url_preferences) ? $PAGE->theme->settings->url_preferences : '#';
        }


        $output .= html_writer::start_tag('nav',
            array(
                'class' => 'navbar yamm navbar-inverse navbar-fixed-top top-bar',
                'id' => 'topbar', 'role' => 'navigation'
            )
        );

        $output .= html_writer::start_div('container');

        $output .= html_writer::start_div('row');
        $output .= html_writer::start_div('col-xs-12');
        $output .= html_writer::start_div('navbar-header clearfix');

        $output .= html_writer::start_tag('button',
            array(
                'data-target' => '.js-navbar-collapse',
                'data-toggle' => 'collapse', 'type' => 'button', 'class' => 'navbar-toggle collapsed'
            )
        );
        $output .= html_writer::tag('span', get_string('nav-toggle', 'theme_mebis'), array('class' => 'sr-only'));
        $output .= html_writer::tag('span', '', array('class' => 'icon-bar'));
        $output .= html_writer::tag('span', '', array('class' => 'icon-bar'));
        $output .= html_writer::tag('span', '', array('class' => 'icon-bar'));
        $output .= html_writer::end_tag('button');

        $output .= html_writer::start_tag('ul',
            array('role' => 'tablist', 'class' => 'nav nav-tabs nav-login hidden-lg')
        );
        $output .= html_writer::start_tag('li', array('class' => 'dropdown'));
        $output .= html_writer::tag('a', get_string('nav-logout', 'theme_mebis'), array('href' => '#', 'class' => 'dropdown-toggle active'));
        $output .= html_writer::end_tag('li');
        $output .= html_writer::end_tag('ul');

        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('js-navbar-collapse collapse hidden-lg');
        $output .= html_writer::tag('ul', $this->buildNavStructure(), array('class' => 'nav'));
        $output .= html_writer::start_tag('ul', array('class' => 'js-navbar-collapse-submenu'));
        $output .= html_writer::start_tag('li');
        $output .= html_writer::start_tag('a', array('href' => '#invert'));
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-kontrast'));
        $output .= get_string('nav-contrast', 'theme_mebis');
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');
        $output .= html_writer::start_tag('li');
        $output .= '<a href="' . $url_support . '">';
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-support'));
        $output .= get_string('nav-support', 'theme_mebis');
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');
        $output .= html_writer::start_tag('li');
        $output .= '<a href="' . $url_preferences . '">';
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-verwaltung'));
        $output .= get_string('nav-management', 'theme_mebis');
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');
        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('collapse navbar-collapse hidden-xs');

        $output .= html_writer::start_tag('ul', array('class' => 'nav navbar-nav'));
        $output .= html_writer::tag('li', '', array('class' => 'divider-vertical'));

        $output .= html_writer::start_tag('li');
        $output .= html_writer::start_tag('a',
            array('href' => '#', 'class' => 'change-fontsize', 'data-change' => 'dec')
        );
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-schrift-verkleinern'));
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');

        $output .= html_writer::start_tag('li');
        $output .= html_writer::start_tag('a',
            array('href' => '#', 'class' => 'change-fontsize no-padding-right', 'data-change' => 'inc')
        );
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-schrift-vergroessern'));
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');

        $output .= html_writer::tag('li', '', array('class' => 'divider-vertical'));

        $output .= html_writer::start_tag('li', array('class' => 'text'));
        $output .= html_writer::start_tag('a', array('href' => '#invert', 'id' => 'me-invert'));
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-kontrast'));
        $output .= html_writer::tag('span', get_string('nav-contrast', 'theme_mebis'));
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');

        $output .= html_writer::tag('li', '', array('class' => 'divider-vertical'));

        $output .= html_writer::start_tag('li', array('class' => 'text'));
        $output .= html_writer::start_tag('a', array('href' => '#'));
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-vorlesen'));
        $output .= html_writer::tag('span', get_string('nav-read', 'theme_mebis'));
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');

        $output .= html_writer::tag('li', '', array('class' => 'divider-vertical'));
        $output .= html_writer::end_tag('ul');

        $output .= html_writer::start_tag('ul', array('class' => 'nav navbar-nav navbar-right'));
        $output .= html_writer::tag('li', '', array('class' => 'divider-vertical'));

        $output .= html_writer::start_tag('li');
        $output .= html_writer::start_tag('a', array('href' => $url_support));
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-support'));
        $output .= html_writer::tag('span', get_string('nav-support', 'theme_mebis'));
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');

        $output .= html_writer::tag('li', '', array('class' => 'divider-vertical no-margin-right'));

        $output .= html_writer::start_tag('li');
        $output .= html_writer::start_tag('a', array('href' => $url_preferences));
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-verwaltung'));
        $output .= html_writer::tag('span', get_string('nav-management', 'theme_mebis'));
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');

        $output .= $userBar;

        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_tag('nav');

        return $output;
    }

    /**
     * Renders the main sidebar navigation. Is to be replaced by an ajax javascript version
     *
     * @return String Html string of the sidebar
     */
    public function main_sidebar()
    {
        $output = html_writer::start_div('me-sidebar-wrapper visible-lg');
        $output .= html_writer::tag('ul', $this->buildNavStructure('fa-2x'), array('class' => 'me-sidebar-nav'));
        $output .= html_writer::end_div();
        return $output;
    }

    /**
     * Renders the moodle header including the menu bar
     *
     * @return String Html string of the header
     */
    public function main_header()
    {
        global $CFG;

        $img_alt = get_string('header-img-title', 'theme_mebis');
        $output = html_writer::start_tag('header', array('class' => 'me-page-header full'));
        $output .= html_writer::start_div('container');
        $output .= html_writer::start_div('row');
        $output .= html_writer::start_div('col-md-12');
        $output .= html_writer::start_div('logo-row logo-small clearfix');
        $output .= html_writer::start_div('row');

        $output .= html_writer::start_div('col-md-6 col-xs-12 logo');
        $output .= html_writer::start_tag('a', array('href' => new moodle_url('/')));
        $output .= html_writer::tag('img', '',
            array(
                'class' => 'pull-left', 'src' => $CFG->wwwroot . '/theme/mebis/pix/mebis-logo-lernplattform.png',
                'data-src-contrast' => $CFG->wwwroot . '/theme/mebis/pix/mebis-logo-lernplattform-kontrast.png',
                'alt' => $img_alt, 'title' => $img_alt, 'height' => '45', 'width' => '340'
            )
        );
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('col-md-6 col-xs-12');
        $output .= html_writer::start_div('me-learning-platform-header-nav');
        $output .= $this->main_menubar();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_tag('header');

        return $output;
    }

    /**
     * Generates the navigation structure based on the input made in the admin interface.
     *
     * @param string $additionalCSSClasses
     * @return String Html string of the navigation structure
     */
    protected function buildNavStructure($additionalCSSClasses = '')
    {
        global $CFG;


        $code = '';
        $navItems = explode(';', trim($CFG->local_dlb_mebis_sites, ";"));

        foreach ($navItems as $navItem) {
            list($name, $url) = explode(',', $navItem);
            if (!empty($name) and !empty($url)) {
                $iconClass = '';
                $liClass = '';
                switch ($name) {
                    default:
                    case 'Startseite':
                        $iconClass = 'icon-me-cockpit';
                        break;
                    case 'Infoportal':
                        $iconClass = 'icon-me-infoportal';
                        break;
                    case 'Mediathek':
                        $iconClass = 'icon-me-mediathek';
                        break;
                    case 'Lernplattform':
                        $iconClass = 'icon-me-lernplattform';
                        $liClass = 'me-active';
                        break;
                    case 'Prüfungsarchiv':
                        $iconClass = 'icon-me-pruefungsarchiv';
                        break;
                }

                $code .= html_writer::start_tag('li', array('class' => $liClass));
                $code .= html_writer::start_tag('a', array('href' => $url));
                $code .= html_writer::tag('i', '', array('class' => $iconClass . ' ' . $additionalCSSClasses));
                $code .= html_writer::tag('span', $name);
                $code .= html_writer::end_tag('a');
                $code .= html_writer::end_tag('li');
            }
        }

        return $code;
    }

    /**
     * Renders the breadcrumb navigation
     *
     * @global type $OUTPUT
     * @return String Html string of the breadcrumb navigation
     */
    public function main_breadcrumbs()
    {
        global $OUTPUT;
        $items = $this->page->navbar->get_items();
        if (empty($items)) { // MDL-46107
            return '';
        }

        $breadcrumbs = '';
        foreach ($items as $item) {
            $item->hideicon = true;
            $attributes = ($item->isactive) ? array('class' => 'active') : array();
            $breadcrumbs .= html_writer::tag('li', $OUTPUT->render($item), $attributes);
        }
        $breadcrumbsLine = html_writer::tag('ol', $breadcrumbs, array('class' => 'breadcrumb text-left'));

        return html_writer::tag('div', html_writer::tag('div', $breadcrumbsLine, array('class' => 'col-md-12')),
            array('class' => 'row')
        );
    }

    /**
     * Renders the main menubar inside the header
     *
     * @global type $PAGE
     * @global type $OUTPUT
     * @global type $COURSE
     * @return String Html string of the menubar
     */
    public function main_menubar()
    {
        global $PAGE, $OUTPUT, $COURSE;
        $block_menu = '';
        $user_menu = '';
        $admin_menu = '';
        $unreadMessages = '';
        $content = '';

        // when the page is set in editing mode, render an additional menu item to add blocks to the current page
        if ($PAGE->user_is_editing()) {
            $block_menu .= html_writer::start_tag('li', array('class' => 'dropdown'));
            $block_menu .= html_writer::tag('a', '<i class="icon-me-block-hinzufuegen"></i>',
                array('class' => 'dropdown-toggle extra-nav-mobile-spacer', 'href' => '#', 'data-toggle' => 'dropdown')
            );
            $block_menu .= html_writer::start_tag('ul',
                array('class' => 'dropdown-menu dropdown-right', 'role' => 'menu')
            );
            $block_menu .= html_writer::start_tag('li');
            $block_menu .= html_writer::start_div();
            $block_menu .= html_writer::start_div('dropdown-inner');
            $addblocks = block_add_block_ui($this->page, $OUTPUT);
            $block_menu .= $addblocks->content;
            $block_menu .= html_writer::end_div();
            $block_menu .= html_writer::end_div();
            $block_menu .= html_writer::end_tag('li');
            $block_menu .= html_writer::end_tag('ul');
            $block_menu .= html_writer::end_tag('li');
        }

        $menu_items = '';

        $pageHeadingButtons = $OUTPUT->page_heading_button();
        if (false !== strpos($pageHeadingButtons, 'edit=')) {
            $pageHeadingButtons = str_replace('class="internal"', 'class="internal strong"', $pageHeadingButtons);
        }

        if (false !== stripos($pageHeadingButtons, '<input')) {
            $pageHeadingButtons = '';

            $node = $PAGE->settingsnav->get('courseadmin');
            if ($node instanceof navigation_node) {
                $editing = $node->children->get('turneditingonoff');
                if ($editing->display && null !== $editing->action) {
                    if ($editing->text instanceof lang_string) {
                        $edittxt = $editing->text->out();
                    } else {
                        $edittxt = $editing->text;
                    }

                    $editCls = '';
                    if (false === strpos($editing->action, 'edit=')) {
                        $editCls = ' strong';
                    }

                    $pageHeadingButtons .= html_writer::start_tag('li');
                    $pageHeadingButtons .= html_writer::tag('a', $edittxt, array('href' => $editing->action, 'class' => 'internal' . $editCls));
                    $pageHeadingButtons .= html_writer::end_tag('li');
                }
            }
        }

        $menu_items .= $pageHeadingButtons;

        $node = $PAGE->settingsnav->get('usercurrentsettings');
        if ($node instanceof navigation_node) {
            $menu_items .= $this->generateMenuContentFor($node);
            if (!empty($menu_items)) {
                $user_menu = html_writer::start_div('dropdown-inner');
                $user_menu .= html_writer::start_tag('ul', array('class' => 'me-subnav'));
                $user_menu .= $menu_items;
                $user_menu .= html_writer::end_tag('ul');
                $user_menu .= html_writer::end_div();
            }
        }

        // try to display admin menu only if user has the required capabilities (improves performance for non-admins)
        if (has_capability('moodle/site:config', context_system::instance())) {
            $admin_menu = html_writer::start_div('dropdown-inner admin-dropdown');
            $admin_menu .= html_writer::tag('strong', get_string('menu-administration-head', 'theme_mebis'));
            $admin_menu .= html_writer::start_tag('ul');
            $admin_menu .= html_writer::start_tag('li');
            $admin_menu .= html_writer::tag('a', get_string('menu-administration-link', 'theme_mebis'), array('href' => new moodle_url('/admin/index.php'), 'class' => 'internal'));
            $admin_menu .= html_writer::start_tag('li');
            $admin_menu .= html_writer::end_tag('ul');
            $admin_menu .= html_writer::end_div();
        }

        if (message_count_unread_messages() > 0) {
            $unreadMessages = html_writer::tag('span', message_count_unread_messages(), array('class' => 'me-msg-count'));
        }

        $content = html_writer::start_div('moodle-menu');
        $content .= html_writer::start_tag('ul', array('class' => 'nav'));
        // Messages menu item
        $content .= html_writer::start_tag('li');
        $content .= html_writer::tag('a', '<i class="fa fa-bullhorn"></i>' . $unreadMessages,
            array('class' => 'extra-nav-mobile-spacer', 'href' => new moodle_url('/message/index.php'))
        );
        $content .= html_writer::end_tag('li');

        // Files menu item
        $content .= html_writer::start_tag('li');
        $content .= html_writer::tag('a', '<i class="fa fa-folder"></i>',
            array('class' => 'extra-nav-mobile-spacer', 'href' => new moodle_url('/user/files.php'))
        );
        $content .= html_writer::end_tag('li');

        // My dashboard item
        $content .= html_writer::start_tag('li');
        $content .= html_writer::tag('a', '<i class="fa fa-laptop"></i>',
            array('class' => 'extra-nav-mobile-spacer', 'href' => new moodle_url('/my'))
        );
        $content .= html_writer::end_tag('li');

        // "Add block" menu item
        $content .= $block_menu;

        // Config menu item (only show if at least one of the menu items has valid nodes)
        if (!empty($user_menu) || !empty($admin_menu)) {
            $content .= html_writer::start_tag('li', array('class' => 'dropdown'));
            $content .= html_writer::tag('a', '<i class="fa fa-cog"></i>',
                array('class' => 'extra-nav-mobile-spacer', 'href' => '#', 'data-prevent' => 'default')
            );
            $content .= html_writer::start_tag('ul', array('class' => 'dropdown-menu', 'role' => 'menu'));
            $content .= html_writer::start_tag('li');
            $content .= html_writer::div($user_menu . $admin_menu, 'cogmenu');
            $content .= html_writer::end_tag('li');
            $content .= html_writer::end_tag('ul');
            $content .= html_writer::end_tag('li');
        }

        $isCourse = ($COURSE->id !== '1');
        if ($isCourse) {
            $node = $PAGE->settingsnav;
            if ($node instanceof navigation_node) {
                $course_menu = $this->generateMenuContentFor($node, array('admin'));
                if ($course_menu) {
                    $content .= html_writer::start_tag('li', array('class' => 'dropdown'));
                    $content .= html_writer::start_tag('a',
                        array('class' => 'dropdown-toggle extra-nav-mobile-spacer', 'href' => '#', 'data-prevent' => 'default')
                    );
                    $content .= html_writer::tag('i', '', array('class' => 'icon-me-lernplattform'));
                    $content .= html_writer::end_tag('a');
                    $content .= html_writer::start_tag('ul', array('class' => 'dropdown-menu', 'role' => 'menu'));
                    $content .= html_writer::start_tag('li');
                    $content .= html_writer::start_div('coursemenu');
                    $content .= html_writer::start_div('dropdown-inner');
                    $content .= html_writer::start_tag('ul', array('class' => 'me-subnav'));
                    $content .= $course_menu;
                    $content .= html_writer::end_tag('ul');
                    $content .= html_writer::end_div();
                    $content .= html_writer::end_div();
                    $content .= html_writer::end_tag('li');
                    $content .= html_writer::end_tag('ul');
                    $content .= html_writer::end_tag('li');
                }
            }
        }

        $content .= html_writer::end_tag('ul');
        $content .= html_writer::end_div();

        return $content;
    }

    /**
     * Turns a list of all child nodes for the given $node name into a list of li elements to render in the menu
     * bar.
     *
     * @param $node navigation_node
     * @param $linkfilters array
     * @return String Html string of the Menu Content
     */
    protected function generateMenuContentFor(navigation_node $node, array $linkfilters = array())
    {
        $menuitems = '';
        foreach ($node->children as $navchild) {
            /* @var $navchild navigation_node */
            if ($navchild->display) {
                $link = '#';
                $linktxt = '';
                $linkCls = '';
                if (null !== $navchild->action) {
                    $link = htmlspecialchars_decode($navchild->action->__toString());
                }

                // skip all the links which contain on the given $linkfilters
                $skipLink = false;
                foreach ($linkfilters as $filter) {
                    if (false !== strpos($link, $filter)) {
                        $skipLink = true;
                        break;
                    }
                }

                if (!$skipLink) {
                    if ($navchild->text instanceof lang_string) {
                        $linktxt = $navchild->text->out();
                    } else {
                        $linktxt = $navchild->text;
                    }

                    if (false !== strpos($link, 'edit=')) {
                        $linkCls = ' strong';
                    }

                    if ($navchild->has_children()) {
                        if ($link !== '#') {
                            $menuitems .= html_writer::start_tag('li', array('class' => 'hiddennavnode'));
                            $menuitems .= html_writer::start_tag('span', array('class' => 'internal hiddennavbutton'));
                            $menuitems .= html_writer::tag('a', $linktxt, array('href' => $link, 'class' => $linkCls));
                            $menuitems .= html_writer::end_tag('span');
                            $menuitems .= html_writer::start_tag('ul', array('class' => 'hiddennavleaf'));
                        } else {
                            if ($linktxt !== '') {
                                $menuitems .= html_writer::start_tag('li', array('class' => 'hiddennavnode'));
                                $menuitems .= html_writer::tag('span', $linktxt, array('class' => 'internal hiddennavbutton' . $linkCls));
                                $menuitems .= html_writer::start_tag('ul', array('class' => 'hiddennavleaf'));
                            }
                        }
                        $menuitems .= $this->generateMenuContentFor($navchild);
                        $menuitems .= html_writer::end_tag('ul');
                        $menuitems .= html_writer::end_tag('li');
                    } else {
                        $menuitems .= html_writer::start_tag('li');
                        $menuitems .= html_writer::tag('a', $linktxt, array('class' => 'internal' . $linkCls, 'href' => $link));
                        $menuitems .= html_writer::end_tag('li');
                    }
                }
            }
        }

        return $menuitems;
    }
}
