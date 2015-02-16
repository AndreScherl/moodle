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

namespace block_dlb\local;

use admin_externalpage;
use admin_root;
use context_course;
use context_coursecat;
use context_system;
use course_enrolment_manager;
use filter_manager;
use html_writer;
use lang_string;
use moodle_database;
use MoodleQuickForm;
use stdClass;
use stored_file;

defined('MOODLE_INTERNAL') || die();

class datenschutz {

    protected $useridstogetherincourse;

    /**
     * Singleton: Aus Performancegründen wird eine Instanz der Klasse Datenschutz erzeugt. Damit
     * wird das Ergebnis der Abfrage in _get_userids_together_in_course gecacht.
     *
     * @staticvar datenschutz $datenschutz
     * @return datenschutz, Instanz der Klasse datenschutz
     */
    public static function get_instance() {
        static $datenschutz;

        if (isset($datenschutz)) {
            return $datenschutz;
        }

        $datenschutz = new datenschutz();
        return $datenschutz;
    }

    /**
     * bricht das Skript mit einer Fehlermeldung ab, falls der eingeloggte User nicht
     * das Recht hat User anderer Schulen (Feld Institution) zu sehen und der User zur übergebenen
     * User-ID nicht den gleichen Wert im Feld Schule (Institution) hat
     *
     * @global moodle_database $DB
     * @global object $USER
     * @param int $userid
     * @return boolean if this user is allowed to view user with given userid.
     */
    private static function _require_same_institution($userid) {
        global $DB, $USER;

        // ... return if a new user is created.
        if ($userid == -1) {
            return;
        }

        // ... institution is the same for equal users.
        if ($userid == $USER->id) {
            return;
        }

        // ... nothing to do, if user has cap to view other institutions.
        if (has_capability("block/dlb:institutionview", context_system::instance())) {
            return;
        }

        // ... check institution.
        $user = $DB->get_record('user', ['id' => $userid]);

        if (empty($USER->institution)) {
            print_error('noinstitutionerror', 'block_dlb');
        }

        if (($USER->institution != $user->institution)) {
            print_error('nopermissiontoedituser', 'block_dlb');
        }
    }

    /**
     * print error, when this user is not allowed to send a message or do some
     * other action (i. e. block contact etc.) related to given user.
     *
     * @global moodle_database $DB
     * @global object $USER
     * @param int $userid
     */
    private static function can_do_message_actions($userid) {
        global $DB, $USER;

        // ... user may send a messagen to himself.
        if ($userid == $USER->id) {
            return;
        }

        // ... this user may send messages to everyone.
        if (has_capability("block/dlb:institutionview", context_system::instance())) {
            return;
        }

        // ... check same institution.
        $user = $DB->get_record('user', ['id' => $userid]);

        // ... user must have same instituion.
        if (empty($USER->institution)) {
            print_error('noinstitutionerror', 'block_dlb');
        }

        if ($USER->institution == $user->institution) {
            return;
        }

        // ... if user is participant in same course.
        $datenschutz = self::get_instance();
        $usertogether = $datenschutz->_get_userids_together_in_course($userid);

        if (in_array($USER->id, array_keys($usertogether))) {
            return;
        }

        // ... if user has got a message from recipient already.
        if ($datenschutz->has_sent_messages($userid, $USER->id)) {
            return;
        }

        print_error('nopermissiontoviewuser', 'block_dlb');
    }

    /**
     * check, whether there are messages sent between users.
     *
     * @global moodle_database $DB
     * @param int $useridfrom
     * @param int $useridto
     * @return bool
     */
    private function has_sent_messages($useridfrom, $useridto) {
        global $DB;

        // ... check read messages.
        $sql = "SELECT count(*) FROM {message_read}
                WHERE useridfrom = ? AND useridto = ?";

        $count = $DB->count_records_sql($sql, [$useridfrom, $useridto]);

        // ... if no read messages, check unread messages.
        if ($count == 0) {

            $sql = "SELECT count(*) FROM {message}
                    WHERE useridfrom = ? AND useridto = ?";

            $count = $DB->count_records_sql($sql, [$useridfrom, $useridto]);
        }

        return ($count > 0);
    }

