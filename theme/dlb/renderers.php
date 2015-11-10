<?php
/*
  #########################################################################
  #                       DLB-Bayern
  # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  #
  # Copyright 2012 Andreas Wagner. All Rights Reserved.
  # This file may not be redistributed in whole or significant part.
  # Content of this file is Protected By International Copyright Laws.
  #
  # ~~~~~~~~~~~~~~~~~~ THIS CODE IS NOT FREE SOFTWARE ~~~~~~~~~~~~~~~~~~~~
  #
  # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  # @author Andreas Wagner (awag), DLB	andreas.wagner@alp.dillingen.de
  # @author Andrea Taras (atar), DLB	andrea.taras@alp.dillingen.de
  #########################################################################
 */

global $CFG;

class theme_dlb_core_renderer extends core_renderer {

    public function __construct(moodle_page $page, $target) {

        parent::__construct($page, $target);

        //lädt zusätzliche Stylesheet für die Schriftarten, Whiteboard-Theme
        $this->load_additional_stylesheets($page);

        //Popup-Notifications nicht erlaubt
        $page->set_popup_notification_allowed(false);
    }

    /** es wird geprüft, ob ein zusätzliches Stylesheet geladen werden muss,
     * um die Schriftgröße zu steuern, der Index des Stylesheet wird in der Sessionvariable
     * $_SESSION['MOODLECSSINDEX'] aufgehoben. Welches STylesheet geladen wird kann im
     * DLB-Block konfiguriert werden ($CFG->block_dlb_addacss für IWB-Theme,
     * $CFG->block_dlb_addcss für alle anderen Themes.
     */
    protected function load_additional_stylesheets(moodle_page $page) {
        global $CFG;

        //1. falls Head-Tag bereits geschlossen wurde, ist Sheet bereits geladen....
        if ($page->requires->is_head_done())
            return;

        //2. Index prüfen...
        $cssindex = -1;

        if (isset($_SESSION['MOODLECSSINDEX'])) {
            $cssindex = $_SESSION['MOODLECSSINDEX'];
        }

        if ($CFG->theme == 'iwb') {

            if (($cssindex > -1) and isset($CFG->block_dlb_addacss)) {

                $ADDCSS = explode(",", $CFG->block_dlb_addacss);

                if (isset($ADDCSS[$cssindex])) {

                    $addcssfile = '/blocks/dlb/addacss/' . $ADDCSS[$cssindex] . '.css';

                    if (file_exists($CFG->dirroot . $addcssfile)) {
                        $page->requires->css($addcssfile);
                    }
                }
            }
        } else if ($CFG->theme != 'iwb') {
            //falls zusätzliche Stylesheets geladen werden sollen, hier tun
            if (($cssindex > -1) and isset($CFG->block_dlb_addcss)) {

                $ADDCSS = explode(",", $CFG->block_dlb_addcss);

                if (isset($ADDCSS[$cssindex])) {

                    $addcssfile = '/blocks/dlb/addcss/' . $ADDCSS[$cssindex] . '.css';

                    if (file_exists($CFG->dirroot . $addcssfile)) {
                        $page->requires->css($addcssfile);
                    }
                }
            }
        }
    }

    /** erzeugt für das Layout general.php einen Header, der einen kursbereichsspezifischen Title und
     * ein kursbereichsspezifischen Hintergrundbild berücksichtigt.
     * @global object $CFG
     * @global object $PAGE
     * @global object $OUTPUT
     * @return String, der HMTL-Code des Headers
     */
    public function generalheader() {
        global $CFG, $PAGE, $OUTPUT;

        $headerdata = array();
        if (file_exists($CFG->dirroot . "/blocks/custom_category/block_custom_category.php")) {
            require_once($CFG->dirroot . "/blocks/custom_category/block_custom_category.php");
            $headerdata = block_custom_category::get_headerdata();
        }

        $style = (!empty($headerdata->background)) ? "background-image:url({$headerdata->background})" : "";
        $headline = (!empty($headerdata->headline)) ? $headerdata->headline : $PAGE->heading;
        $editlink = (!empty($headerdata->editlink)) ? $OUTPUT->action_icon($headerdata->editlink, new pix_icon('t/edit', get_string('edit'))) : "";
        //header-left
        $headerleft = html_writer::tag('div', '', array("id" => "general-header-left"));

        $strhome = "zur Startseite";
        $content = html_writer::link($CFG->wwwroot, $headerleft, array("alt" => $strhome, "title" => $strhome));
        //header-right
        $content .= html_writer::tag('div', '', array("style" => $style, "id" => "general-header-right"));
        //header-middle
        $heading = html_writer::tag('h1', $headline);

        $heading = "<table id=\"header-middle-table\"><tr><td>" . $heading . $editlink . "</td></tr></table>";
        $content .= html_writer::tag('div', $heading, array("id" => "general-header-middle"));

        return html_writer::tag('div', $content, array("id" => "general-header"));
    }

//++++++++ Toolbarfunctions

    /** erzeugt den HTML-Code für einen Tooltip in der Toolbar */
    protected function toolbar_tooltip($text) {
        return "<div><div class='toolbar-tooltip'><div class='tooltip-left'></div><div class='tooltip-content'>{$text}</div><div class='tooltip-right'></div></div><div style='clear:both'></div></div>";
    }

