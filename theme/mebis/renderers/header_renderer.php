<?php

/**
 * Header renderer.
 *
 * @package theme_mebis
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/lib/outputrenderers.php");

class theme_mebis_header_renderer extends renderer_base {

    /**
     * Generates the navigation structure based on the input made in the admin interface.
     * This is a temporary solution and would be replaced, when cockpit-site
     * (i. e. central entry point for mebis systems) is finished.
     * 
     * @param string $additionalCSSClasses
     * @return String Html string of the navigation structure
     */
    protected function buildNavStructure($additionalCSSClasses = '') {
        global $CFG;

        $code = '';
        $navItems = explode(';', trim($CFG->local_mbs_mebis_sites, ";"));
        foreach ($navItems as $navItem) {
            if (!empty($navItem) && $navItem !== '') {
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
                            $liClass .= ' me-active';
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
        }
        return $code;
    }

    /**
     * Render the top navbar containing fontsize switch, user login etc.
     * This is a temporary solution and would be replaced, when cockpit-site
     * (i. e. central entry point for mebis systems) is finished.
     *
     * @global record $USER
     * @global object $PAGE
     * @global object $OUTPUT
     * @return String Html string of the navbar
     */
    public function main_navbar() {
        global $USER, $PAGE, $CFG;
        $output = '';
        $userBar = '';
        $muserBar = '';

        $url_support = isset($PAGE->theme->settings->url_support) ? $PAGE->theme->settings->url_support : '#';
        $url_login = isset($PAGE->theme->settings->url_login) ? $PAGE->theme->settings->url_login : '#';
        $url_logout = isset($PAGE->theme->settings->url_logout) ? $PAGE->theme->settings->url_logout : '#';
        $url_preferences = isset($PAGE->theme->settings->url_preferences) ? $PAGE->theme->settings->url_preferences : '#';
        $url_preferences_personal = isset($PAGE->theme->settings->url_preferences_personal) ? $PAGE->theme->settings->url_preferences_personal : '#';
        // Roles with capability to view the link to the IDM in topbar.
        $idmlinkroles = array('idm-koordinator', 'helpdesk', 'nutzerverwalter', 'schuelerverwalter');

        if (isloggedin()) {
            // desktop version
            $userBar .= html_writer::tag('li', '', array('class' => 'divider-vertical divider-profile-left'));

            $userBar .= html_writer::start_tag('li', array('class' => 'profile'));
            $userBar .= html_writer::start_tag('a', array('href' => $url_preferences_personal));
            $userBar .= html_writer::start_tag('span', array('class' => 'me-username'));
            $userBar .= html_writer::tag('span', fullname($USER));
            $userBar .= html_writer::end_tag('span');
            $userBar .= html_writer::tag('img', '',
                array('class' => 'user-avatar', 'src' => $CFG->wwwroot . '/theme/mebis/pix/avatar40px.jpg',
                'alt' => 'Avatar', 'size' => '40', 'link' => false));
            $userBar .= html_writer::end_tag('a');
            $userBar .= html_writer::end_tag('li');

            $userBar .= html_writer::tag('li', '', array('class' => 'divider-vertical divider-profile-right'));

            $userBar .= html_writer::start_tag('li');
            $userBar .= html_writer::start_tag('a', array('href' => $url_logout));
            //$userBar .= html_writer::tag('i', '', array('class' => 'icon-me-login'));            
            $userBar .= html_writer::tag('span', get_string('nav-logout', 'theme_mebis'));
            $userBar .= html_writer::end_tag('a');
            $userBar .= html_writer::end_tag('li');

            $userBar .= html_writer::tag('li', '', array('class' => 'divider-vertical'));
            
            // mobile version
            $muserBar .= html_writer::start_tag('li', array('class' => 'dropdown'));
            $muserBar .= html_writer::link($url_logout, 'Logout', array('class' => 'dropdown-toggle active'));
            $avatarimg = html_writer::tag('img', '',
                array('class' => 'user-avatar',
                    'src' => $CFG->wwwroot . '/theme/mebis/pix/avatar40px.jpg',
                    'width' => '50px',
                    'height' => '50px',
                    'alt' => 'User Profile',
                    'link' => false));
            $muserBar .= html_writer::link($url_preferences_personal, $avatarimg, array('class' => 'navbar-mobile-avatar'));
            $muserBar .= html_writer::end_tag('li');
        } else {

            $userBar .= html_writer::tag('li', '', array('class' => 'divider-vertical no-margin-right'));

            $userBar .= html_writer::start_tag('li', array('class' => 'me-login-box'));
            $userBar .= html_writer::start_tag('a', array('href' => $url_login));
            //$userBar .= html_writer::tag('i', '', array('class' => 'icon-me-login')); 
            $userBar .= html_writer::tag('span', get_string('nav-login', 'theme_mebis'));
            $userBar .= html_writer::end_tag('a');
            $userBar .= html_writer::end_tag('li');

            $userBar .= html_writer::tag('li', '', array('class' => 'divider-vertical no-margin-left'));
            
            // mobile version
            $muserBar .= html_writer::start_tag('li', array('class' => 'dropdown'));
            $loginicon .= html_writer::tag('i', '', array('class' => 'icon-me-login')); 
            $muserBar .= html_writer::link($url_login, $loginicon.'Login', array('class' => 'dropdown-toggle'));
            $muserBar .= html_writer::end_tag('li');
        }

        $output .= html_writer::start_tag('nav', array(
                    'class' => 'navbar yamm navbar-inverse navbar-fixed-top top-bar',
                    'id' => 'topbar', 'role' => 'navigation'
                        )
        );
        $output .= html_writer::start_div('container');
        $output .= html_writer::start_div('row');
        $output .= html_writer::start_div('col-xs-12');
        $output .= html_writer::start_div('navbar-header clearfix');
        
        // This renders the button to open mobile sidebar with horizontal lines.
        $output .= html_writer::start_tag('button', array(
                    'data-target' => '.js-navbar-collapse',
                    'data-toggle' => 'collapse', 'type' => 'button',
                    'class' => 'navbar-toggle collapsed hidden-lg'));
        $output .= html_writer::tag('span', get_string('nav-toggle', 'theme_mebis'), array('class' => 'sr-only'));
        $output .= html_writer::tag('span', '', array('class' => 'icon-bar'));
        $output .= html_writer::tag('span', '', array('class' => 'icon-bar'));
        $output .= html_writer::tag('span', '', array('class' => 'icon-bar'));
        $output .= html_writer::end_tag('button');
        
        // The right top bar menu: user profile and logout
        $output .= html_writer::start_tag('ul', array('role' => 'tablist',
                    'class' => 'nav nav-tabs nav-login hidden-lg'));
        
        $output .= $muserBar;
        
        // $output .= html_writer::start_tag('li');
        // $output .= html_writer::tag('a', get_string('nav-logout', 'theme_mebis'), array('href' => $url_logout, 'class' => 'dropdown-toggle active'));
        // $output .= html_writer::end_tag('li');    
        
        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        
        // The mobile sidebar, dropping down after click the button above.
        $output .= html_writer::start_div('js-navbar-collapse collapse hidden-lg');
        // Links to other mebis applications.
        $output .= html_writer::tag('ul', $this->buildNavStructure(), array('class' => 'nav'));
        // Links to support and idm.
        $output .= html_writer::start_tag('ul', array('class' => 'js-navbar-collapse-submenu'));
        // Support link.
        $output .= html_writer::start_tag('li');
        $output .= html_writer::start_tag('a', array('href' => $url_support));
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-support'));
        $output .= html_writer::tag('span', get_string('nav-support', 'theme_mebis'));
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');
        // IDM link.
        $output .= html_writer::start_tag('li');
        $output .= html_writer::start_tag('a', array('href' => $url_preferences));
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-verwaltung'));
        $output .= html_writer::tag('span', get_string('nav-management', 'theme_mebis'));
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');
        $output .= html_writer::end_tag('ul');
        // End of mobile sidebar.
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('collapse navbar-collapse hidden-xs hidden-sm hidden-md');
        $output .= html_writer::start_tag('ul', array('class' => 'nav navbar-nav'));
        $output .= html_writer::tag('li', '', array('class' => 'divider-vertical'));

        $output .= html_writer::start_tag('li');
        $output .= html_writer::start_tag('a', array('href' => '#', 'class' => 'change-fontsize', 'data-change' => 'dec'));
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-schrift-verkleinern'));
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');

        $output .= html_writer::start_tag('li');
        $output .= html_writer::start_tag('a', array('href' => '#', 'class' => 'change-fontsize no-padding-right', 
            'data-change' => 'inc'));
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-schrift-vergroessern'));
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');

        $output .= html_writer::tag('li', '', array('class' => 'divider-vertical'));

        //contrast mode is not working, so no display of the icon
       /* $output .= html_writer::start_tag('li', array('class' => 'text'));
        $output .= html_writer::start_tag('a', array('href' => '#invert', 'id' => 'me-invert'));
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-kontrast'));
        $output .= html_writer::tag('span', get_string('nav-contrast', 'theme_mebis'));
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');

        $output .= html_writer::tag('li', '', array('class' => 'divider-vertical'));*/
        
        $output .= html_writer::end_tag('ul');

        $output .= html_writer::start_tag('ul', array('class' => 'nav navbar-nav navbar-right'));
        $output .= html_writer::tag('li', '', array('class' => 'divider-vertical'));

        $output .= html_writer::start_tag('li', array('class' => 'me-support-button'));
        $output .= html_writer::start_tag('a', array('href' => $url_support));
        $output .= html_writer::tag('i', '', array('class' => 'icon-me-support'));
        $output .= html_writer::tag('span', get_string('nav-support', 'theme_mebis'));
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_tag('li');

        $output .= html_writer::tag('li', '', array('class' => 'divider-vertical no-margin-right'));
        
        // Link to Nutzerverwaltung (IDM), only shown to users with appropriate capability
        $canseeidmlink = false;
        foreach ($idmlinkroles as $idmlinkrole) {
            if((isset($USER->mebisRole) && in_array($idmlinkrole, $USER->mebisRole)) || is_siteadmin()) {
                $canseeidmlink = true;
                continue;
            }
        }
        if ($canseeidmlink) {
            $output .= html_writer::start_tag('li');
            $output .= html_writer::start_tag('a', array('href' => $url_preferences));
            $output .= html_writer::tag('i', '', array('class' => 'icon-me-verwaltung'));
            $output .= html_writer::tag('span', get_string('nav-management', 'theme_mebis'));
            $output .= html_writer::end_tag('a');
            $output .= html_writer::end_tag('li');
        }
        
        $output .= $userBar;

        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_tag('nav');

        return $output;
    }

    /**
     * Renders the main sidebar navigation.
     * This is a temporary solution and would be replaced, when cockpit-site
     * (i. e. central entry point for mebis systems) is finished.
     *
     * @return String Html string of the sidebar
     */
    public function main_sidebar() {
        $output = html_writer::start_div('me-sidebar-wrapper visible-lg');
        $output .= html_writer::tag('ul', $this->buildNavStructure('fa-2x'), array('class' => 'me-sidebar-nav'));
        $output .= html_writer::end_div();
        return $output;
    }

    /**
     * Renders the moodle header (sub menu) including the menu bar
     *
     * @return String Html string of the header
     */
    public function main_header() {
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
        $output .= html_writer::tag('img', '', array(
                    'class' => 'pull-left', 'src' => $CFG->wwwroot . '/theme/mebis/pix/mebis-logo-lernplattform.png',
                    'data-src-contrast' => $CFG->wwwroot . '/theme/mebis/pix/mebis-logo-lernplattform-kontrast.png',
                    'alt' => $img_alt, 'title' => $img_alt, 'height' => '45', 'width' => '340'
                        )
        );
        $output .= html_writer::end_tag('a');
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('col-md-6 col-xs-12');
        $output .= html_writer::start_div('me-learning-platform-header-nav');

        // Render the main_menubar;
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
     * Renders the breadcrumb navigation
     *
     * @global type $OUTPUT
     * @return String Html string of the breadcrumb navigation
     */
    public function main_breadcrumbs() {
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

        return $breadcrumbsLine;
    }

    /** render the dropable add block menu
     * 
     * @global object $PAGE
     * @global object $OUTPUT
     * @return string HTML code for the dropdwon.
     */
    protected function render_menubar_add_block_menu() {
        global $PAGE, $OUTPUT;

        if (!$PAGE->user_is_editing()) {
            return '';
        }

        $params = array('class' => 'dropdown-toggle extra-nav-mobile-spacer', 'href' => '#', 'data-toggle' => 'dropdown');
        $block_menu = html_writer::tag('a', '<i class="icon-me-block-hinzufuegen"></i>', $params);
        $block_menu .= html_writer::start_tag('ul', array('class' => 'dropdown-menu dropdown-right', 'role' => 'menu')
        );
        $block_menu .= html_writer::start_tag('li');
        $block_menu .= html_writer::start_div();
        $addblocks = block_add_block_ui($this->page, $OUTPUT);
        $block_menu .= html_writer::div($addblocks->content, 'dropdown-inner');
        $block_menu .= html_writer::end_div();
        $block_menu .= html_writer::end_tag('li');
        $block_menu .= html_writer::end_tag('ul');

        return html_writer::tag('li', $block_menu, array('class' => 'dropdown'));
    }

    /** Render the  Website-Administration - Link only for admins 
     * Note that this is intentionally NON moodle standard, if user has at least
     * one capability to view a page within the admin tree this user must use
     * moodles settings block.
     * 
     * @return string
     */
    protected function render_menubar_admin_menu() {

        // Display
        if (!has_capability('moodle/site:config', context_system::instance())) {
            return '';
        }

        $content = html_writer::start_div('dropdown-inner admin-dropdown');
        $content .= html_writer::tag('strong', get_string('menu-administration-head', 'theme_mebis'));

        $url = new moodle_url('/admin/index.php');
        $text = get_string('menu-administration-link', 'theme_mebis');
        $webadminlink = html_writer::link($url, $text, array('class' => 'internal'));
        $item = html_writer::tag('li', $webadminlink);
        $content .= html_writer::tag('ul', $item);

        $content .= html_writer::end_div();

        return $content;
    }

    /** Render the userrelated content for settings dropdown
     * 
     * @return string
     */
    protected function render_menubar_user_menu() {
        global $PAGE;

        $content = '';

        $node = $PAGE->settingsnav->get('usercurrentsettings');

        if ($node instanceof navigation_node) {

            $menuitems = $this->generateMenuContentFor($node, array('siteadministration', 'usersettings'));
            if (!empty($menuitems)) {
                $content = html_writer::start_div('dropdown-inner');
                $content .= html_writer::start_tag('ul', array('class' => 'me-subnav'));
                $content .= $menuitems;
                $content .= html_writer::end_tag('ul');
                $content .= html_writer::end_div();
            }
        }

        return $content;
    }

    /** Render all the course related administration stuff.
     * 
     * @return string
     */
    protected function render_menubar_courseadmin_menu() {
        global $PAGE, $COURSE;
        $content ='';
        
        // Show only in real courses.
        if ($COURSE->id == SITEID) {
            return '';
        }

        $node = $PAGE->settingsnav;

        if ($node instanceof navigation_node) {

            $coursemenu = $this->generateMenuContentFor($node, array('siteadministration', 'usersettings'));

            if ($coursemenu) {
                $content = html_writer::start_tag('li', array('id' => 'coursedropdownmenu', 'class' => 'dropdown'));
                $content .= html_writer::start_tag('a', array('class' => 'dropdown-toggle extra-nav-mobile-spacer', 'href' => '#', 'data-prevent' => 'default'));
                $content .= html_writer::tag('i', '', array('class' => 'icon-me-lernplattform'));
                $content .= html_writer::end_tag('a');
                $content .= html_writer::start_tag('ul', array('class' => 'dropdown-menu', 'role' => 'menu'));
                $content .= html_writer::start_tag('li');
                $content .= html_writer::start_div('coursemenu');
                $content .= html_writer::start_div('dropdown-inner');
                $content .= html_writer::start_tag('ul', array('class' => 'me-subnav'));
                $content .= $coursemenu;
                $content .= html_writer::end_tag('ul');
                $content .= html_writer::end_div();
                $content .= html_writer::end_div();
                $content .= html_writer::end_tag('li');
                $content .= html_writer::end_tag('ul');
                $content .= html_writer::end_tag('li');
            }
        }

        return $content;
    }

    /**
     * Renders the main menubar inside the header (submenu), including:
     * 
     * - settings menu
     * - Messages menu item
     * - Files menu item
     * - My dashboard item
     * - add block menu dropdown
     * - Config menu item
     *
     * @global type $PAGE
     * @global type $OUTPUT
     * @global type $COURSE
     * @return String Html string of the menubar
     */
    public function main_menubar() {

        $content = '';

        // Messages menu item.
        $unreadMessages = '';
        if (message_count_unread_messages() > 0) {
            $unreadMessages = html_writer::tag('span', message_count_unread_messages(), array('class' => 'me-msg-count'));
        }

        $url = new moodle_url('/message/index.php');
        $text = html_writer::tag('i', '', array('class' => 'fa fa-bullhorn')) . $unreadMessages;
        $messageslink = html_writer::link($url, $text, array('class' => 'extra-nav-mobile-spacer'));
        $content .= html_writer::tag('li', $messageslink);

        // Files menu item.
        $url = new moodle_url('/user/files.php');
        $text = html_writer::tag('i', '', array('class' => 'fa fa-folder'));
        $fileslink = html_writer::link($url, $text, array('class' => 'extra-nav-mobile-spacer'));
        $content .= html_writer::tag('li', $fileslink);

        // My dashboard item.
        $url = new moodle_url('/my');
        $text = html_writer::tag('i', '', array('class' => 'fa fa-laptop'));
        $dashboardlink = html_writer::link($url, $text, array('class' => 'extra-nav-mobile-spacer'));
        $content .= html_writer::tag('li', $dashboardlink);

        // add block menu item.
        $content .= $this->render_menubar_add_block_menu();

        // add menu containing user and website administration functions. 
        $adminmenu = $this->render_menubar_admin_menu();
        $usermenu = $this->render_menubar_user_menu();

        if (!empty($usermenu) || !empty($adminmenu)) {
            $content .= html_writer::start_tag('li', array('class' => 'dropdown'));
            $content .= html_writer::tag('a', '<i class="fa fa-cog"></i>', array('class' => 'dropdown-toggle extra-nav-mobile-spacer', 'href' => '#', 'data-prevent' => 'default')
            );
            $content .= html_writer::start_tag('ul', array('class' => 'dropdown-menu', 'role' => 'menu'));
            $content .= html_writer::start_tag('li');
            $content .= html_writer::div($usermenu . $adminmenu, 'cogmenu');
            $content .= html_writer::end_tag('li');
            $content .= html_writer::end_tag('ul');
            $content .= html_writer::end_tag('li');
        }

        // add all the course related administration stuff.
        $content .= $this->render_menubar_courseadmin_menu();

        $contentlist = html_writer::tag('ul', $content, array('class' => 'nav'));
        return html_writer::div($contentlist, 'moodle-menu');
    }

    /**
     * Turns a list of all child nodes for the given $node name into a list of li
     * elements to render in the menu bar.
     *
     * @param $node navigation_node
     * @param $nodeFilter array
     * @return String Html string of the Menu Content
     */
    protected function generateMenuContentFor(navigation_node $node, array $nodeFilter = array()) {
        $menuitems = '';
        foreach ($node->children as $navchild) {
            /* @var $navchild navigation_node */
            if ($navchild->display) {
                $link = '#';
                $linktxt = '';
                $linkCls = '';
                if (null !== $navchild->action) {
                    //this check is necessary, otherwise there's no __toString() method
                    if($navchild->action instanceof moodle_url) {
                        $link = htmlspecialchars_decode($navchild->action->__toString());
                    }
                    else if($navchild->action instanceof action_link){
                       $link = htmlspecialchars_decode($navchild->action->url->__toString());
                    }
                }

                // skip all nodes which contain one of the given $nodeFilter
                $skipLink = false;
                foreach ($nodeFilter as $filter) {
                    if (false !== strpos($navchild->id, $filter)) {
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
