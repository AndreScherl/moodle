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
  # @author Andrea Taras, DLB andrea.taras@alp.dillingen.de
  #########################################################################
 */

class datenschutz {

    var $userids_together_in_course;

    /** Singleton: Aus Performancegründen wird eine Instanz der Klasse Datenschutz erzeugt. Damit
     * wird das Ergebnis der Abfrage in _get_userids_together_in_course gecacht.
     *
     * @staticvar datenschutz $datenschutz
     * @return datenschutz, Instanz der Klasse datenschutz
     */
    public static function getInstance() {
        static $datenschutz;

        if (isset($datenschutz))
            return $datenschutz;

        $datenschutz = new datenschutz();
        return $datenschutz;
    }

    /** bricht das Skript mit einer Fehlermeldung ab, falls der eingeloggte User nicht
     * das Recht hat User anderer Schulen (Feld Institution) zu sehen und der User zur übergebenen
     * User-ID nicht den gleichen Wert im Feld Schule (Institution) hat
     *
     * @global moodle_database $DB
     * @global object $USER
     * @param int $userid
     * @return void
     */
    private static function _require_same_institution($userid) {
        global $DB, $USER;

//neuer User wird angelegt
        if ($userid == -1)
            return;

//User bearbeitet eigenes Formular
        if ($userid == $USER->id)
            return;

//falls das erforderliche Recht existiert weiter zum original Skript
        if (has_capability("block/dlb:institutionview", get_system_context()))
            return;

//Gültigkeitsprüfung ist bereits erfolgt!
        $user = $DB->get_record('user', array('id' => $userid));

        if (empty($USER->institution)) {
            print_error('noinstitutionerror', 'block_dlb');
        }

        if (($USER->institution != $user->institution)) {
            print_error('nopermissiontoedituser', 'block_dlb');
        }
    }

    /** bricht das Skript mit einer Fehlermeldung ab, falls der eingeloggte User nicht
     * das Recht hat User anderer Schulen (Feld Institution) zu sehen und der User zur übergebenen
     * User-ID nicht den gleichen Wert im Feld Schule (Institution) hat
     *
     * @global moodle_database $DB
     * @global object $USER
     * @param int $userid
     * @return void
     */
    private static function _require_cap_to_view_user($userid) {
        global $DB, $USER;

//User bearbeitet eigenes Formular
        if ($userid == $USER->id)
            return;

//falls das erforderliche Recht existiert weiter zum original Skript
        if (has_capability("block/dlb:institutionview", get_system_context()))
            return;

//Gültigkeitsprüfung ist bereits erfolgt!
        $user = $DB->get_record('user', array('id' => $userid));

        if (empty($USER->institution)) {
            print_error('noinstitutionerror', 'block_dlb');
        }

        if ($USER->institution == $user->institution)
            return;

//falls $USER und $userid in gleichem Kurs sind, ok
        $datenschutz = datenschutz::getInstance();
        $usertogether = $datenschutz->_get_userids_together_in_course($userid);
        if (in_array($USER->id, array_keys($usertogether)))
            return;

        print_error('nopermissiontoviewuser', 'block_dlb');
    }

    /** gibt alle Ids der User, die mit diesem User gemeinsam in einen Kurs
     * eingeschrieben sind.
     *
     * @global moodle_database $DB
     * @param int $userid, die ID des Users
     * @return object[], recordset bestehend aus userids
     */
    private function _get_userids_together_in_course($userid) {
        global $DB;

        if (isset($this->userids_together_in_course))
            return $this->userids_together_in_course;

        $sql = "SELECT DISTINCT userid FROM {user_enrolments} ue " .
                "JOIN {enrol} e ON e.id = ue.enrolid " .
                "WHERE courseid in (" .
                "SELECT courseid FROM {user_enrolments} ue " .
                "JOIN {enrol} e ON e.id = ue.enrolid where userid = :userid)";

        $this->userids_together_in_course = $DB->get_records_sql($sql, array("userid" => $userid));
        return $this->userids_together_in_course;
    }

