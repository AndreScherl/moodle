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
 # @author Andreas Wagner, DLB	andreas.wagner@alp.dillingen.de
 #########################################################################
*/

class theme_dlb_core_renderer extends core_renderer {

    /** im Konstruktor wird geprüft, ob ein zusätzliches Stylesheet geladen werden muss,
     * um die Schriftgröße zu steuern, der Index des Stylesheet wird in der Sessionvariable
     * $_SESSION['MOODLECSSINDEX'] aufgehoben. Welches STylesheet geladen wird kann im
     * DLB-Block konfiguriert werden ($CFG->block_dlb_addacss für IWB-Theme,
     * $CFG->block_dlb_addcss für alle anderen Themes.
     *
     * @global object $CFG
     * @param moodle_page $page
     * @param obejct $target
     * @return none
     */
    public function __construct(moodle_page $page, $target) {
        global $CFG, $USER;

        parent::__construct($page, $target);

        //falls, der User nicht mehr als ein Gast ist nicht das Theme zeigen...
        $currenttheme = (!empty($_SESSION['SESSION']->theme))? $_SESSION['SESSION']->theme : $CFG->theme;
        $isrealuser = (isloggedin() and !isguestuser());

        //Umleitung auf Hilfeseiten ($page->type == 'help') führt wegen fehlender Cookies zu unendlichem Aufruf der Seite
        /*if (!$isrealuser and ($page->pagetype != 'help')) {
            //switch to formal_white
            $_SESSION['SESSION']->theme = 'formal_white';
            redirect($page->url);
        }*/

        //prüfen, ob zusätzliche Stylesheets geladenwerden müssen...

        //1. falls Head-Tag bereits geschlossen wurde, ist Sheet bereits geladen....
        if ($page->requires->is_head_done()) return;

        //2. Index prüfen...
        $cssindex = -1;

        if (isset($_SESSION['MOODLECSSINDEX'])) {
            $cssindex = $_SESSION['MOODLECSSINDEX'];
        }

        if($CFG->theme == 'iwb') {
            if (($cssindex > -1) and isset($CFG->block_dlb_addacss)) {

                $ADDCSS = explode(",",$CFG->block_dlb_addacss);

                if (isset($ADDCSS[$cssindex])) {

                    $addcssfile = '/blocks/dlb/addacss/'.$ADDCSS[$cssindex].'.css';

                    if (file_exists($CFG->dirroot.$addcssfile)) {
                        $page->requires->css($addcssfile);
                    }
                }
            }

        }
        else if($CFG->theme !='iwb') {
            //falls zusätzliche Stylesheets geladen werden sollen, hier tun
            if (($cssindex > -1) and isset($CFG->block_dlb_addcss)) {

                $ADDCSS = explode(",",$CFG->block_dlb_addcss);

                if (isset($ADDCSS[$cssindex])) {

                    $addcssfile = '/blocks/dlb/addcss/'.$ADDCSS[$cssindex].'.css';

                    if (file_exists($CFG->dirroot.$addcssfile)) {
                        $page->requires->css($addcssfile);
                    }
                }
            }

        }

        //Popup-Notifications nicht erlaubt
        $page->set_popup_notification_allowed(false);
    }

