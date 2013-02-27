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
 * Strings for component 'auth_ldapdlb', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   auth_ldap
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$string['auth_ldap_logout_settings'] = 'Logout-Settings';
$string['auth_ldap_logouturl'] = 'URL for Logout-Redirection';
$string['auth_ldap_logouturl_key'] = 'Logout-URL';
$string['auth_ldap_local_subcontext'] = 'Local subcontext where users of this instance are located.  For example: \'ou=teachers\'';
$string['auth_ldap_local_subcontext_key'] = 'Subcontext';
$string['auth_auto_delete_interval'] = 'Enter number of days new users shall be stored before the are automatically deletey by cron';
$string['auth_auto_delete_interval_key'] = 'Auto-Delete';
$string['auth_ldapdlbdescription'] = 'This method is based on LDAP-Authentication and provides authentication against an external LDAP server.
                                  If the given username and password are valid, Moodle creates a new user
                                  entry in its database. This module can read user attributes from LDAP and prefill
                                  wanted fields in Moodle.  For following logins only the username and
                                  password are checked. Some modifications for DLB-Project are included.';
$string['pluginname'] = 'DLB-LDAP server';
$string['auth_ldapdlb_invalidschoolid'] = 'School not found in local LDAP-Context';
$string['auth_ldapdlb_invalidrole'] = 'Invalid User-Group';
$string['auth_ldapdlb_organisationlist_key'] = 'Organisations';
$string['auth_ldapdlb_organisationlist'] = 'List of all LDAP-Organisations and Login-Pages (eg: GYM=http://myurl.com/login/index.php)';

$string['site:dlbuploadusers'] = 'Nutzer hochladen';
$string['site:dlbuploadusers_selectinstitute'] = 'Schul-ID auswählen';