    /** ändert die $wherecondition so ab, dass die vereinbarte Sichtbarkeitsregel
     * eingehalten wird.
     *
     * @global object $USER, der aktuelle User
     * @param string $wherecondition, die Bedingung des SQL-Statement zur Usersuche
     * @param string $tablealias
     * @return string
     */
    private static function _addInstitutionFilter($wherecondition = "", $tablealias = "", $strictinstitution = false) {
        global $USER;

//Wenn $USER über Institutsgrenzen hinaus sehen kann, nichts ändern
        if (has_capability("block/dlb:institutionview", get_system_context()))
            return $wherecondition;

        if (empty($USER->institution)) {
            print_error('noinstitutionerror', 'block_dlb');
        }

        if (!empty($wherecondition))
            $wherecondition .= " AND ";

        if ($strictinstitution) {
            $wherecondition .= " ({$tablealias}institution = '{$USER->institution}')";
            return $wherecondition;
        }

//Wenn $USER nicht das Recht hat über Institutsgrenzen hinaus zu sehen
//muss das Feld Institution gleich sein oder der die User belegen gemeinsam einen Kurs
        $datenschutz = datenschutz::getInstance();
        $userids = $datenschutz->_get_userids_together_in_course($USER->id);

        if ($userids) {

            $userids = array_keys($userids);
            $wherecondition .= " (({$tablealias}institution = '{$USER->institution}') or {$tablealias}id IN (" . implode(",", $userids) . ")) ";
            return $wherecondition;
        } else {

            $wherecondition .= " ({$tablealias}institution = '{$USER->institution}')";
            return $wherecondition;
        }
    }

    /*     * **************************************************************************************
     * nachfolgend sind alle verwendeten Corecode-Hacks gelistet.
     * Die Funktionsbezeichnung wird nach der Position des Hacks gebildet:
     *
     * z. B. für einen Hack in der Datei /enrol/locallib.php beginnt die Funktionsbezeichnung
     * mit "hook_enrol_locallib_"
     * ************************************************************************************** */

    /** @HOOK DS01: Hook in enrol/locallib.php course_enrolment_manager->get_potential_users()
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der Rollenzuweisung im Kurs
     * (unter Verwendung von AJAX) sieht
     *
     * @param String $wherecondition
     * @return string
     */
    public static function hook_enrol_locallib_get_potential_users($wherecondition) {
        return datenschutz::_addInstitutionFilter($wherecondition, "u.");
    }

    /** @HOOK DS02: Hook in enrol/manual/locallib.php enrol_manual_potential_participant->find_users()
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der Rollenzuweisung im Kurs
     * (ohne Verwendung von AJAX) sieht
     *
     * @param String $wherecondition
     * @return string
     */
    public static function hook_enrol_manual_locallib_find_users($wherecondition) {
        return datenschutz::_addInstitutionFilter($wherecondition, "u.");
    }

    /** @HOOK DS03: Hook in admin/user.php
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der globalen Nutzerverwaltung sieht
     *
     * @param String $wherecondition
     * @return string
     */
    public static function hook_admin_user_get_extrasql($wherecondition) {
        return datenschutz::_addInstitutionFilter($wherecondition, "", true);
    }

    /** @HOOK DS04: Hook in admin/user.php
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die <b>Gesamtanzahl der User</b> mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der globalen Nutzerverwaltung sieht
     */
    public static function hook_admin_user_get_extrasqlusercount() {
        return datenschutz::_addInstitutionFilter("", "", true);
    }

    /** DS05: Hook in local/user/editadvanced.php
     * prüft, ob der eingeloggte User zur Bearbeitung des Users mit der ID $usertoedit
     * berechtigt ist. Ist dies nicht der Fall, so wird mit einer Fehlermeldung abgebrochen.
     *
     * Durch die Verwendung eines "localized Scripts" (local/...) wird diese Prüfung
     * vor der Verarbeitung des originalen Skripts user/editadvanced.php aufgerufen
     *
     * @param int $useridtoedit, die ID des zu bearbeitenden Users
     */
    public static function hook_local_user_editadvanced_require_same_institution($useridtoedit) {
        datenschutz::_require_same_institution($useridtoedit);
    }

    /** DS06: Hook in local/user/edit.php
     * prüft, ob der eingeloggte User zur Bearbeitung des Users mit der ID $usertoedit
     * berechtigt ist. Ist dies nicht der Fall, so wird mit einer Fehlermeldung abgebrochen.
     *
     * Durch die Verwendung eines "localized Scripts" (local/...) wird diese Prüfung
     * vor der Verarbeitung des originalen Skripts user/edit.php aufgerufen
     *
     * @param int $useridtoedit, die ID des zu bearbeitenden Users
     */
    public static function hook_local_user_edit_require_same_institution($useridtoedit) {
        datenschutz::_require_same_institution($useridtoedit);
    }