    /** get all users, which are enrolled in courses with this user ($USER).
     *
     * @global moodle_database $DB
     * @param int $userid, die ID des Users
     * @return object[], recordset bestehend aus userids
     */
    private function _get_userids_together_in_course($userid) {
        global $DB;

        if (isset($this->useridstogetherincourse)) {
            return $this->useridstogetherincourse;
        }

        $sql = "SELECT DISTINCT userid FROM {user_enrolments} ue " .
                "JOIN {enrol} e ON e.id = ue.enrolid " .
                "WHERE courseid in (" .
                "SELECT courseid FROM {user_enrolments} ue " .
                "JOIN {enrol} e ON e.id = ue.enrolid where userid = :userid)";

        $this->useridstogetherincourse = $DB->get_records_sql($sql, ["userid" => $userid]);
        return $this->useridstogetherincourse;
    }

    /**
     * ändert die $wherecondition so ab, dass die vereinbarte Sichtbarkeitsregel
     * eingehalten wird.
     *
     * @param string $wherecondition , die Bedingung des SQL-Statement zur Usersuche
     * @param string $tablealias
     * @param bool $strictinstitution
     * @global object $USER , der aktuelle User
     * @return string
     */
    private static function _add_institution_filter($wherecondition = "", $tablealias = "", $strictinstitution = false) {
        global $USER;

        // ... nothing to do, if user has cap to view other institutions.
        if (has_capability("block/dlb:institutionview", context_system::instance())) {
            return $wherecondition;
        }

        if (empty($USER->institution)) {
            print_error('noinstitutionerror', 'block_dlb');
        }

        if (!empty($wherecondition)) {
            $wherecondition .= " AND ";
        }

        if ($strictinstitution) {
            $wherecondition .= " ({$tablealias}institution = '{$USER->institution}')";
            return $wherecondition;
        }

        // ...check if users are enrolled in the same course.
        $datenschutz = self::get_instance();
        $userids = $datenschutz->_get_userids_together_in_course($USER->id);

        if ($userids) {

            $userids = array_keys($userids);
            $wherecondition .= " (({$tablealias}institution = '{$USER->institution}') or {$tablealias}id IN ("
                    . implode(",", $userids) . ")) ";
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

    /**
     * @HOOK DS01: Hook in enrol/locallib.php course_enrolment_manager->get_potential_users()
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der Rollenzuweisung im Kurs
     * (unter Verwendung von AJAX) sieht
     *
     * @param String $wherecondition
     * @return string
     */
    public static function hook_enrol_locallib_get_potential_users($wherecondition) {
        return self::_add_institution_filter($wherecondition, "u.", true);
    }

    /**
     * @HOOK DS02: Hook in enrol/manual/locallib.php enrol_manual_potential_participant->find_users()
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der Rollenzuweisung im Kurs
     * (ohne Verwendung von AJAX) sieht
     *
     * @param String $wherecondition
     * @return string
     */
    public static function hook_enrol_manual_locallib_find_users($wherecondition) {
        return self::_add_institution_filter($wherecondition, "u.", true);
    }

    /**
     * @HOOK DS03: Hook in admin/user.php
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der globalen Nutzerverwaltung sieht
     *
     * @param String $wherecondition
     * @return string
     * 
     * obsolete seit dem IDM (Feb 2014)
     */
    /**
     * @HOOK DS04: Hook in admin/user.php
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die <b>Gesamtanzahl der User</b> mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der globalen Nutzerverwaltung sieht
     * 
     * obsolete seit dem IDM (Feb 2014) 
     */
    /**
     * DS05: Hook in local/user/editadvanced.php
     * prüft, ob der eingeloggte User zur Bearbeitung des Users mit der ID $usertoedit
     * berechtigt ist. Ist dies nicht der Fall, so wird mit einer Fehlermeldung abgebrochen.
     *
     * Durch die Verwendung eines "localized Scripts" (local/...) wird diese Prüfung
     * vor der Verarbeitung des originalen Skripts user/editadvanced.php aufgerufen
     *
     * @param int $useridtoedit, die ID des zu bearbeitenden Users
     *
     * * obsolete seit dem IDM (Feb 2014)
     */
    /**
     * DS06: Hook in local/user/edit.php
     * prüft, ob der eingeloggte User zur Bearbeitung des Users mit der ID $usertoedit
     * berechtigt ist. Ist dies nicht der Fall, so wird mit einer Fehlermeldung abgebrochen.
     *
     * Durch die Verwendung eines "localized Scripts" (local/...) wird diese Prüfung
     * vor der Verarbeitung des originalen Skripts user/edit.php aufgerufen
     *
     * @param int $useridtoedit, die ID des zu bearbeitenden Users
     * 
     * * obsolete seit dem IDM (Feb 2014)
     * 
     */

    /**
     * @HOOK DS07: Hook in admin/roles/lib.php
     * potential_assignees_course_and_above->get_potential_users()
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der Rollenzuweisung außerhalb des Kurses
     *
     * @param String $wherecondition
     * @return string
     */
    public static function hook_admin_roles_lib_find_users($wherecondition) {
        return self::_add_institution_filter($wherecondition);
    }

    /**
     * @HOOK DS08: Hook in cohort/lib.php
     * cohort_candidate_selector->find_users()
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der Rollenzuweisung zu einer Kohorte sieht
     *
     * @param String $wherecondition
     * @return string
     */
    public static function hook_cohort_lib_find_users($wherecondition) {
        return self::_add_institution_filter($wherecondition, "u.", true);
    }

    /**
     * @HOOK DS09: Hook in message/lib.php in der Funktion message_search_users()
     *
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der Suche nach Kontakten sieht
     *
     * @return string
     */
    public static function hook_message_lib_message_search_users() {
        $wherecondition = self::_add_institution_filter("", "u.");
        $wherecondition = (!empty($wherecondition)) ? " AND " . $wherecondition : "";
        return $wherecondition;
    }

    /**
     * @HOOK DS10: Hook in user/profile/lib.php
     *
     * blendet das Beschreibungsfeld aus.
     * deaktiviert die Uploadmöglichkeit für Bilder
     * =>Verhinderung nach dem Submit siehe DS20
     *
     * sperrt die Bearbeitung des Feldes Schule (Institution) für Nicht Admins
     *
     * @param MoodleQuickForm $mform
     * @param mixed , false oder 0 bei neuem User, sonst Userid des bearbeiteten Users.
     */
    public static function hook_profile_definition_after_data($mform, $userid) {
        global $CFG, $DB, $USER;

        // Editor für alle entfernen => nicht sichtbar.
        if ($mform->elementExists('description_editor')) {

            $mform->hardFreeze('description_editor');
            $mform->addElement('hidden', 'description_editor[text]', '');
            $mform->addElement('hidden', 'description_editor[format]', '0');
            $mform->setConstants('description_editor[text]');
            $mform->setConstants('description_editor[format]');
        }

        // Add a new section.
        $settingsheader = $mform->createElement('header', 'moodlesettings', get_string('settings'));
        $mform->insertElementBefore($settingsheader, 'maildisplay');

        // ... move school information (field city) into top section.
        if ($mform->elementExists('city')) {
            $schoolelement = $mform->removeElement('city');
            $schoolelement->updateAttributes("'size=70'");
            $mform->insertElementBefore($schoolelement, 'moodlesettings');
        }

        // Admin hat Zugriff auf alle Felder...
        if (has_capability("moodle/site:config", context_system::instance())) {
            return;
        }

        // Profilfeld schule für bestehende User sperren.
        $user = $DB->get_record('user', ['id' => $userid]); // $user ist der bearbeitete User (!= $USER).

        if (!empty($CFG->bm_school_field) && ($user)) { // Für bestehenden User das Schulfeld nicht ändern.
            // Falls das Recht nicht besteht über Institutsgrenzen hinauszusehen, darf Profilfeld schule nicht verändert werden.
            $schulfeldname = "profile_field_" . $CFG->bm_school_field;

            if ($mform->elementExists($schulfeldname)) {

                $sql = "SELECT data FROM {user_info_data} as id " .
                        "JOIN {user_info_field} inf ON inf.id = id.fieldid " .
                        "WHERE id.userid = :userid and inf.name = :fieldname";

                $schule = $DB->get_field_sql($sql, ['userid' => $user->id, 'fieldname' => $CFG->bm_school_field]);

                $mform->hardFreeze($schulfeldname);
                $mform->setConstants($schulfeldname, $schule);
            }
        }

        // Weitere gesperrte Felder für bereits angelegte User....
        if ($user) {

            if ($mform->elementExists('city')) {
                // Ersetzt Inputfeld durch Anzeige.
                $mform->hardFreeze('city');
                // Macht den Submit unüberschreibbar, auch bei Formularmanipulationen
                // z. B. Einfügen von <input id="id_city" name="city" value="hack" />.
                $mform->setConstants(['city' => $user->city]);
            }

            if ($mform->elementExists('institution')) {
                // Ersetzt Inputfeld durch Anzeige.
                $mform->hardFreeze('institution');
                // Macht den Submit unüberschreibbar, auch bei Formularmanipulationen.
                $mform->setConstants(['institution' => $user->institution]);
            }

            if ($mform->elementExists('idnumber')) {
                // Ersetzt Inputfeld durch Anzeige.
                $mform->hardFreeze('idnumber');
                // Macht den Submit unüberschreibbar, auch bei Formularmanipulationen.
                $mform->setConstants(['idnumber' => $user->idnumber]);
            }
            
            if ($mform->elementExists('department')) {
                // Ersetzt Inputfeld durch Anzeige.
                $mform->hardFreeze('department');
                // Macht den Submit unüberschreibbar, auch bei Formularmanipulationen.
                $mform->setConstants(['department' => $user->department]);
            }
            
            if ($mform->elementExists('interests')) {
                // Ersetzt Inputfeld durch Anzeige.
                $mform->hardFreeze('interests');
                // Macht den Submit unüberschreibbar, auch bei Formularmanipulationen.
                $mform->setConstants(['interests' => '']);
            }
        }
        // ...remove field, which should not be edited.
        $removefields = array('firstnamephonetic', 'lastnamephonetic', 'middlename', 'alternatename',   
            'url', 'icq', 'skype', 'aim', 'yahoo', 'msn','phone1','phone2','address');
        foreach ($removefields as $field) {
            if ($mform->elementExists($field)) {
                $mform->removeElement($field);
            }
        }

        // Falls kein username angezeigt wird, Information darüber einfügen.
        if (!$mform->elementExists('username')) {
            $username = $mform->createElement('static', 'username', get_string('username'));
            $mform->insertElementBefore($username, 'firstname');
        }

        $authplugin = get_auth_plugin($user->auth);
        // Felder für User, die editadvanced aufrufen können weil sie das Recht
        // moodle/user:update haben, aber keine Admins sind, trotzdem sperren.
        if (has_capability('moodle/user:update', context_system::instance())) {

            // Anmeldenamen schützen.
            if ($mform->elementExists('username')) {
                // Ersetzt Inputfeld durch Anzeige.
                $mform->hardFreeze('username');
                // Macht den Submit unüberschreibbar, auch bei Formularmanipulationen.
                $mform->setConstants(['username' => $user->username]);
            }

            // Restliche Einstellungen des Auth-Plugins schützen.
            $fields = get_user_fieldnames();
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

        // ... hide and lock some fields for Shibboleth-Users and Provide a link to edit personal data in LDAP-Portal.
        $editmebisprofileurl = "";
        if (method_exists($authplugin, 'edit_mebis_profile')) {

            $editmebisprofileurl = $authplugin->edit_mebis_profile();
        }

        if (!empty($editmebisprofileurl)) {

            if ($mform->elementExists('suspended')) {
                $mform->removeElement('suspended');
            }

            if ($mform->elementExists('newpassword')) {
                $mform->removeElement('newpassword');
            }

            if ($mform->elementExists('preference_auth_forcepasswordchange')) {
                $mform->removeElement('preference_auth_forcepasswordchange');
            }

            if ($mform->elementExists('email')) {
                // Ersetzt Inputfeld durch Anzeige.
                $mform->hardFreeze('email');
                // Macht den Submit unüberschreibbar, auch bei Formularmanipulationen.
                $mform->setConstants(['email' => $user->email]);
            }

            // Add a Link to edit personal data in LDAP-Portal.
            if ($user->id == $USER->id) {
                $output = html_writer::link($editmebisprofileurl, get_string('editmebisprofile', 'theme_dlb'));
                $output = html_writer::tag('div', $output, ['class' => 'editprofileurl']);

                $element = $mform->createElement('html', $output);
                $mform->insertElementBefore($element, 'username');
            }
        }
    }

    /**
     * @HOOK DS11: Hook in mod/chat/mod_form.php
     * entfernt aus der Liste der Optionen für die Löschungfristen die Option "niemals löschen"
     *
     * @param array $options, die Optionen der Löschungsfristen von Chatprotokollen
     */
    public static function hook_mod_chat_mod_form_definition(&$options) {
        unset($options[0]);
    }

    /**
     * @HOOK DS12: Hook in report/outline/lib.php
     * verhindert die Anzeige von Navigationslinks aus personenbezogene Berichte im Kontext des Kurses
     *
     * @return bool, muss false zurückgeben, falls die personenbezogenen Berichte nicht angezeigt werden sollen.
     */
    public static function hook_report_outline_lib_report_outline_can_access_user_report() {
        return has_capability("moodle/site:config", context_system::instance());
    }

    /**
     * @HOOK DS13: Hook in local/report/outline/user.php
     * verhindert den direkten Aufruf des personenbezogenen Berichtes im Kontext
     * eines Kurses für Nicht-Admins
     */
    public static function hook_local_report_outline_user_require_access_user_report() {
        if (!has_capability("moodle/site:config", context_system::instance())) {
            print_error('notallowedtoaccessuserreport', 'block_dlb');
        }
    }

    /**
     * @HOOK DS14: Hook in local/course/recent.php
     * verhindert den direkten Aufruf der Austellung vergangener Aktivitäten für Nicht-Admins
     */
    public static function hook_local_course_recent_require_access_recent_activities() {
        if (!has_capability("moodle/site:config", context_system::instance())) {
            print_error('notallowedtoaccessrecentactivities', 'block_dlb');
        }
    }

    /**
     * @HOOK DS15: Hook in course/lib.php
     * prüft, ob der User den Link zum Auswertungsformular vergangener Aktivitäten sehen darf
     *
     * @return bool, muss false zurückgeben wenn der Link nicht angezeigt werden soll.
     */
    public static function hook_course_lib_can_access_recent_activities() {
        return has_capability("moodle/site:config", context_system::instance());
    }

    /**
     * @HOOK DS16: Hook in admin/settings/user
     * verbirgt die Bulk verwaltung für User, die nicht das Recht haben über Schulgrenzen hinaus zu sehen
     * obsolete seit der neuen Userverwaltung
     */

    /**
     * @HOOK DS17 Hook in message/index.php
     * bricht das Messaging-Skript ab, falls dieser User nicht das Recht hat den
     * User mit der ID $user2id zu sehen.
     *
     * @param int $user2id, 0 oder eine gültige Userid
     */
    public static function hook_message_index($user2id) {

        // ... allow actions not regarding a foreign user.
        if ($user2id == 0) {
            return;
        }

        // ... allow all actions for administrators.
        if (has_capability('moodle/site:config', context_system::instance(), $user2id)) {
            return;
        }

        // ...check whether $USER has the permission to run the message/index.php script.
        self::can_do_message_actions($user2id);
    }

    /**
     * @HOOK DS18 Hook in lib/filelib.php
     * verhindert den Download verschiedener Dateitypen, falls diese nicht durch einen
     * Player aufgerufen werden.
     *
     * @param stored_file $storedfile
     */
    public static function hook_filelib_send_stored_file($storedfile) {
        global $FULLME, $OUTPUT, $COURSE;

        $lockedmimetypes = ['audio/mp3', 'video/x-flv'];
        // Feststellen, ob der Aufruf eine Audio oder Videodatei anfordert.
        if (!in_array($storedfile->get_mimetype(), $lockedmimetypes)) {
            return;
        }

        $allowedreferer = ['flowplayer'];
        // Feststellen ob der Aufruf von einem Player stammt.
        foreach ($allowedreferer as $referer) {
            if (strpos($_SERVER['HTTP_REFERER'], $referer) > 0) {
                return;
            }
        }

        // HTML der Seite generieren mit Hilfe von Filtern Player einbinden.
        $context = context_course::instance($COURSE->id);
        $text = filter_manager::instance()->filter_text("<a href=\"{$FULLME}\" />Multimediafile</a>", $context);
        echo $OUTPUT->header();
        echo $text;
        echo $OUTPUT->footer();
        die;
    }

    /**
     * @HOOK DS19, Hook in enrol/instances.php
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

        $enrolmentmethods = [];
        foreach ($enrolments as $enrolment) {
            $enrolmentmethods[] = $enrolment->enrolmentinstance->enrol;
        }
        return $enrolmentmethods;
    }

    /** @HOOK DS20: entfällt seit ProfilePicture-Picker von Synergy */

    /**
     * @HOOK DS21, Hook in mod/chat/lib.php
     * anonymisiert das Chat-Protokoll
     * author: Andrea Taras
     */
    public static function hook_mod_chat_format_message_anon($message, $courseid, $sender, $currentuser, $chatlastrow = null) {
        global $USER;

        $output = new stdClass();
        $output->beep = false;       // By default.
        $output->refreshusers = false; // By default.
        // Use get_user_timezone() to find the correct timezone for displaying this message:
        // It's either the current user's timezone or else decided by some Moodle config setting
        // First, "reset" $USER->timezone (which could have been set by a previous call to here)
        // because otherwise the value for the previous $currentuser will take precedence over $CFG->timezone.
        $USER->timezone = 99;
        $tz = get_user_timezone($currentuser->timezone);

        // Before formatting the message time string, set $USER->timezone to the above.
        // This will allow dst_offset_on (called by userdate) to work correctly, otherwise the
        // message times appear off because DST is not taken into account when it should be.
        $USER->timezone = $tz;
        $message->strtime = userdate($message->timestamp, get_string('strftimemessage', 'chat'), $tz);

        $message->picture = '';
        $hashy = substr(md5($sender->id), 0, 4);
        $myanon = get_string('chatuser', 'chat') . ' ' . $hashy;
        if ($courseid) {
            $message->picture = '';
        }

        // Calculate the row class.
        if ($chatlastrow !== null) {
            $rowclass = ' class="r' . $chatlastrow . '" ';
        } else {
            $rowclass = '';
        }

        // Start processing the message.

        if (!empty($message->system)) {
            // System event.

            $output->text = $message->strtime . ': ' . get_string('message' . $message->message, 'chat', $myanon);
            $output->html = '<table class="chat-event"><tr' . $rowclass . '><td class="picture">' . $message->picture .
                    '</td><td class="text">';
            $output->html .= '<span class="event">' . $output->text . '</span></td></tr></table>';
            $output->basic = '<dl><dt class="event">' . $message->strtime . ': ' .
                    get_string('message' . $message->message, 'chat', $myanon) . '</dt></dl>';

            if ($message->message == 'exit' or $message->message == 'enter') {
                $output->refreshusers = true; // Force user panel refresh ASAP.
            }

            return $output;
        }

        // It's not a system event.
        $text = trim($message->message);

        // Parse the text to clean and filter it.
        $options = new stdClass();
        $options->para = false;
        $text = format_text($text, FORMAT_MOODLE, $options, $courseid);

        // And now check for special cases.
        $patternto = '#^\s*To\s([^:]+):(.*)#';
        $special = false;

        $outinfo = '';
        $outmain = '';
        if (substr($text, 0, 5) == 'beep ') {
            // It's a beep!
            $special = true;
            $beepwho = trim(substr($text, 5));

            if ($beepwho == 'all') {   // Everyone.
                $outinfo = $message->strtime . ': ' . get_string('messagebeepseveryone', 'chat', $myanon);
                $outmain = '';
                $output->beep = true;  // Eventually this should be set to a filename uploaded by the user.
            } else if ($beepwho == $currentuser->id) {  // Current user.
                $outinfo = $message->strtime . ': ' . get_string('messagebeepsyou', 'chat', $myanon);
                $outmain = '';
                $output->beep = true;
            } else {  // Something is not caught?
                return false;
            }
        } else if (substr($text, 0, 1) == '/') {     // It's a user command.
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
        } else if (preg_match($patternto, $text)) {
            $special = true;
            $matches = [];
            preg_match($patternto, $text, $matches);
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

        // Format the message as a small table.

        $output->text = strip_tags($outinfo . ': ' . $outmain);

        $output->html = "<table class=\"chat-message\">
        <tr$rowclass>
        <td class=\"picture\" valign=\"top\">$message->picture</td>
        <td class=\"text\">";
        $output->html .= "<span class=\"title\">$outinfo</span>";
        if ($outmain) {
            $output->html .= ": $outmain";
            $output->basic = '<dl><dt class="title">' . $outinfo . ':</dt><dd class="text">' . $outmain . '</dd></dl>';
        } else {
            $output->basic = '<dl><dt class="title">' . $outinfo . '</dt></dl>';
        }
        $output->html .= "</td>
        </tr>
        </table>";
        return $output;
    }

    /**
     * @HOOK DS22: Hook in enrol/locallib.php course_enrolment_manager->get_other_users()
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der Rollenzuweisung im Kurs
     * (unter Verwendung von AJAX) sieht
     *
     * @return string
     */
    public static function hook_enrol_locallib_get_other_users() {

        $whereinstitution = self::_add_institution_filter("", "u.", true);
        if (!empty($whereinstitution)) {
            $whereinstitution = " AND " . $whereinstitution;
        }
        return $whereinstitution;
    }

    /**
     * @HOOK DS23: Hook in admin/roles/lib.php potential_assignees_below_course->find_users()
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der Rollenzuweisung im Kurs
     * (unter Verwendung von AJAX) sieht
     *
     * Translation: Hook in admin / roles / lib.php potential_assignees_below_course->find_users ()
     * Changed the WHERE clause so that the currently edited user only
     * Users with the same value in the institution (school) or the user, taking with him
     * Are enrolled in a course at the role assignment in the course
     * (Using AJAX) provides
     *
     * @param String $wherecondition
     * @return string
     */
    public static function hook_admin_roles_lib_potential_assignees_below_course($wherecondition) {
        return self::_add_institution_filter($wherecondition, "u.", true);
    }

    /**
     * @HOOK DS24: Hook in enrol/locallib.php course_enrolment_manager->search_other_users()
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der Rollenzuweisung im Kurs
     * (unter Verwendung von AJAX) sieht
     *
     * @param String $wherecondition
     * @return string
     */
    public static function hook_enrol_locallib_search_other_users($wherecondition) {
        return self::_add_institution_filter($wherecondition, "u.", true);
    }

    /**
     * @HOOK DS25: Hook in admin/roles/lib.php potential_assignees_course_and_above->find_users()
     * verändert die WHERE-Bedingung so, dass der aktuell bearbeitende User nur
     * die User mit gleichen Wert im Feld institution (Schule) oder die User, die mit ihm
     * in einen Kurs eingeschrieben sind, bei der Rollenzuweisung im Kurs
     * (unter Verwendung von AJAX) sieht
     *
     * @param String $wherecondition
     * @return string
     */
    public static function hook_admin_roles_potential_assignees_course_and_above($wherecondition) {
        return self::_add_institution_filter($wherecondition, "", true);
    }

    /**
     * @HOOK DS26: Hook in editcategory_form.php, Check if the user is allowed to edit the idnumber.
     * Avoid that a schoolnumber, which is needed as idnumber for the maincategory  of schoolwould
     * be used in some subcategories inputed by a "Schulkoordinator".
     *
     * @param int $categoryid
     * @param int $parent
     * @return bool
     */
    public static function can_edit_idnumber($categoryid, $parent) {
        if ($categoryid) {
            $context = context_coursecat::instance($categoryid);
        } else if ($parent) {
            $context = context_coursecat::instance($parent);
        } else {
            $context = context_system::instance();
        }
        return has_capability('local/dlb:editschoolid', $context);
    }

    /**
     * @HOOK DS27: Bugfix of moodles messaging system to avoid message processing interruption
     *
     * @param bool $currentstate
     * @return bool
     */
    public static function hook_user_messageselect_avoid_message_interruption($user, $messagebody, $format, $currentstate) {
        global $USER;
        $mstate = message_post_message($USER, $user, $messagebody, $format);
        $currentstate = $currentstate && $mstate;
        return $currentstate;
    }

}