    /** überschreibt die originale Funktion, um einen Zeilenumbruch einzufügen  */
    public function login_info($withlinks = NULL) {
        global $USER, $CFG, $DB, $SESSION;

        if (during_initial_install()) {
            return '';
        }

        $loginapge = ((string)$this->page->url === get_login_url());
        $course = $this->page->course;

        if (session_is_loggedinas()) {
            $realuser = session_get_realuser();
            $fullname = fullname($realuser, true);
            $realuserinfo = " [<a href=\"$CFG->wwwroot/course/loginas.php?id=$course->id&amp;sesskey=".sesskey()."\">$fullname</a>] ";
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
            if (is_mnet_remote_user($USER) and $idprovider = $DB->get_record('mnet_host', array('id'=>$USER->mnethostid))) {
                $username .= " from <a href=\"{$idprovider->wwwroot}\">{$idprovider->name}</a>";
            }
            if (isguestuser()) {
                $loggedinas = $realuserinfo.get_string('loggedinasguest');
                if (!$loginapge) {
                    $loggedinas .= " (<a href=\"$loginurl\">".get_string('login').'</a>)';
                }
                //+++ awag, keine Information über Gastlogin, falls der User automatisch eingeloggt wird
                if ($CFG->autologinguests) $loggedinas = "";
                //---
            } else if (is_role_switched($course->id)) { // Has switched roles
                $rolename = '';
                if ($role = $DB->get_record('role', array('id'=>$USER->access['rsw'][$context->path]))) {
                    $rolename = ': '.format_string($role->name);
                }
                $loggedinas = get_string('loggedinas', 'moodle', $username).$rolename.
                        " (<a href=\"$CFG->wwwroot/course/view.php?id=$course->id&amp;switchrole=0&amp;sesskey=".sesskey()."\">".get_string('switchrolereturn').'</a>)';
            } else {
                $loggedinas = $realuserinfo.get_string('loggedinas', 'moodle', $username).' '.
                        " (<a href=\"$CFG->wwwroot/login/logout.php?sesskey=".sesskey()."\">".get_string('logout').'</a>)';
            }
        } else {
            $loggedinas = get_string('loggedinnot', 'moodle');
            if (!$loginapge) {
                $loggedinas .= " (<a href=\"$loginurl\">".get_string('login').'</a>)';
            }
        }

        $loggedinas = '<div class="logininfo">'.$loggedinas.'</div>';

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
                            $loggedinas .= ' (<a href="'.$CFG->wwwroot.'/report/log/index.php'.
                                    '?chooselog=1&amp;id=1&amp;modid=site_errors">'.get_string('logs').'</a>)';
                        }
                        $loggedinas .= '</div>';
                    }
                }
            }
        }
        return $loggedinas;
    }

    /** überschreibt die originale Funktion, um den Blockcode mit zusätzlichen DIVS
     * zu versehen, die für die Abrundungen an den Ecken erforderlich sind
     *
     * @param block_contents $bc
     * @param obejct $region
     * @return String
     */
    function block(block_contents $bc, $region) {

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

        $output .= html_writer::start_tag('div', array("class" =>"bottom"));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');


        $output .= $this->block_annotation($bc);

        $output .= $skipdest;

        $this->init_block_hider_js($bc);
        return $output;
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

        for ($i=0;$i < $itemcount;$i++) {

            $item = $items[$i];
            $item->hideicon = true;

            if ($i===0) {

                $content = html_writer::tag('div', '', array('class'=>"breadcrumb-start"));
                //$content .= html_writer::tag('div', $this->render($item), array('class'=>"breadcrumb-nav"));
                //ersten Breadcrumb (Link auf Startseite) nicht verlinken!
                $content .= html_writer::tag('div', $item->text, array('class'=>"breadcrumb-nav"));

            } else {
                //falls home nicht am Beginn steht auslassen

                $content = html_writer::tag('div', $this->render($item), array('class'=>"breadcrumb-nav"));
            }

            $class = ($i=== $itemcount - 1)? "breadcrumb-end" : "breadcrumb-sep";
            $content .=html_writer::tag('div', '', array('class'=>$class));

            $htmlblocks[] = $content;
        }

        //accessibility: heading for navbar list  (MDL-20446)
        $navbarcontent = html_writer::tag('span', get_string('pagepath'), array('class'=>'accesshide'));
        $navbarcontent .= html_writer::tag('div', join('', $htmlblocks));
        // XHTML
        return $navbarcontent;
    }

    /** erzeugt für das Layout general.php einen Header, der einen kursbereichsspezifischen Title und
     * ein kursbereichsspezifischen Hintergrundbild berücksichtigt.
     * @global object $CFG
     * @global object $PAGE
     * @global object $OUTPUT
     * @return String, der HMTL-Code des Headers
     */
    function generalheader() {
        global $CFG, $PAGE, $OUTPUT;

        $headerdata = array();
        if (file_exists($CFG->dirroot."/blocks/custom_category/block_custom_category.php")) {
            require_once($CFG->dirroot."/blocks/custom_category/block_custom_category.php");
            $headerdata = block_custom_category::get_headerdata();
        }

        $style = (!empty($headerdata->background))? "background-image:url({$headerdata->background})" : "";
        $headline = (!empty($headerdata->headline))? $headerdata->headline : $PAGE->heading;
        $editlink = (!empty($headerdata->editlink))? $OUTPUT->action_icon($headerdata->editlink , new pix_icon('t/edit', get_string('edit'))) : "";
        //header-left
        $headerleft = html_writer::tag('div', '', array("id" => "general-header-left"));

        $strhome = "zur Startseite";
        $content = html_writer::link($CFG->wwwroot, $headerleft, array("alt"=>$strhome, "title" => $strhome));
        //header-right
        $content .= html_writer::tag('div', '', array("style" => $style, "id" => "general-header-right"));
        //header-middle
        $heading = html_writer::tag('h1', $headline);

        $heading = "<table id=\"header-middle-table\"><tr><td>".$heading.$editlink."</td></tr></table>";
        $content .= html_writer::tag('div', $heading, array("id" => "general-header-middle"));


        return html_writer::tag('div', $content, array("id" => "general-header"));
    }

    /** erzeugt den HTML-Code für einen Tooltip in der Toolbar*/
    function toolbar_tooltip($text) {
        return "<div><div class='toolbar-tooltip'><div class='tooltip-left'></div><div class='tooltip-content'>{$text}</div><div class='tooltip-right'></div></div><div style='clear:both'></div></div>";
    }

    /**
     * Return a formatted count of the number of upcoming calendar events, for displaying on the toolbar
     * @return string
     */
    function toolbar_calendarcount() {
        global $CFG;

        require_once($CFG->dirroot.'/calendar/lib.php');

        // Code copied from block_calendar_upcoming
        $defaultlookahead = CALENDAR_DEFAULT_UPCOMING_LOOKAHEAD;
        if (isset($CFG->calendar_lookahead)) {
            $defaultlookahead = intval($CFG->calendar_lookahead);
        }
        $lookahead = get_user_preferences('calendar_lookahead', $defaultlookahead);

        $defaultmaxevents = CALENDAR_DEFAULT_UPCOMING_MAXEVENTS;
        if (isset($CFG->calendar_maxevents)) {
            $defaultmaxevents = intval($CFG->calendar_maxevents);
        }
        $maxevents = get_user_preferences('calendar_maxevents', $defaultmaxevents);

        $filtercourse = calendar_get_default_courses();
        list($courses, $group, $user) = calendar_set_filters($filtercourse);
        $events = calendar_get_upcoming($courses, $group, $user, $lookahead, $maxevents);

        $upcoming = count($events);
        if ($upcoming == 0) {
            return "";
        }

        return "<div><div class='toolbar-toolpop'><div class='toolpop-left'></div><div class='toolpop-content'>{$upcoming}</div><div class='toolpop-right'></div></div><div style='clear:both'></div></div>";
    }

    /** ermittelt die Anzahl der ungelesenen Mitteilungen des aktuell eingeloggte Users und erzeugt den
     * HTML-Code zur Ausgabe in der Toolbar
     * @global object $USER
     * @return String, HMTL-Code zur Anzeige in der Toolbar
     */
    function toolbar_mymessage () {
        global $USER;

        $mymess = message_count_unread_messages($USER);

        if ($mymess == 0) return "";

        return "<div><div class='toolbar-toolpop'><div class='toolpop-left'></div><div class='toolpop-content'>{$mymess}</div><div class='toolpop-right'></div></div><div style='clear:both'></div></div>";
    }

    /**erzeugt den HTML-Code für den Loginbutton in der Toolbar*/
    function toolbar_loginbutton() {
        global $CFG;

        if (isloggedin() and !isguestuser()) {
            $text = get_string('logout');
            $url = "{$CFG->wwwroot}/login/logout.php?sesskey=".sesskey();
        } else {
            $text = get_string('login');
            $url = $CFG->wwwroot."/login/index.php";
        }

        $href = html_writer::link($url, $this->pix_icon('toolbar/toolbar-login', $text, 'theme', array('title'=>'')));
        $content = html_writer::tag('div', $href.$this->toolbar_tooltip($text), array("class" => "toolbar-login-item", "id" => "toolbar-login"));
        return $content;
    }

    function toolbar_themecontent() {
        global $USER, $CFG, $PAGE, $SESSION;

        $content = "";
        $themeswitchurl = "/blocks/dlb/switchtheme/switch.php?returnto=".urlencode($PAGE->url);
        $tooltip = (!empty($SESSION->theme))? "Zum Standard-Theme" : 'Barrierefreies Theme';
        $href = html_writer::link($CFG->wwwroot.$themeswitchurl,  $this->pix_icon('toolbar/toolbar-kontrast', $tooltip, 'theme', array('title'=>'')));
        $content .= html_writer::tag('div', $href.$this->toolbar_tooltip($tooltip), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_6"));

        $fontswitchurl = "/blocks/dlb/switchfont/switch.php?returnto=".urlencode($PAGE->url);
        $href = html_writer::link($CFG->wwwroot.$fontswitchurl."&value=1",  $this->pix_icon('toolbar/toolbar-groesse1', 'Text größer', 'theme', array('title'=>'')));
        $content .= html_writer::tag('div', $href.$this->toolbar_tooltip('Text größer'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_7"));

        $href = html_writer::link($CFG->wwwroot.$fontswitchurl."&value=0",  $this->pix_icon('toolbar/toolbar-groesse2', 'Text Standardgröße', 'theme', array('title'=>'')));
        $content .= html_writer::tag('div', $href.$this->toolbar_tooltip('Text Standardgröße'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_8"));

        $href = html_writer::link($CFG->wwwroot.$fontswitchurl."&value=-1",  $this->pix_icon('toolbar/toolbar-groesse3', 'Text kleiner', 'theme', array('title'=>'')));
        $content .= html_writer::tag('div', $href.$this->toolbar_tooltip('Text kleiner'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_9"));

        /*$mylink =$CFG->wwwroot."/theme/dlb/help/help.php";

        $actionlink = $this->action_link($mylink, $this->pix_icon('toolbar/toolbar-hilfe','Hilfe', 'theme', array('title'=>'')),new popup_action('click', $mylink,  'Help',array('height' => '400','width' => '500','top' => 0,'left' => 0,'menubar' => false,'location' => false,'scrollbars' => true,'resizable' => false,'toolbar' => false,'status' => false,'directories' => false,'fullscreen' => false,'dependent' => true)) );

        $content .= html_writer::tag('div', $actionlink.$this->toolbar_tooltip('Hilfe'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_10"));

$contentreader ="<script type='text/javascript'><!--
vrweb_icon = '01';
vrweb_iconcolor = 'grey';
vrweb_guilang = 'de';
vrweb_lang = 'de-de';
vrweb_srctype = 'html';
vrweb_readcontent = 'text';
vrweb_srccharset = 'utf8';
vrweb_sitetopic = '';
vrweb_simpleparse = '0';
vrweb_readelementsname = '';
vrweb_readelementsclass = '';
vrweb_readelementsid = '';
vrweb_exclelementsname = '';
vrweb_exclelementsclass = '';
vrweb_exclelementsid = '';
vrweb_customerid = '11384';
vrweb_cache = '0';
vrweb_sndtype = '1';
vrweb_sndquality = '4';
vrweb_sndspeed = '100';
vrweb_sndpitch = '100';
vrweb_sndgender = 'W';
vrweb_brhandling = '0';
//--></script>
<script type='text/javascript' src='http://vrweb.linguatec.net/javascripts/services/vrweb/readpremium2.js'></script>";
        $content .= html_writer::tag('div', $contentreader, array("class" => "toolbar-content-item", "id" => "toolbar-content-item_10"));*/

        $content .= "<div style=\"clear:both\"></div>";

        return $content;
    }

    /** erzeugt den HTML-Code für alle restlichen Element der Toolbar */
    function toolbar_content() {
        global $USER, $CFG, $PAGE, $SESSION;

        $content = "";

        if (isloggedin() and !isguestuser()) {

            $href = html_writer::link($CFG->wwwroot."/my",  $this->pix_icon('toolbar/toolbar-schreibtisch', 'Mein Schreibtisch', 'theme', array('title'=>'')));
            $content .= html_writer::tag('div', $href.$this->toolbar_tooltip('Meine Startseite'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_2"));

            $href = html_writer::link($CFG->wwwroot."/user/profile.php?id={$USER->id}",  $this->pix_icon('toolbar/toolbar-profil', 'Profil', 'theme', array('title'=>'')));
            $content .= html_writer::tag('div', $href.$this->toolbar_tooltip('Profil'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_0"));

            /* awag: Portfolio für später vorbereitet...
        $href = html_writer::link("",  $this->pix_icon('toolbar/toolbar-portfolio', 'Portfolio', 'theme', array('title'=>'')));
        $content .= html_writer::tag('div', $href.$this->toolbar_tooltip('Portfolio'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_1"));
            */

            $href = html_writer::link($CFG->wwwroot."/calendar/view.php?view=month",  $this->toolbar_calendarcount().$this->pix_icon('toolbar/toolbar-calendar', 'Kalender', 'theme', array('title'=>'')));
            $content .= html_writer::tag('div', $href.$this->toolbar_tooltip('Kalender'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_3"));

            $href = html_writer::link($CFG->wwwroot."/message/index.php",  $this->toolbar_mymessage().$this->pix_icon('toolbar/toolbar-mitteilungen', 'Mitteilungen', 'theme', array('title'=>'')));

            $content .= html_writer::tag('div', $href.$this->toolbar_tooltip('Mitteilungen'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_4"));

            $context = get_context_instance(CONTEXT_USER, $USER->id);
            if (has_capability('moodle/user:manageownfiles', $context)) {
                $href = html_writer::link($CFG->wwwroot."/user/files.php",  $this->pix_icon('toolbar/toolbar-dateien', 'Dateien', 'theme', array('title'=>'')));
                $content .= html_writer::tag('div', $href.$this->toolbar_tooltip('Eigene Dateien'), array("class" => "toolbar-content-item", "id" => "toolbar-content-item_5"));
            }
        }

        //Themeumschalter
        $content .= $this->toolbar_themecontent();

        $content = html_writer::tag('div', $content, array("id"=>"toolbar-content"));
        return $content;
    }

    function support_button() {
        global $USER, $DB, $CFG, $SESSION;

        if (!isloggedin() or isguestuser() or empty($CFG->block_dlb_supporturl)) return "";

	if (isset($SESSION->isTeacher)) {
	    $USER->isTeacher = $SESSION->isTeacher;
	    unset($SESSION->isTeacher);
        }

        $content = "";
        if (isset($USER->isTeacher) and ($USER->isTeacher)) {

            /*$content .= html_writer::link($CFG->block_dlb_supporturl,  get_string('support', 'block_dlb'), array('target' => '_blank'));
            $content = "<div id='supportbutton'><div class='toolbar-bsupport'><div class='bsupport-left'></div><div class='bsupport-content'>{$content}</div><div class='bsupport-right'></div></div><div style='clear:both'></div></div>";
            $content .= "<div style=\"clear:both\"></div>";*/

            $mylink =$CFG->block_dlb_supporturl;

            $actionlink = $this->action_link($mylink, $this->pix_icon('toolbar/support','Support', 'theme', array('title'=>'')),new popup_action('click', $mylink,  'Help',array('height' => '400','width' => '960','top' => 0,'left' => 0,'menubar' => false,'location' => false,'scrollbars' => true,'resizable' => false,'toolbar' => false,'status' => false,'directories' => false,'fullscreen' => false,'dependent' => true)) );

            $content .= html_writer::tag('div', $actionlink, array("class" => "toolbar-content-item", "id" => "toolbar-content-item_10"));
            $content .= "<div style=\"clear:both\"></div>";



        }
        return $content;
    }

    /** lädt die verfügbaren Symbole für die Blöcke im Dock in die globale JS-Variable theme_dock_images,
     * auf die das Skript blocks/dock.js zugreift.
     *
     * @global object $CFG
     * @global object $PAGE
     */
    function _load_dock_images() {
        global $CFG, $PAGE;

        $dock_images = array("activity_modules", "admin_bookmarks", "blog_menu", "blog_recent",
                "blog_tags", "calendar_month", "calendar_upcoming", "comments", "community",
                "completionstatus",  "course_list", "course_overview", "course_summary", "dlb",
                "feedback", "glossary_random", "html", "login", "meinekurse","mentees", "messages", "mnet_hosts",
                "myprofile", "navigation", "news_items" , "online_users", "participants",
                "private_files", "quiz_results", "quickcourselist", "recent_activity", "rss_client",
                "search_forums", "section_links", "selfcompletion" , "settings", "tags");

        $imgpathfallback = "/theme/dlb/pix/blocks/";
        $imgpaththeme = "/theme/".$PAGE->theme->name."/pix/blocks/";

        $js_lines = array();

        foreach ($dock_images as $dock_image) {

            $filename = "dock_".$dock_image.".png";
            $imgpath = (file_exists($CFG->dirroot.$imgpaththeme.$filename))? $imgpaththeme.$filename : $imgpathfallback.$filename;
            $js_lines[] =  '"block_'.$dock_image.'":"'.$CFG->wwwroot.$imgpath.'"';

        }
        ?>
<script type="text/javascript">

        <?php echo "var theme_dock_images = {".implode(",\n", $js_lines)."}"; ?>
</script>
        <?php
    }

    /** überschreibt die originale Funktion, um die dock-Symbole zu laden */
    public function standard_head_html() {
        return parent::standard_head_html().$this->_load_dock_images();
    }

    public function pagecontent_footer () {
        global $CFG;
        
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