    /** @HOOK DS07: Hook in admin/roles/lib.php
     * potential_assignees_course_and_above->get_potential_users()
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der Rollenzuweisung außerhalb des Kurses
     *
     * @param String $wherecondition
     * @return string
     */
    public static function hook_admin_roles_lib_find_users($wherecondition) {
        return datenschutz::_addInstitutionFilter($wherecondition);
    }

    /** @HOOK DS08: Hook in cohort/lib.php
     * cohort_candidate_selector->find_users()
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der Rollenzuweisung zu einer Kohorte sieht
     *
     * @param String $wherecondition
     * @return string
     */
    public static function hook_cohort_lib_find_users($wherecondition) {
        return datenschutz::_addInstitutionFilter($wherecondition, "u.");
    }

    /** @HOOK DS09: Hook in message/lib.php in der Funktion message_search_users()
     *
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der Suche nach Kontakten sieht
     *
     * @return string
     */
    public static function hook_message_lib_message_search_users() {
        $wherecondition = datenschutz::_addInstitutionFilter("", "u.");
        $wherecondition = (!empty($wherecondition)) ? " AND " . $wherecondition : "";
        return $wherecondition;
    }

    /** @HOOK DS10: Hook in user/profile/lib.php
     *
     * blendet das Beschreibungsfeld aus.
     * deaktiviert die Uploadmöglichkeit für Bilder
     * =>Verhinderung nach dem Submit siehe DS20
     *
     * sperrt die Bearbeitung des Feldes Schule (Institution) für Nicht Admins
     *
     * @param moodle_form $mform
     * @param mixed, false oder 0 bei neuem User, sonst Userid des bearbeiteten Users.
     */
    public static function hook_profile_definition_after_data(&$mform, $userid) {
        global $CFG, $DB;

        //Editor für alle entfernen => nicht sichtbar
        if ($mform->elementExists('description_editor')) {

            $mform->hardFreeze('description_editor');
            $mform->addElement('hidden', 'description_editor[text]', '');
            $mform->addElement('hidden', 'description_editor[format]', '0');
            $mform->setConstants('description_editor[text]');
            $mform->setConstants('description_editor[format]');
        }

        //admin hat Zugriff auf alle Felder...
        if (has_capability("moodle/site:config", get_system_context()))
            return;

        //Profilfeld schule für bestehende User sperren.
        $user = $DB->get_record('user', array('id' => $userid)); //$user ist der bearbeitete User (!= $USER)

        if (!empty($CFG->bm_school_field) && ($user)) {// für bestehenden User das Schulfeld nicht ändern.
            //Falls das Recht nicht besteht über Institutsgrenzen hinauszusehen, darf Profilfeld schule nicht verändert werden.
            $schulfeldname = "profile_field_" . $CFG->bm_school_field;

            if ($mform->elementExists($schulfeldname)) {

                $sql = "SELECT data FROM {user_info_data} as id " .
                        "JOIN {user_info_field} inf ON inf.id = id.fieldid " .
                        "WHERE id.userid = :userid and inf.name = :fieldname";

                $schule = $DB->get_field_sql($sql, array('userid' => $user->id, 'fieldname' => $CFG->bm_school_field));

                $mform->hardFreeze($schulfeldname);
                $mform->setConstants($schulfeldname, $schule);
            }
        }

        //weitere gesperrte Felder für bereits angelegte User....
        if ($user) {

            if ($mform->elementExists('city')) {
                //ersetzt Inputfeld durch Anzeige
                $mform->hardFreeze('city');
                //macht den Submit unüberschreibbar, auch bei Formularmanipulationen
                //z. B. Einfügen von <input id="id_city" name="city" value="hack" />
                $mform->setConstants(array('city' => $user->city));
            }

            if ($mform->elementExists('institution')) {
                //ersetzt Inputfeld durch Anzeige
                $mform->hardFreeze('institution');
                //macht den Submit unüberschreibbar, auch bei Formularmanipulationen
                $mform->setConstants(array('institution' => $user->institution));
            }

            if ($mform->elementExists('idnumber')) {
                //ersetzt Inputfeld durch Anzeige
                $mform->hardFreeze('idnumber');
                //macht den Submit unüberschreibbar, auch bei Formularmanipulationen
                $mform->setConstants(array('idnumber' => $user->idnumber));
            }
        }
        //falls kein username angezeigt wird, Information darüber einfügen
        if (!$mform->elementExists('username')) {
            $username = $mform->createElement('static', 'username', get_string('username'));
            $mform->insertElementBefore($username, 'firstname');
        }
        
        // Felder für User, die editadvanced aufrufen können weil sie das Recht
        // moodle/user:update haben, aber keine Admins sind, trotzdem sperren.
        if (has_capability('moodle/user:update', context_system::instance())) {
            
            //Anmeldenamen schützen
            if ($mform->elementExists('username')) {
                //ersetzt Inputfeld durch Anzeige
                $mform->hardFreeze('username');
                //macht den Submit unüberschreibbar, auch bei Formularmanipulationen
                $mform->setConstants(array('username' => $user->username));
            }
            
            //restliche Einstellungen des Auth-Plugins schützen
            $fields = get_user_fieldnames();
            $authplugin = get_auth_plugin($user->auth);
            foreach ($fields as $field) {
                if (!$mform->elementExists($field)) {
                    continue;
                }
                $configvariable = 'field_lock_' . $field;
                if (isset($authplugin->config->{$configvariable})) {
                    if ($authplugin->config->{$configvariable} === 'locked') {
                        $mform->hardFreeze($field);
                        $mform->setConstant($field, $user->$field);
                    } else if ($authplugin->config->{$configvariable} === 'unlockedifempty' and $user->$field != '') {
                        $mform->hardFreeze($field);
                        $mform->setConstant($field, $user->$field);
                    }
                }
            }
        }
    }