    /** ermittelt die Anzahl der ungelesenen Mitteilungen des aktuell eingeloggte Users und erzeugt den
     * HTML-Code zur Ausgabe in der Toolbar
     * @global object $USER
     * @return String, HMTL-Code zur Anzeige in der Toolbar
     */
    protected function toolbar_mymessage() {
        global $USER;

        $mymess = message_count_unread_messages($USER);

        if ($mymess == 0)
            return "";

        return "<div><div class='toolbar-toolpop'><div class='toolpop-left'></div><div class='toolpop-content'>{$mymess}</div><div class='toolpop-right'></div></div><div style='clear:both'></div></div>";
    }

    /** erzeugt alle Toolbarelemente zur Steuerung des Themes */
    protected function toolbar_themecontent() {
        global $CFG, $PAGE, $SESSION;

        $content = "";
        $themeswitchurl = "/blocks/dlb/switchtheme/switch.php?returnto=" . urlencode($PAGE->url);
        $tooltip = (!empty($SESSION->theme)) ? "Zum Standard-Theme" : 'Barrierefreies Theme';
        $href = html_writer::link($CFG->wwwroot . $themeswitchurl, $this->pix_icon('toolbar/toolbar-kontrast', $tooltip, 'theme', array('title' => '')));
        $content .= html_writer::tag('div', $href . $this->toolbar_tooltip($tooltip), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_6"));

        $fontswitchurl = "/blocks/dlb/switchfont/switch.php?returnto=" . urlencode($PAGE->url);
        $href = html_writer::link($CFG->wwwroot . $fontswitchurl . "&value=1", $this->pix_icon('toolbar/toolbar-groesse1', 'Text größer', 'theme', array('title' => '')));
        $content .= html_writer::tag('div', $href . $this->toolbar_tooltip('Text größer'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_7"));

        $href = html_writer::link($CFG->wwwroot . $fontswitchurl . "&value=0", $this->pix_icon('toolbar/toolbar-groesse2', 'Text Standardgröße', 'theme', array('title' => '')));
        $content .= html_writer::tag('div', $href . $this->toolbar_tooltip('Text Standardgröße'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_8"));

        $href = html_writer::link($CFG->wwwroot . $fontswitchurl . "&value=-1", $this->pix_icon('toolbar/toolbar-groesse3', 'Text kleiner', 'theme', array('title' => '')));
        $content .= html_writer::tag('div', $href . $this->toolbar_tooltip('Text kleiner'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_9"));

        return $content;
    }

    /** erzeugt den HTML-Code für den Loginbutton in der Toolbar */
    public function toolbar_loginbutton() {
        global $CFG;

        if (isloggedin() and !isguestuser()) {
            $text = get_string('logout');
            $url = "{$CFG->wwwroot}/login/logout.php?sesskey=" . sesskey();

            $href = html_writer::link($url, $this->pix_icon('toolbar/logout', $text, 'theme', array('title' => '')));
            $content = html_writer::tag('div', $href . $this->toolbar_tooltip($text), array("class" => "toolbar-login-item", "id" => "toolbar-login"));
        } else {
            $text = get_string('login');
            $url = $CFG->wwwroot . "/login/index.php";

            $href = html_writer::link($url, $this->pix_icon('toolbar/login', $text, 'theme', array('title' => '')));
            $content = html_writer::tag('div', $href . $this->toolbar_tooltip($text), array("class" => "toolbar-login-item", "id" => "toolbar-login"));
        }

        return $content;
    }

    /** erzeugt den HTML-Code für alle restlichen Element der Toolbar */
    public function toolbar_content() {
        global $USER, $CFG;

        $content = "";

        if (isloggedin() and !isguestuser()) {

            $href = html_writer::link($CFG->wwwroot . "/my", $this->pix_icon('toolbar/toolbar-schreibtisch', 'Mein Schreibtisch', 'theme', array('title' => '')));
            $content .= html_writer::tag('div', $href . $this->toolbar_tooltip('Meine Startseite'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_2"));

            $href = html_writer::link($CFG->wwwroot . "/calendar/view.php?view=month", $this->pix_icon('toolbar/toolbar-calendar', 'Kalender', 'theme', array('title' => '')));
            $content .= html_writer::tag('div', $href . $this->toolbar_tooltip('Kalender'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_3"));

            $href = html_writer::link($CFG->wwwroot . "/message/index.php", $this->toolbar_mymessage() . $this->pix_icon('toolbar/toolbar-mitteilungen', 'Mitteilungen', 'theme', array('title' => '')));

            $content .= html_writer::tag('div', $href . $this->toolbar_tooltip('Mitteilungen'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_4"));

            $context = context_user::instance($USER->id);
            if (has_capability('moodle/user:manageownfiles', $context)) {
                $href = html_writer::link($CFG->wwwroot . "/user/files.php", $this->pix_icon('toolbar/toolbar-dateien', 'Dateien', 'theme', array('title' => '')));
                $content .= html_writer::tag('div', $href . $this->toolbar_tooltip('Eigene Dateien'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_5"));
            }
        }

        //Themeumschalter
        $content = html_writer::tag('div', $content, array("id" => "toolbar-content-left"));

        $content .= $this->toolbar_themecontent();

        $content = html_writer::tag('div', $content, array("id" => "toolbar-content"));
        return $content;
    }

// +++ Support Button

    /** returns true if user is ahtenticated via sibboleth and has appropriated role. */
    private function can_edit_users() {
        global $USER;

        $caneditusers = (($USER->auth == 'shibboleth') && (in_array('nutzerverwalter', $USER->mebisRole) || in_array('schuelerverwalter', $USER->mebisRole)));

        return $caneditusers;
    }

    /** genetate the settings menu in the toolbar
     * Note that content of this menu depends on:
     *
     * 1- authenticfication type of the user
     * 2- the mebis role obtained by shibboleth auth.
     *
     * @global object $OUTPUT
     * @global object $USER
     * @global object $PAGE
     * @global type $COURSE
     * @return string
     */
    public function toolbar_settings_menu() {
        global $OUTPUT, $USER, $PAGE, $COURSE;

        if (!isloggedin() or isguestuser()) {
            return "";
        }

        // ... display always this users settings in toolbar.
        $user = $USER;
        $usercontext = context_user::instance($user->id); // User context
        $currentuser = true;

        $course = $COURSE;
        $systemcontext = context_system::instance();

        // ... get the Authentification for this user.
        $userauthplugin = false;
        if (!empty($user->auth)) {
            $userauthplugin = get_auth_plugin($user->auth);
        }

        $settingmenuitems = array();

        // ... get the passwordchangeurl from auth-plugin for all users which:
        // 1- has capability to change their own password.

        /* if ($userauthplugin && $currentuser && !\core\session\manager::is_loggedinas() && !isguestuser() && has_capability('moodle/user:changeownpassword', $systemcontext)) {

          $passwordchangeurl = $userauthplugin->change_password_url();

          if (empty($passwordchangeurl)) {
          $passwordchangeurl = new moodle_url('/login/change_password.php', array('id' => $course->id));
          }
          $settingmenuitems[] = html_writer::link($passwordchangeurl, get_string("changepassword", "theme_dlb"));
          } */

        // ... get change password link from auth-plugin for all users which:
        // 1. has not the capability moodle/user:update
        // 2. has the capability to edit their own profile (moodle/user:editownprofile)

        if (!is_mnet_remote_user($user)) {

            if (is_siteadmin($USER)) {

                $url = new moodle_url('/user/editadvanced.php', array('id' => $user->id, 'course' => $course->id));
                $settingmenuitems[] = html_writer::link($url, get_string('editmyprofile'));
            } else if ((has_capability('moodle/user:editprofile', $usercontext) && !is_siteadmin($user)) || ($currentuser && has_capability('moodle/user:editownprofile', $systemcontext))) {

                $url = new moodle_url('/user/edit.php', array('id' => $user->id, 'course' => $course->id));

                if (method_exists($userauthplugin, 'edit_mebis_profile')) {

                    $profileurl = $userauthplugin->edit_mebis_profile();
                    if (!empty($profileurl)) {
                        $url = $profileurl;
                    }
                }
                $settingmenuitems[] = html_writer::link($url, get_string('mebisprofile', 'theme_dlb'));
            }
        }

        // ... get the userediturl from auth-plugin for all users which:
        // 1- are authenticated via shibboleth.
        // 2- have the mebisRole "nutzerverwalter" (i. e. can_edit_users() is true)

        if ($this->can_edit_users()) {

            $editusersurl = $userauthplugin->edit_users_url();
            if (!empty($editusersurl)) {
                $settingmenuitems[] = html_writer::link($editusersurl, get_string('editusersurl', 'theme_dlb'));
            }
        }

        // ... get the link for editing the user-specific settings for moodle.
        if (has_capability('moodle/user:update', $systemcontext)) {

            $moodlesettingsurl = new moodle_url('/user/editadvanced.php', array('id' => $user->id, 'course' => $course->id));
            $settingmenuitems[] = html_writer::link($moodlesettingsurl, get_string('editmysettings', 'theme_dlb'));
        } else if ((has_capability('moodle/user:editprofile', $usercontext) && !is_siteadmin($user)) ||
                ($currentuser && has_capability('moodle/user:editownprofile', $systemcontext))) {

            $moodlesettingsurl = new moodle_url('/user/edit.php', array('id' => $user->id, 'course' => $course->id));
            $settingmenuitems[] = html_writer::link($moodlesettingsurl, get_string('editmysettings', 'theme_dlb'));
        }


        $output = html_writer::tag('div', $OUTPUT->pix_icon('toolbar/einstellungen', get_string('settings'), 'theme'), array('id' => 'toolbar-settings', 'class' => 'toolbar-content-item'));

        $submenu = html_writer::tag('ul', html_writer::tag('li', implode('</li><li>', $settingmenuitems)));
        $output .= html_writer::tag('div', $submenu, array('class' => 'toolbar-submenu', 'id' => 'toolbar-submenu',
                    'style' => 'display:none'
                ));

        $jsmodule = array(
            'name' => 'theme_dlb',
            'fullpath' => new moodle_url('/theme/dlb/js/menu.js'),
            'requires' => array('node', 'event-mouseenter')
        );

        $args = array();
        $PAGE->requires->js_init_call("M.theme_dlb.init", array($args), false, $jsmodule);

        return $output;
    }

    /** renders the HTML-code for the support button */
    public function support_button() {
        global $CFG;

        $supporturl = $CFG->block_dlb_supporturl;

        $content = '';
        if (!empty($supporturl)) {

            $popupaction = new popup_action('click', $supporturl, 'Help',
                            array('height' => '400', 'width' => '500', 'top' => 0, 'left' => 0,
                                'menubar' => false, 'location' => false, 'scrollbars' => true,
                                'resizable' => false, 'toolbar' => false, 'status' => false,
                                'directories' => false, 'fullscreen' => false, 'dependent' => true));

            $pixicon = $this->pix_icon('toolbar/support', 'Support', 'theme', array('title' => ''));

            $actionlink = $this->action_link($supporturl, $pixicon, $popupaction);

            $content = html_writer::tag('div', $actionlink . $this->toolbar_tooltip('Support'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_10"));
        }
        return $content;
    }

    /** adding debug output for profile form */
    public function standard_footer_html() {
        global $CFG, $USER, $PAGE, $OUTPUT;

        if ($CFG->debugdisplay && debugging('', DEBUG_DEVELOPER)) {  // Show user object
            if (($PAGE->pagetype == 'user-edit') or ($PAGE->pagetype == 'user-editadvanced')) {

                echo html_writer::tag('div', '', array('class' => 'clearfix'));
                echo $OUTPUT->heading('DEBUG MODE:  User session variables');
                echo html_writer::start_tag('div', array('style' => 'text-align:left'));
                print_object($USER);
                echo html_writer::end_tag('div');
            }
        }

        return parent::standard_footer_html();
    }

    /** gibt die Links auf die Institutionen zurück */
    public function pagecontent_footer() {
        global $CFG;

        if (!isloggedin()) {
            ?>
            <div class="page-content-footer">
                <div id="page-content-footer-right">
                    <a href="http://alp.dillingen.de/" target="_blank">
                        <div class="logo_alp" alt="Link zur Homepage der Akademie für Lehrerfortbildung und Personalführung Dillingen" title="Link zur Homepage der Akademie für Lehrerfortbildung und Personalführung Dillingen">
                        </div>
                    </a>
                    <a href="http://www.isb.bayern.de/" target="_blank">
                        <div class="logo_isb" alt="Link zur Homepage des Staatinstituts für Schulqualität und Bildungsforschung" title="Link zur Homepage des Staatinstituts für Schulqualität und Bildungsforschung">
                        </div>
                    </a>
                    <a href="http://www.km.bayern.de/" target="_blank">
                        <div class="logo_stmuk" alt="Link zur Homepage des Bayerischen Staatsministeriums für Bildung und Kultus, Wissenschaft und Kunst " title="Link zur Homepage des Bayerischen Staatsministeriums für Bildung und Kultus, Wissenschaft und Kunst ">
                        </div>
                    </a>
                </div>
                <div id="page-content-footer-left">
                    <?php echo $CFG->block_dlb_contentfooterleft; ?>
                </div>
            </div>
            <?php
        }
    }

//++++ Overriden Methods from core_renderer

    /** überschreibt die originale Funktion, um den Blockcode mit zusätzlichen DIVS
     * zu versehen, die für die Abrundungen an den Ecken erforderlich sind
     *
     * @param <block_contents> $bc
     * @param <object> $region
     * @return <String>
     */
    public function block(block_contents $bc, $region) {

        $bc = clone($bc); // Avoid messing up the object passed in.
        if (empty($bc->blockinstanceid) || !strip_tags($bc->title)) {
            $bc->collapsible = block_contents::NOT_HIDEABLE;
        }
        if ($bc->collapsible == block_contents::HIDDEN) {
            $bc->add_class('hidden');
        }
        if (!empty($bc->controls)) {
            $bc->add_class('block_with_controls');
        }
        if (!empty($bc->blockinstanceid)) {
            $bc->attributes['data-instanceid'] = $bc->blockinstanceid;
        }
        if ($bc->dockable) {
            $bc->attributes['data-dockable'] = 1;
        }

        $skiptitle = strip_tags($bc->title);
        if (empty($skiptitle)) {
            $output = '';
            $skipdest = '';
        } else {
            $output = html_writer::tag('a', get_string('skipa', 'access', $skiptitle), array('href' => '#sb-' . $bc->skipid, 'class' => 'skip-block'));
            $skipdest = html_writer::tag('span', '', array('id' => 'sb-' . $bc->skipid, 'class' => 'skip-block-to'));
        }

        $output .= html_writer::start_tag('div', $bc->attributes);

        $output .= html_writer::tag('div', $this->block_header($bc), array("class" => "header-wrapper"));
        $output .= $this->block_content($bc);

        $output .= html_writer::start_tag('div', array("class" => "bottom"));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        $output .= $this->block_annotation($bc);
        $output .= $skipdest;

        $this->init_block_hider_js($bc);
        return $output;
    }

    /** überschreibt die originale Funktion, um einen Zeilenumbruch einzufügen  */
    public function login_info($withlinks = null) {
        global $USER, $CFG, $DB, $SESSION;

        if (during_initial_install()) {
            return '';
        }

        if (is_null($withlinks)) {
            $withlinks = empty($this->page->layout_options['nologinlinks']);
        }

        $loginapge = ((string) $this->page->url === get_login_url());
        $course = $this->page->course;

        if (\core\session\manager::is_loggedinas()) {
            $realuser = \core\session\manager::get_realuser();
            $fullname = fullname($realuser, true);

            $loginastitle = get_string('loginas');
            $realuserinfo = " [<a href=\"$CFG->wwwroot/course/loginas.php?id=$course->id&amp;sesskey=" . sesskey() . "\"";
            $realuserinfo .= "title =\"" . $loginastitle . "\">$fullname</a>] ";
        } else {
            $realuserinfo = '';
        }

        $loginurl = get_login_url();

        if (empty($course->id)) {
// $course->id is not defined during installation
            return '';
        } else if (isloggedin()) {
            $context = context_course::instance($course->id);

            $fullname = fullname($USER, true);
// Since Moodle 2.0 this link always goes to the public profile page (not the course profile page)
            if ($withlinks) {
                $linktitle = get_string('viewprofile');
                $username = "<a href=\"$CFG->wwwroot/user/profile.php?id=$USER->id\" title=\"$linktitle\">$fullname</a>";
            } else {
                $username = $fullname;
            }
            if (is_mnet_remote_user($USER) and $idprovider = $DB->get_record('mnet_host', array('id' => $USER->mnethostid))) {
                if ($withlinks) {
                    $username .= " from <a href=\"{$idprovider->wwwroot}\">{$idprovider->name}</a>";
                } else {
                    $username .= " from {$idprovider->name}";
                }
            }
//+++ awag: hier einen <br />-Tag eingefügt...
            $username = "<br />" . $username;
//--- awag ---
            if (isguestuser()) {
                $loggedinas = $realuserinfo . get_string('loggedinasguest');
                if (!$loginapge && $withlinks) {
                    $loggedinas .= " (<a href=\"$loginurl\">" . get_string('login') . '</a>)';
                }
                //+++ awag, keine Information über Gastlogin, falls der User automatisch eingeloggt wird
                if ($CFG->autologinguests)
                    $loggedinas = "";
                //---
            } else if (is_role_switched($course->id)) { // Has switched roles
                $rolename = '';
                if ($role = $DB->get_record('role', array('id' => $USER->access['rsw'][$context->path]))) {
                    $rolename = ': ' . format_string($role->name);
                }
                //+++ atar: String loggedinas aus Theme-Languagefile geladen
                $loggedinas = get_string('loggedinas', 'theme_dlb', $username) . $rolename .
                        " (<a href=\"$CFG->wwwroot/course/view.php?id=$course->id&amp;switchrole=0&amp;sesskey=" . sesskey() . "\">" . get_string('switchrolereturn') . '</a>)';
            }
//+++ atar: Logout-Link entfernt
            else {
                $loggedinas = $realuserinfo . get_string('loggedinas', 'theme_dlb', $username) . '</a>';
            }
            $loggedinas = '<div class="logininfo"  id="logininfo">' . $loggedinas . '</div>';
        }
//+++ atar: String loggedinnot und login aus Theme-Languagefile geladen
        else {
            $loggedinas = get_string('loggedinnot', 'theme_dlb');
            if (!$loginapge) {
                $loggedinas .= " <a href=\"$loginurl\">" . get_string('login', 'theme_dlb') . '</a>';
            }
        }

        if (isset($SESSION->justloggedin)) {
            unset($SESSION->justloggedin);
            if (!empty($CFG->displayloginfailures)) {
                if (!isguestuser()) {
                    if ($count = count_login_failures($CFG->displayloginfailures, $USER->username, $USER->lastlogin)) {
                        $loggedinas .= '&nbsp;<div class="loginfailures">';
                        if (empty($count->accounts)) {
                            $loggedinas .= get_string('failedloginattempts', '', $count);
                        } else {
                            $loggedinas .= get_string('failedloginattemptsall', '', $count);
                        }
                        if (file_exists("$CFG->dirroot/report/log/index.php") and has_capability('report/log:view', context_system::instance())) {
                            $loggedinas .= ' (<a href="' . $CFG->wwwroot . '/report/log/index.php' .
                                    '?chooselog=1&amp;id=1&amp;modid=site_errors">' . get_string('logs') . '</a>)';
                        }
                        $loggedinas .= '</div>';
                    }
                }
            }
        }
        return $loggedinas;
    }

    /** überschreibt die originale Funktion, um DIVS für das Layout einzufügen */
    public function navbar() {
        $items = $this->page->navbar->get_items();

        $htmlblocks = array();

//Im Array können home = Startseite und myhome vorkommen
//falls home vorkommt und nicht an erster Position ist, muss es gelöscht werden
//home soll immer an erster Position stehen

        if (isset($items[0]) and ($items[0]->key != 'home')) {

            $properties = array(
                'key' => 'home',
                'type' => navigation_node::TYPE_SYSTEM,
                'text' => get_string('home'),
                'action' => new moodle_url('/index.php?redirect=0')
            );
            $item = new navigation_node($properties);
            $item->hideicon = true;

            array_unshift($items, $item);
        }

// Iterate the navarray and display each node
        $itemcount = count($items);

        for ($i = 0; $i < $itemcount; $i++) {

            $item = $items[$i];
            $item->hideicon = true;

            if ($i === 0) {

                $content = html_writer::tag('div', '', array('class' => "breadcrumb-start"));
                //$content .= html_writer::tag('div', $this->render($item), array('class'=>"breadcrumb-nav"));
                //ersten Breadcrumb (Link auf Startseite) nicht verlinken!
                $content .= html_writer::tag('div', $item->text, array('class' => "breadcrumb-nav"));
            } else {
                //falls home nicht am Beginn steht auslassen

                $content = html_writer::tag('div', $this->render($item), array('class' => "breadcrumb-nav"));
            }

            $class = ($i === $itemcount - 1) ? "breadcrumb-end" : "breadcrumb-sep";
            $content .=html_writer::tag('div', '', array('class' => $class));

            $htmlblocks[] = $content;
        }

//accessibility: heading for navbar list  (MDL-20446)
        $navbarcontent = html_writer::tag('span', get_string('pagepath'), array('class' => 'accesshide'));
        $navbarcontent .= html_writer::tag('div', join('', $htmlblocks));
// XHTML
        return $navbarcontent;
    }

    /** lädt die verfügbaren Symbole für die Blöcke im Dock in die globale JS-Variable theme_dock_images,
     * auf die das Skript blocks/dock.js zugreift.
     *
     * @global object $CFG
     * @global object $PAGE
     */
    protected function _load_dock_images() {
        global $CFG, $PAGE;

        $dock_images = array("activity_modules", "admin_bookmarks", "blog_menu", "blog_recent",
            "blog_tags", "calendar_month", "calendar_upcoming", "comments", "community",
            "completionstatus", "course_list", "course_overview", "course_summary", "dlb",
            "feedback", "glossary_random", "html", "login", "meinekurse", "meineschulen", "mentees", "messages", "mnet_hosts",
            "myprofile", "navigation", "news_items", "online_users", "participants",
            "private_files", "quiz_results", "quickcourselist", "recent_activity", "rss_client",
            "search_forums", "section_links", "selfcompletion", "settings", "tags");

        $imgpathfallback = "/theme/dlb/pix/blocks/";
        $imgpaththeme = "/theme/" . $PAGE->theme->name . "/pix/blocks/";

        $js_lines = array();

        foreach ($dock_images as $dock_image) {

            $filename = "dock_" . $dock_image . ".png";
            $imgpath = (file_exists($CFG->dirroot . $imgpaththeme . $filename)) ? $imgpaththeme . $filename : $imgpathfallback . $filename;
            $js_lines[] = '"' . $dock_image . '":"' . $CFG->wwwroot . $imgpath . '"';
        }
        ?>
        <script type="text/javascript">

        <?php echo "var theme_dock_images = {" . implode(",\n", $js_lines) . "}"; ?>
        </script>
        <?php
    }

    /** überschreibt die originale Funktion, um die dock-Symbole zu laden */
    public function standard_head_html() {
        return parent::standard_head_html() . $this->_load_dock_images();
    }

}
require_once($CFG->dirroot . '/theme/mebis/renderers/core_renderer.php');
class theme_dlb_core_media_renderer extends core_media_renderer {

    protected function get_players_raw() {
        $ret = parent::get_players_raw();
        $ret += array('mediathek' => new core_media_player_mediathek());
        return $ret;
    }

}

class theme_dlb_core_renderer_maintenance extends core_renderer_maintenance {

    public function toolbar_settings_menu() {
        return '';
    }

    public function toolbar_content() {
        return '';
    }

    public function support_button() {
        return '';
    }

    public function toolbar_loginbutton() {
        return '';
    }

    public function generalheader() {
        return '';
    }

}

/** Note, that there is another constant in block/meineschule, which holds the catdepth of school categories.
 *  if category structure would be changed, both constants must be adapted!
 */
//define('DLB_SCHOOL_CAT_DEPTH', 3); already defined in theme mebis

class theme_dlb_core_course_management_renderer extends core_course_management_renderer {

    /** get (and cache) the category ids below an optional level (level == 3 for school-catgories), where
     *  the user has the capability moodle/category:manage or moodle/course:create
     * 
     * @global type $USER
     * @param type $category
     */
    protected function get_editable_schoolids($level = DLB_SCHOOL_CAT_DEPTH) {
        global $USER, $DB;

        if (!empty($USER->editableschoolids)) {
            return $USER->editableschoolids;
        }

        // get roleids with caps.
        $sql = "SELECT DISTINCT rc.roleid FROM {role_capabilities} rc 
                JOIN {role_context_levels} rcl ON rcl.roleid = rc.roleid
                WHERE rcl.contextlevel = ? and (rc.capability = ? OR rc.capability = ?)";

        $params = array(CONTEXT_COURSECAT, 'moodle/category:manage', 'moodle/course:create');

        if (!$roleids = $DB->get_fieldset_sql($sql, $params)) {
            return array();
        }

        // now get the category ids below that special level.
        list($inroleids, $params) = $DB->get_in_or_equal($roleids);
        $params[] = $USER->id;
        $params[] = CONTEXT_COURSECAT;
        $params[] = $level;

        $sql = "SELECT cat.id, cat.path FROM {context} ctx
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                JOIN {course_categories} cat on ctx.instanceid = cat.id 
                WHERE ra.roleid {$inroleids} and ra.userid = ? and ctx.contextlevel = ? and ctx.depth >= ?";

        if (!$catdata = $DB->get_records_sql($sql, $params)) {
            return array();
        }

        // level of retrieved cats may be higher than school cat (normally level == 3)
        // so retrieve the id of the parent of the school category at level 3.
        $categoryids = array();

        foreach ($catdata as $catdate) {
            $parents = explode('/', $catdate->path);
            if (!empty($parents[$level])) {
                $categoryids[$parents[$level]] = $parents[$level];
            }
        }

        $USER->editableschoolids = $categoryids;

        return $categoryids;
    }

    /** check, wheter this category can be managed, 
     *  i. e. at least one of given editable categories is a parent of this
     *  category or this category is called directly. 
     * 
     * @param object $category, category object.
     * @param array $parentids, list of possible parents.
     * @return boolean, true if one of the parent id is in the parent list of the category.
     */
    protected function can_manage_category($category, $editablecatids) {

        if (empty($category)) {
            return false;
        }

        $catidstocheck = $category->get_parents();

        // possibility to manage main category.
        $catidstocheck[] = $category->id;

        $result = array_intersect($editablecatids, $catidstocheck);

        return (count($result) > 0);
    }

    /**
     * Presents a course category listing.
     *
     * @param coursecat $category The currently selected category. Also the category to highlight in the listing.
     * @return string
     */
    public function category_listing(coursecat $category = null) {
        global $PAGE;
        
        $perfdebug = optional_param('perfdebug', 0, PARAM_INT);
        
        if (optional_param('purge', 0, PARAM_INT) == 1) {
            cache_helper::purge_by_event('changesincoursecat');
            if ($perfdebug) {
                echo "<br/>cache purged";
            }
        }
        $starttime = microtime(true);

        if ($category === null) {
            $selectedparents = array();
            $selectedcategory = null;
        } else {
            $selectedparents = $category->get_parents();
            $selectedparents[] = $category->id;
            $selectedcategory = $category->id;
        }
        $catatlevel = \core_course\management\helper::get_expanded_categories('');
        $catatlevel[] = array_shift($selectedparents);
        $catatlevel = array_unique($catatlevel);

        // +++ awag: get all editable schools //
        $listings = array();

        $datatime = 0;
        $startdatatime = microtime(true);
        // don't restrict the list for site-admins.
        if (is_siteadmin()) {
            
            $listings[] = coursecat::get(0)->get_children();
            
        } else { // non site admins.
            // get schoolids (category of level 3), which contains elements (category, subcategories or courses) this user can edit.
            $editableschoolids = $this->get_editable_schoolids();

            // when required category is not in editable school, redirect the user, when he is no siteadmin.
            $usercanedit = (!empty($editableschoolids) && $this->can_manage_category($category, $editableschoolids));

            if (!$usercanedit) {
                $param = (isset($category)) ? array('categoryid' => $category->id) : array();
                $url = new moodle_url('/course/index.php', $param);
                redirect($url);
            }

            // prepare listings data for rendereing.
            foreach ($editableschoolids as $catid) {

                $coursecat = coursecat::get($catid);

                if (in_array($catid, $selectedparents)) {
                    $catatlevel[] = $catid;
                    $catatlevel = array_unique($catatlevel);
                }
                $listings[] = array($catid => $coursecat);
            }
        }
        $datatime += (microtime(true) - $startdatatime);
        // --- awag;

        $attributes = array(
            'class' => 'ml',
            'role' => 'tree',
            'aria-labelledby' => 'category-listing-title'
        );

        $html = html_writer::start_div('category-listing');
        $html .= html_writer::tag('h3', get_string('categories'), array('id' => 'category-listing-title'));
        $html .= $this->category_listing_actions($category);

        // +++ awag: print out all editable schools, like original renders but in a loop.


        $rendertime = 0;
        
        foreach ($listings as $listing) {

            $html .= html_writer::start_tag('ul', $attributes);
            foreach ($listing as $listitem) {
                // Render each category in the listing.
                $subcategories = array();
                if (in_array($listitem->id, $catatlevel)) {
                    $startdatatime = microtime(true);
                    $subcategories = $listitem->get_children();
                    $datatime += (microtime(true) - $startdatatime);
                }
                $startrendertime = microtime(true);
                $html .= $this->category_listitem(
                        $listitem, $subcategories, $listitem->get_children_count(), $selectedcategory, $selectedparents
                );
                $rendertime += (microtime(true) - $startrendertime);
            }
            $html .= html_writer::end_tag('ul');
        }
        $html .= $this->category_bulk_actions($category);
        $html .= html_writer::end_div();
        
        if ($perfdebug) {
            echo "<br/>category_listing: ".(microtime(true) - $starttime);
            echo "<br/>datatime: ".$datatime;
            echo "<br/>renderttime: ".$rendertime;
        }
        
        return $html;
    }

}

require_once($CFG->dirroot . '/backup/util/ui/renderer.php');

class theme_dlb_core_backup_renderer extends core_backup_renderer {
    
    /**
     * Overrides the backup render, to exclude existing courses form restore
     * form, where the user is NOT having the capability moode/restore:restorecourse
     * in the context of the course.
     * 
     * Update notice: This method is mainly taken form the original renderer with one hook 
     * (see comment).
     *
     * @param moodle_url $nextstageurl
     * @param bool $wholecourse true if we are restoring whole course (as with backup::TYPE_1COURSE), false otherwise
     * @param restore_category_search $categories
     * @param restore_course_search $courses
     * @param int $currentcourse
     * @return string
     */
    public function course_selector(moodle_url $nextstageurl,
                                    $wholecourse = true,
                                    restore_category_search $categories = null,
                                    restore_course_search $courses = null,
                                    $currentcourse = null) {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/course/lib.php');

        // These variables are used to check if the form using this function was submitted.
        $target = optional_param('target', false, PARAM_INT);
        $targetid = optional_param('targetid', null, PARAM_INT);

        // Check if they submitted the form but did not provide all the data we need.
        $missingdata = false;
        if ($target and is_null($targetid)) {
            $missingdata = true;
        }

        $nextstageurl->param('sesskey', sesskey());

        $form = html_writer::start_tag('form', array('method' => 'post', 'action' => $nextstageurl->out_omit_querystring(),
                    'class' => 'mform'));
        foreach ($nextstageurl->params() as $key => $value) {
            $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value));
        }

        $hasrestoreoption = false;

        $html = html_writer::start_tag('div', array('class' => 'backup-course-selector backup-restore'));
        if ($wholecourse && !empty($categories) && ($categories->get_count() > 0 || $categories->get_search())) {
            // New course
            $hasrestoreoption = true;
            $html .= $form;
            $html .= html_writer::start_tag('div', array('class' => 'bcs-new-course backup-section'));
            $html .= $this->output->heading(get_string('restoretonewcourse', 'backup'), 2, array('class' => 'header'));
            $html .= $this->backup_detail_input(get_string('restoretonewcourse', 'backup'), 'radio', 'target', backup::TARGET_NEW_COURSE, array('checked' => 'checked'));
            $selectacategoryhtml = $this->backup_detail_pair(get_string('selectacategory', 'backup'), $this->render($categories));
            // Display the category selection as required if the form was submitted but this data was not supplied.
            if ($missingdata && $target == backup::TARGET_NEW_COURSE) {
                $html .= html_writer::span(get_string('required'), 'error');
                $html .= html_writer::start_tag('fieldset', array('class' => 'error'));
                $html .= $selectacategoryhtml;
                $html .= html_writer::end_tag('fieldset');
            } else {
                $html .= $selectacategoryhtml;
            }
            $html .= $this->backup_detail_pair('', html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('continue'))));
            $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('form');
        }

        if ($wholecourse && !empty($currentcourse)) {
            // Current course
            $hasrestoreoption = true;
            $html .= $form;
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'targetid', 'value' => $currentcourse));
            $html .= html_writer::start_tag('div', array('class' => 'bcs-current-course backup-section'));
            $html .= $this->output->heading(get_string('restoretocurrentcourse', 'backup'), 2, array('class' => 'header'));
            $html .= $this->backup_detail_input(get_string('restoretocurrentcourseadding', 'backup'), 'radio', 'target', backup::TARGET_CURRENT_ADDING, array('checked' => 'checked'));
            $html .= $this->backup_detail_input(get_string('restoretocurrentcoursedeleting', 'backup'), 'radio', 'target', backup::TARGET_CURRENT_DELETING);
            $html .= $this->backup_detail_pair('', html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('continue'))));
            $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('form');
        }

        // If we are restoring an activity, then include the current course.
        if (!$wholecourse) {
            $courses->invalidate_results(); // Clean list of courses.
            $courses->set_include_currentcourse();
        }

        // +++ awag: Hook into this to check courses.
        $courses->require_capability('moodle/course:manageactivities');
        // --- awag: end of Hook.
        
        if (!empty($courses) && ($courses->get_count() > 0 || $courses->get_search())) {
            // Existing course
            $hasrestoreoption = true;
            $html .= $form;
            $html .= html_writer::start_tag('div', array('class' => 'bcs-existing-course backup-section'));
            $html .= $this->output->heading(get_string('restoretoexistingcourse', 'backup'), 2, array('class' => 'header'));
            if ($wholecourse) {
                $html .= $this->backup_detail_input(get_string('restoretoexistingcourseadding', 'backup'), 'radio', 'target', backup::TARGET_EXISTING_ADDING, array('checked' => 'checked'));
                $html .= $this->backup_detail_input(get_string('restoretoexistingcoursedeleting', 'backup'), 'radio', 'target', backup::TARGET_EXISTING_DELETING);
            } else {
                $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'target', 'value' => backup::TARGET_EXISTING_ADDING));
            }
            
            $selectacoursehtml = $this->backup_detail_pair(get_string('selectacourse', 'backup'), $this->render($courses));
            
            // Display the course selection as required if the form was submitted but this data was not supplied.
            if ($missingdata && $target == backup::TARGET_EXISTING_ADDING) {
                $html .= html_writer::span(get_string('required'), 'error');
                $html .= html_writer::start_tag('fieldset', array('class' => 'error'));
                $html .= $selectacoursehtml;
                $html .= html_writer::end_tag('fieldset');
            } else {
                $html .= $selectacoursehtml;
            }
            $html .= $this->backup_detail_pair('', html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('continue'))));
            $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('form');
        }

        if (!$hasrestoreoption) {
            echo $this->output->notification(get_string('norestoreoptions', 'backup'));
        }

        $html .= html_writer::end_tag('div');
        return $html;
    }

}

