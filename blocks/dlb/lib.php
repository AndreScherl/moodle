<?php
/**diese Funktion wird aufgerufen, wenn ein User über die Funktion delete_user() gelöscht wird
 * die Datenbank enhält bereits das veränderte Flag deleted==1 und den modifizierten
 * username, das übergebene Userobject jedoch nicht.
 */
function block_dlb_on_user_deleted($user) {
   global $DB;
   /* @TODO: Löschen des Users aus LDAP? */
   /* Anonymisieren der Userdaten */
   $deleteduser = $DB->get_record('user', array("id" => $user->id));
   $now = time();
   /* erzeuge leeres Standarduser-Objekt */
   $u = new stdClass();
   $u->id = $deleteduser->id;
   $u->auth = 'ldapdlb';
   $u->confirmed = $deleteduser->confirmed;
   $u->policyagreed = '0';
   $u->deleted = '1';
   $u->suspended = '0';
   $u->mnethostid = '';
   $u->username = md5($deleteduser->username.$deleteduser->id.$now);
   $u->password = '';
   $u->idnumber = '';
   $u->firstname = 'Anonymous';
   $u->lastname = '';
   $u->email = $deleteduser->email.$deleteduser->id;
   $u->emailstop = '0';
   $u->icq = '';
   $u->skype = '';
   $u->yahoo = '';
   $u->aim = '';
   $u->msn = '';
   $u->phone1 = '';
   $u->phone2 = '';
   $u->institution = '';
   $u->department = '';
   $u->address = '';
   $u->city = '';
   $u->country = 'DE';
   $u->lang = 'en';
   $u->theme = '';
   $u->timezone = '99';
   $u->firstaccess = '0';
   $u->lastaccess = '0';
   $u->lastlogin = '0';
   $u->currentlogin = '0';
   $u->lastip = '';
   $u->secret = '';
   $u->picture = '0';
   $u->url = '';
   $u->description = '';
   $u->descriptionformat = '0';
   $u->mailformat = '1';
   $u->maildigest = '0';
   $u->maildisplay = '2';
   $u->htmleditor = '1';
   $u->ajax = '1';
   $u->autosubscribe = '1';
   $u->trackforums = '0';
   $u->timecreated = '0';
   $u->timemodified = '0';
   $u->trustbitmask = '0';
   $u->imagealt = '';
   $u->screenreader = '0';
   //abspeichern
   $DB->update_record('user', $u);
}
function block_dlb_has_capability_anywhere() {
    
}