    /** @HOOK DS11: Hook in mod/chat/mod_form.php
     * entfernt aus der Liste der Optionen für die Löschungfristen die Option "niemals löschen"
     *
     * @param array $options, die Optionen der Löschungsfristen von Chatprotokollen
     */
    public static function hook_mod_chat_mod_form_definition(&$options) {
        unset($options[0]);
    }

    /** @HOOK DS12: Hook in report/outline/lib.php
     * verhindert die Anzeige von Navigationslinks aus personenbezogene Berichte im Kontext des Kurses
     *
     * @return bool, muss false zurückgeben, falls die personenbezogenen Berichte nicht angezeigt werden sollen.
     */
    public static function hook_report_outline_lib_report_outline_can_access_user_report() {
        return has_capability("moodle/site:config", get_system_context());
    }

    /** @HOOK DS13: Hook in local/report/outline/user.php
     * verhindert den direkten Aufruf des personenbezogenen Berichtes im Kontext
     * eines Kurses für Nicht-Admins
     */
    public static function hook_local_report_outline_user_require_access_user_report() {
        if (!has_capability("moodle/site:config", get_system_context())) {
            print_error('notallowedtoaccessuserreport', 'block_dlb');
        }
    }

    /** @HOOK DS14: Hook in local/course/recent.php
     * verhindert den direkten Aufruf der Austellung vergangener Aktivitäten für Nicht-Admins
     */
    public static function hook_local_course_recent_require_access_recent_activities() {
        if (!has_capability("moodle/site:config", get_system_context())) {
            print_error('notallowedtoaccessrecentactivities', 'block_dlb');
        }
    }

    /** @HOOK DS15: Hook in course/lib.php
     * prüft, ob der User den Link zum Auswertungsformular vergangener Aktivitäten sehen darf
     *
     * @return bool, muss false zurückgeben wenn der Link nicht angezeigt werden soll.
     */
    public static function hook_course_lib_can_access_recent_activities() {
        return has_capability("moodle/site:config", get_system_context());
    }

    /** @HOOK DS 16: Hook in admin/settings/user
     * verbirgt die Bulk verwaltung für User, die nicht das Recht haben über Schulgrenzen hinaus zu sehen
     */
    public static function hook_admin_users_hide_bulk(&$ADMIN) {
        global $CFG;

        $systemcontext = context_system::instance();
        if (has_capability("moodle/site:config", $systemcontext) or
                (has_capability("block/dlb:institutionview", $systemcontext))) {
            $ADMIN->add('accounts', new admin_externalpage('userbulk', get_string('userbulk', 'admin'), "$CFG->wwwroot/$CFG->admin/user/user_bulk.php", array('moodle/user:update', 'moodle/user:delete')));
        }
    }

