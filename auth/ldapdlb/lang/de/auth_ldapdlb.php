<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'auth_ldap', language 'de', branch 'MOODLE_22_STABLE'
 *
 * @package   auth_ldapdlb
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['auth_ldap_logout_settings'] = 'Logout-Einstellungen';
$string['auth_ldap_logouturl'] = 'URL, auf die beim Logout umgeleitet wird';
$string['auth_ldap_logouturl_key'] = 'Logout-URL';
$string['auth_ldap_local_subcontext'] = 'Lokaler LDAP-Subcontext, in dem die Nutzer der Instanz untergeordnet sind.  Zum Beispiel: \'ou=teachers\'';
$string['auth_ldap_local_subcontext_key'] = 'Subkontext';
$string['auth_auto_delete_interval'] = 'Geben Sie einen Zeitraum in Tagen an. Nach der eingetragenen Zeit werden inaktive Nutzerkonten automatisch gel&ouml;scht';
$string['auth_auto_delete_interval_key'] = 'Auto-Delete';
$string['auth_ldapdlbdescription'] = '<p>Diese Anmeldemethode ermöglicht die Authentifizierung über einen externen LDAP-Server. 

<p>Um ein neues LDAP-basiertes Nutzerkonto in Moodle anzulegen, muss vorher das LDAP-Nutzerkonto existieren. Beim ersten Login wird automatisch ein neues Nutzerkonto in der Moodle-Datenbank, wobei Anmeldename und Kennwort vorher von LDAP geprüft werden. Das Modul sorgt dafür, dass ausgewählte Nutzerdaten von LDAP in die Moodle-Datenbank übernommen werden können. Wenn das Kennwort weiterhin ausschließlich von LDAP verwaltet wird, ermöglicht dies einheitliche Anmeldedaten in unterschiedlichen Moodle-Instanzen und bei anderen Servern.

<p>Bei allen weiteren Logins werden weiterhin Anmeldename und Kennwort vom LDAP-Server überprüft.';
$string['pluginname'] = 'DLB-LDAP-Server';
$string['auth_ldapdlb_invalidschoolid'] = 'Die Schule konnte im lokalen LDAP-Subkontext nicht gefunden werden';
$string['auth_ldapdlb_invalidrole'] = 'Ungültige Benutzergruppe';
$string['auth_ldapdlb_organisationlist_key'] = 'Organisationen';
$string['auth_ldapdlb_organisationlist'] = 'Liste aller LDAP-Organisationen und zugehöriger Login-Seiten (z.B.: GYM=http://myurl.com/login/index.php)';

$string['site:dlbuploadusers'] = 'Nutzer hochladen';
$string['site:dlbuploadusers_selectinstitute'] = 'Schul-ID auswählen';
