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

        //überprüft, ob das Attribut $USER->isTeacher gesetzt ist, falls nicht wird der Wert gesetzt.
        $this->check_user_isTeacher();

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

    /** überprüfen, ob der User ein Lehrer ist, speichern des Ergebnisses für die
     * Dauer der Session im Attribut $USER->isTeacher
     *
     * @global object $USER
     * @global type $SESSION
     * @global type $DB
     * @return boolean, true falls der User als Lehrer gilt.
     */
    protected function check_user_isTeacher() {
        global $USER, $SESSION, $DB;

        //nur echte User zulassen....
        if (!isloggedin() or isguestuser())
            return false;

        if (isset($USER->isTeacher))
            return $USER->isTeacher;

        //für Shibboleth und LDAP-USER SESSION überprüfen und übernehmen.
        if (isset($SESSION->isTeacher)) {
            $USER->isTeacher = $SESSION->isTeacher;
            return $USER->isTeacher;
        }

        //prüfen, ob der User in irgend einem Kurs die Lehrerrolle hat.
        $roles = get_roles_with_capability('enrol/self:config');
        list($rsql, $params) = $DB->get_in_or_equal(array_keys($roles), SQL_PARAMS_NAMED);
        $params['userid'] = $USER->id;
        $sql = "SELECT ra.id
                              FROM {role_assignments} ra
                             WHERE ra.roleid $rsql
                               AND ra.userid = :userid";

        $USER->isTeacher = $DB->record_exists_sql($sql, $params);

        return $USER->isTeacher;
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
        global $USER, $CFG, $PAGE, $SESSION;

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
        global $USER, $CFG, $PAGE, $SESSION;

        $content = "";

        if (isloggedin() and !isguestuser()) {

            $href = html_writer::link($CFG->wwwroot . "/my", $this->pix_icon('toolbar/toolbar-schreibtisch', 'Mein Schreibtisch', 'theme', array('title' => '')));
            $content .= html_writer::tag('div', $href . $this->toolbar_tooltip('Meine Startseite'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_2"));

           /*atar: Kursbereichsicon vorerst deaktiviert*/
            $href = html_writer::link($CFG->wwwroot . "/blocks/meineschulen/search.php", $this->pix_icon('toolbar/toolbar-schulesuchen', 'Schule suchen', 'theme', array('title' => '')));
            $content .= html_writer::tag('div', $href . $this->toolbar_tooltip('Schule suchen'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_11"));


            $href = html_writer::link($CFG->wwwroot . "/user/profile.php?id={$USER->id}", $this->pix_icon('toolbar/toolbar-profil', 'Profil', 'theme', array('title' => '')));
            $content .= html_writer::tag('div', $href . $this->toolbar_tooltip('Profil'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_0"));

            /* awag: Portfolio für später vorbereitet...
              $href = html_writer::link("",  $this->pix_icon('toolbar/toolbar-portfolio', 'Portfolio', 'theme', array('title'=>'')));
              $content .= html_writer::tag('div', $href.$this->toolbar_tooltip('Portfolio'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_1"));
             */

            $href = html_writer::link($CFG->wwwroot . "/calendar/view.php?view=month", $this->pix_icon('toolbar/toolbar-calendar', 'Kalender', 'theme', array('title' => '')));
            $content .= html_writer::tag('div', $href . $this->toolbar_tooltip('Kalender'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_3"));

            $href = html_writer::link($CFG->wwwroot . "/message/index.php", $this->toolbar_mymessage() . $this->pix_icon('toolbar/toolbar-mitteilungen', 'Mitteilungen', 'theme', array('title' => '')));

            $content .= html_writer::tag('div', $href . $this->toolbar_tooltip('Mitteilungen'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_4"));

            $context = get_context_instance(CONTEXT_USER, $USER->id);
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

    /** prüft, ob der User den Support-Button sehen darf und merkt sich das Ergebnis
     *  im Attribut $USER->canseesupportbutton
     *
     * @global object $USER
     * @global type $DB
     * @global object $CFG
     * @return boolean
     */
    protected function can_see_supportbutton() {

        global $USER, $DB, $CFG;

 if (!isloggedin() or isguestuser() or empty($CFG->block_dlb_supporturl))
            return false;

        if (isset($USER->canseesupportbutton))
            return $USER->canseesupportbutton;

        if ($USER->isTeacher) {

            $USER->canseesupportbutton = true;
            return true;
        }

        if (!empty($CFG->block_dlb_rolestosupport)) {

            $sql = "SELECT count(*) as count FROM {role_assignments} Where roleid in ({$CFG->block_dlb_rolestosupport}) and userid = '{$USER->id}'";
            $count = $DB->count_records_sql($sql);

            //wird für die Dauer der SESSION gecacht.
            $USER->canseesupportbutton = ($count > 0);
        }
        return $USER->canseesupportbutton;
    }

    /** gibt den HTML-Code des Support-button zurück, falls der User diesen sehen darf*/
    public function support_button() {
        global $USER, $DB, $CFG;

       $content = "";
                if (!isloggedin() or isguestuser() or empty($CFG->block_dlb_supporturl)){


             $outlink = new moodle_url('https://lernplattform.mebis.bayern.de/support/course/view.php?id=51');

            $actionlink = $this->action_link($outlink, $this->pix_icon('toolbar/support', 'Support', 'theme', array('title' => '')), new popup_action('click', $outlink, 'Help', array('height' => '400', 'width' => '500', 'top' => 0, 'left' => 0, 'menubar' => false, 'location' => false, 'scrollbars' => true, 'resizable' => false, 'toolbar' => false, 'status' => false, 'directories' => false, 'fullscreen' => false, 'dependent' => true)));

            $content .= html_writer::tag('div', $actionlink . $this->toolbar_tooltip('Support'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_10"));
            $content .= "<div style=\"clear:both\"></div>";


        }elseif (isloggedin()&&$this->can_see_supportbutton()) {

            $mylink = $CFG->block_dlb_supporturl;

            $actionlink = $this->action_link($mylink, $this->pix_icon('toolbar/support', 'Support', 'theme', array('title' => '')), new popup_action('click', $mylink, 'Help', array('height' => '400', 'width' => '500', 'top' => 0, 'left' => 0, 'menubar' => false, 'location' => false, 'scrollbars' => true, 'resizable' => false, 'toolbar' => false, 'status' => false, 'directories' => false, 'fullscreen' => false, 'dependent' => true)));

            $content .= html_writer::tag('div', $actionlink . $this->toolbar_tooltip('Support'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_10"));
            $content .= "<div style=\"clear:both\"></div>";
        }
        return $content;
    }

    /** gibt die Links auf die Institutionen zurück */
    public function pagecontent_footer() {
        global $CFG;
       if (isloggedin()){

        $content = "";
       }
       else{
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
                    <div class="logo_stmuk" alt="Link zur Homepage des Bayerischen Staatsministeriums für Unterricht und Kultus " title="Link zur Homepage des Bayerischen Staatsministeriums für Unterricht und Kultus ">
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

        $loginapge = ((string) $this->page->url === get_login_url());
        $course = $this->page->course;

        if (session_is_loggedinas()) {
            $realuser = session_get_realuser();
            $fullname = fullname($realuser, true);
            $realuserinfo = " [<a href=\"$CFG->wwwroot/course/loginas.php?id=$course->id&amp;sesskey=" . sesskey() . "\">$fullname</a>] ";
        } else {
            $realuserinfo = '';
        }

        $loginurl = get_login_url();

        if (empty($course->id)) {
            // $course->id is not defined during installation
            return '';
        } else if (isloggedin()) {
            $context = get_context_instance(CONTEXT_COURSE, $course->id);

            $fullname = fullname($USER, true);
            // Since Moodle 2.0 this link always goes to the public profile page (not the course profile page)
            //+++ awag: hier einen <br />-Tag eingefügt...
            $username = "<br /><a href=\"$CFG->wwwroot/user/profile.php?id=$USER->id\">$fullname</a>";
            //--- awag ---
            if (is_mnet_remote_user($USER) and $idprovider = $DB->get_record('mnet_host', array('id' => $USER->mnethostid))) {
                $username .= " from <a href=\"{$idprovider->wwwroot}\">{$idprovider->name}</a>";
            }
            if (isguestuser()) {
                $loggedinas = $realuserinfo . get_string('loggedinasguest');
                if (!$loginapge) {
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
                        if (file_exists("$CFG->dirroot/report/log/index.php") and has_capability('report/log:view', get_context_instance(CONTEXT_SYSTEM))) {
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
            $js_lines[] = '"block_' . $dock_image . '":"' . $CFG->wwwroot . $imgpath . '"';
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



// The following code embeds the mediathek player in the 'preview' page when inserting video/audion
require_once($CFG->libdir . '/medialib.php');

class core_media_player_mediathek extends core_media_player_external {

    protected function embed_external(moodle_url $url, $name, $width, $height, $options) {
        global $DB;
        $hash = $this->matches[1];
        if ($desturl = $DB->get_field('repository_mediathek_link', 'url', array('hash' => $hash))) {
            return '<iframe style="height:300px;width:400px;" src="' . $desturl . '"></iframe>';
        }

        return core_media_player::PLACEHOLDER;
    }

    protected function get_regex() {
        global $CFG;
        $basepath = preg_quote("{$CFG->wwwroot}/repository/mediathek/link.php?hash=");
        $regex = "%{$basepath}([a-z0-9]*)(&|&amp;)embed=1%";
        return $regex;
    }

    public function get_rank() {
        return 1020;
    }

    public function get_embeddable_markers() {
        return array('repository/mediathek/link.php');
    }

    public function is_enabled() {
        return true;
    }
}

class theme_dlb_core_media_renderer extends core_media_renderer {

    protected function get_players_raw() {
        $ret = parent::get_players_raw();
        $ret += array('mediathek' => new core_media_player_mediathek());
        return $ret;
    }
}