    /** @HOOK DS 17 Hook in message/index.php
     * bricht das Messaging-Skript ab, falls dieser User nicht das Recht hat den
     * User mit der ID $user2id zu sehen.
     *
     * @param int $user2id, 0 oder eine gültige Userid
     * @return void
     */
    public static function hook_message_index($user2id) {
        if ($user2id == 0)
            return;
if(has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM),$user2id))
         return;
        datenschutz::_require_cap_to_view_user($user2id);
    }

    /** @HOOK DS 18 Hook in lib/filelib.php
     * verhindert den Download verschiedener Dateitypen, falls diese nicht durch einen
     * Player aufgerufen werden.
     *
     * @param stored_file $stored_file
     */
    public static function hook_filelib_send_stored_file($stored_file) {
        global $FULLME, $OUTPUT;

//* Debug HTTP_REFERER

        /* $fp = fopen("datei.txt","a+");
          ob_start();
          print_r($_REQUEST);
          print_r($_SERVER);
          echo "test";
          $text = ob_get_contents();
          ob_clean();
          fwrite($fp, $text);
          fclose($fp); */

        $locked_mimetypes = array('audio/mp3', 'video/x-flv');
//feststellen, ob der Aufruf eine Audio oder Videodatei anfordert.
        if (!in_array($stored_file->get_mimetype(), $locked_mimetypes))
            return;

        $allowed_referer = array('flowplayer');
//feststellen ob der Aufruf von einem Player stammt:
        foreach ($allowed_referer as $referer) {
            if (strpos($_SERVER['HTTP_REFERER'], $referer) > 0)
                return;
        }

//HTML der Seite generieren mit Hilfe von Filtern Player einbinden.
        $text = filter_text("<a href=\"{$FULLME}\" />Multimediafile</a>");
        echo $OUTPUT->header();
        echo $text;
        echo $OUTPUT->footer();
        die;
    }

    /** @HOOK DS19, Hook in enrol/instances.php
     * verhindern, dass sich ein Trainer durch Verbergen bzw. Löschen der Einschreibemethode
     * selbst aus dem Kurs ausschließt
     *
     * @param $course
     * @return array, die Einschreibemethoden dieses Users
     */
    public static function hook_enrol_instances_get_user_enrolmentmethods($course) {
        global $CFG, $PAGE, $USER;

        require_once("$CFG->dirroot/enrol/locallib.php");
        $manager = new course_enrolment_manager($PAGE, $course);
        $enrolments = $manager->get_user_enrolments($USER->id);

        $enrolmentmethods = array();
        foreach ($enrolments as $enrolment) {
            $enrolmentmethods[] = $enrolment->enrolmentinstance->enrol;
        }
        return $enrolmentmethods;
    }

    /** @HOOK DS20: entfällt seit ProfilePicture-Picker von Synergy */

    /** @HOOK DS21, Hook in mod/chat/lib.php
     * anonymisiert das Chat-Protokoll
     * author: Andrea Taras
     */
    public static function hook_mod_chat_format_message_anon($message, $courseid, $sender, $currentuser, $chat_lastrow = NULL) {
        global $CFG, $USER, $OUTPUT;

        $output = new stdClass();
        $output->beep = false;       // by default
        $output->refreshusers = false; // by default
        // Use get_user_timezone() to find the correct timezone for displaying this message:
        // It's either the current user's timezone or else decided by some Moodle config setting
        // First, "reset" $USER->timezone (which could have been set by a previous call to here)
        // because otherwise the value for the previous $currentuser will take precedence over $CFG->timezone
        $USER->timezone = 99;
        $tz = get_user_timezone($currentuser->timezone);

        // Before formatting the message time string, set $USER->timezone to the above.
        // This will allow dst_offset_on (called by userdate) to work correctly, otherwise the
        // message times appear off because DST is not taken into account when it should be.
        $USER->timezone = $tz;
        $message->strtime = userdate($message->timestamp, get_string('strftimemessage', 'chat'), $tz);

        //$message->picture = $OUTPUT->user_picture($sender, array('size'=>false, 'courseid'=>$courseid, 'link'=>false));
        $message->picture = '';
        $hashy = substr(md5($sender->id), 0, 4);
        $myanon = get_string('chatuser', 'chat') . ' ' . $hashy;
        if ($courseid) {
            //$message->picture = "<a onclick=\"window.open('$CFG->wwwroot/user/view.php?id=$sender->id&amp;course=$courseid')\" href=\"$CFG->wwwroot/user/view.php?id=$sender->id&amp;course=$courseid\">$message->picture</a>";
            $message->picture = '';
        }

        //Calculate the row class
        if ($chat_lastrow !== NULL) {
            $rowclass = ' class="r' . $chat_lastrow . '" ';
        } else {
            $rowclass = '';
        }

        // Start processing the message

        if (!empty($message->system)) {
            // System event

            $output->text = $message->strtime . ': ' . get_string('message' . $message->message, 'chat', $myanon);
            $output->html = '<table class="chat-event"><tr' . $rowclass . '><td class="picture">' . $message->picture . '</td><td class="text">';
            $output->html .= '<span class="event">' . $output->text . '</span></td></tr></table>';
            $output->basic = '<dl><dt class="event">' . $message->strtime . ': ' . get_string('message' . $message->message, 'chat', $myanon) . '</dt></dl>';

            if ($message->message == 'exit' or $message->message == 'enter') {

                $output->refreshusers = true; //force user panel refresh ASAP
            }

            return $output;
        }

        // It's not a system event
        $text = trim($message->message);

        /// Parse the text to clean and filter it
        $options = new stdClass();
        $options->para = false;
        $text = format_text($text, FORMAT_MOODLE, $options, $courseid);

        // And now check for special cases
        $patternTo = '#^\s*To\s([^:]+):(.*)#';
        $special = false;

        if (substr($text, 0, 5) == 'beep ') {
            /// It's a beep!
            $special = true;
            $beepwho = trim(substr($text, 5));

            if ($beepwho == 'all') {   // everyone
                $outinfo = $message->strtime . ': ' . get_string('messagebeepseveryone', 'chat', $myanon);
                $outmain = '';
                $output->beep = true;  // (eventually this should be set to
                //  to a filename uploaded by the user)
            } else if ($beepwho == $currentuser->id) {  // current user
                $outinfo = $message->strtime . ': ' . get_string('messagebeepsyou', 'chat', $myanon);
                $outmain = '';
                $output->beep = true;
            } else {  //something is not caught?
                return false;
            }
        } else if (substr($text, 0, 1) == '/') {     /// It's a user command
            $special = true;
            $pattern = '#(^\/)(\w+).*#';
            preg_match($pattern, $text, $matches);
            $command = isset($matches[2]) ? $matches[2] : false;
            // Support some IRC commands.
            switch ($command) {
                case 'me':
                    $outinfo = $message->strtime;
                    $outmain = '*** <b>' . $myanon . ' ' . substr($text, 4) . '</b>';
                    break;
                default:
                    // Error, we set special back to false to use the classic message output.
                    $special = false;
                    break;
            }
        } else if (preg_match($patternTo, $text)) {
            $special = true;
            $matches = array();
            preg_match($patternTo, $text, $matches);
            if (isset($matches[1]) && isset($matches[2])) {
                $outinfo = $message->strtime;
                $outmain = $myanon . ' ' . get_string('saidto', 'chat') . ' <i>' . $matches[1] . '</i>: ' . $matches[2];
            } else {
                // Error, we set special back to false to use the classic message output.
                $special = false;
            }
        }

        if (!$special) {
            $outinfo = $message->strtime . ' ' . $myanon;
            $outmain = $text;
        }

        /// Format the message as a small table

        $output->text = strip_tags($outinfo . ': ' . $outmain);

        $output->html = "<table class=\"chat-message\"><tr$rowclass><td class=\"picture\" valign=\"top\">$message->picture</td><td class=\"text\">";
        $output->html .= "<span class=\"title\">$outinfo</span>";
        if ($outmain) {
            $output->html .= ": $outmain";
            $output->basic = '<dl><dt class="title">' . $outinfo . ':</dt><dd class="text">' . $outmain . '</dd></dl>';
        } else {
            $output->basic = '<dl><dt class="title">' . $outinfo . '</dt></dl>';
        }
        $output->html .= "</td></tr></table>";
        return $output;
    }

}
