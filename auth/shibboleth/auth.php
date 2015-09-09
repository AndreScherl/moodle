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
 * Authentication Plugin: Shibboleth Authentication
 * Authentication using Shibboleth.
 *
 * Distributed under GPL (c)Markus Hagman 2004-2006
 *
 * @package auth_shibboleth
 * @author Martin Dougiamas
 * @author Lukas Haemmerle
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

/**
 * Shibboleth authentication plugin.
 */
class auth_plugin_shibboleth extends auth_plugin_base {

    /**
     * Constructor.
     */
    function auth_plugin_shibboleth() {
        $this->authtype = 'shibboleth';
        $this->config = get_config('auth/shibboleth');
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username (with system magic quotes)
     * @param string $password The password (with system magic quotes)
     * @return bool Authentication success or failure.
     */
    function user_login($username, $password) {
        global $SESSION;

        // If we are in the shibboleth directory then we trust the server var
        if (!empty($_SERVER[$this->config->user_attribute])) {
            // Associate Shibboleth session with user for SLO preparation
            $sessionkey = '';
            if (isset($_SERVER['Shib-Session-ID'])) {
                // This is only available for Shibboleth 2.x SPs
                $sessionkey = $_SERVER['Shib-Session-ID'];
            } else {
                // Try to find out using the user's cookie
                foreach ($_COOKIE as $name => $value) {
                    if (preg_match('/_shibsession_/i', $name)) {
                        $sessionkey = $value;
                    }
                }
            }

            // Set shibboleth session ID for logout
            $SESSION->shibboleth_session_id = $sessionkey;

            //+++ Andre Scherl, add conditions to restrict the access of the mebis beta lms
            if ($_SERVER["Shib-Application-ID"] == "beta") {
                return ((strtolower($_SERVER[$this->config->user_attribute]) == strtolower($username)) && ($_SERVER["mebisBetaAccess"] == "TRUE"));
            }
            //---

            return (strtolower($_SERVER[$this->config->user_attribute]) == strtolower($username));
        } else {
            // If we are not, the user has used the manual login and the login name is
            // unknown, so we return false.
            return false;
        }
    }

    /**
     * Returns the user information for 'external' users. In this case the
     * attributes provided by Shibboleth
     *
     * @return array $result Associative array of user data
     */
    function get_userinfo($username) {
        // reads user information from shibboleth attributes and return it in array()
        global $CFG;

        // Check whether we have got all the essential attributes
        if (empty($_SERVER[$this->config->user_attribute])) {
            print_error('shib_not_all_attributes_error', 'auth_shibboleth', '', "'" . $this->config->user_attribute . "' ('" . $_SERVER[$this->config->user_attribute] . "'), '" . $this->config->field_map_firstname . "' ('" . $_SERVER[$this->config->field_map_firstname] . "'), '" . $this->config->field_map_lastname . "' ('" . $_SERVER[$this->config->field_map_lastname] . "') and '" . $this->config->field_map_email . "' ('" . $_SERVER[$this->config->field_map_email] . "')");
        }

        $attrmap = $this->get_attributes();

        $result = array();
        $search_attribs = array();

        foreach ($attrmap as $key => $value) {
            // Check if attribute is present
            if (!isset($_SERVER[$value])) {
                $result[$key] = '';
                continue;
            }

            // Make usename lowercase
            if ($key == 'username') {
                $result[$key] = strtolower($this->get_first_string($_SERVER[$value]));
            } else {
                $result[$key] = $this->get_first_string($_SERVER[$value]);
            }
            
            // +++ Andre Scherl, remove the role 'Kursersteller' from old school if the user changed the school
            if ($key == 'institution') {
                global $DB;
                $olduser = $DB->get_record('user', array('username' => $username));
                if (isset($olduser->institution) && $olduser->institution != $_SERVER['mebisSchoolID']) {
                    $category = $DB->get_record('course_categories', array('idnumber' => $olduser->institution));
                    $context = context_coursecat::instance($category->id);
                    $roleid = $DB->get_field('role', 'id', array('shortname' => 'kursersteller'));
                    role_unassign($roleid, $olduser->id, $context->id);
                }
            }
            // ---
        }

        // Provide an API to modify the information to fit the Moodle internal
        // data representation
        if (
                $this->config->convert_data && $this->config->convert_data != '' && is_readable($this->config->convert_data)
        ) {

            // Include a custom file outside the Moodle dir to
            // modify the variable $moodleattributes
            include($this->config->convert_data);
        }

        return $result;
    }

    /**
     * Returns array containg attribute mappings between Moodle and Shibboleth.
     *
     * @return array
     */
    function get_attributes() {
        $configarray = (array) $this->config;

        $moodleattributes = array();
        foreach ($this->userfields as $field) {
            if (isset($configarray["field_map_$field"])) {
                $moodleattributes[$field] = $configarray["field_map_$field"];
            }
        }
        $moodleattributes['username'] = $configarray["user_attribute"];

        return $moodleattributes;
    }

    function prevent_local_passwords() {
        return true;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    function is_internal() {
        return false;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    function can_change_password() {
        return false;
    }

    /**
     * Hook for login page
     *
     */
    function loginpage_hook() {
        global $SESSION, $CFG;

        // Prevent username from being shown on login page after logout
        $CFG->nolastloggedin = true;

        return;
    }

    /**
     * Hook for logout page
     *
     */
    function logoutpage_hook() {
        global $SESSION, $redirect;

        // Only do this if logout handler is defined, and if the user is actually logged in via Shibboleth
        $logouthandlervalid = isset($this->config->logout_handler) && !empty($this->config->logout_handler);
        if (isset($SESSION->shibboleth_session_id) && $logouthandlervalid) {
            // Check if there is an alternative logout return url defined
            if (isset($this->config->logout_return_url) && !empty($this->config->logout_return_url)) {
                // Set temp_redirect to alternative return url
                $temp_redirect = $this->config->logout_return_url;
            } else {
                // Backup old redirect url
                $temp_redirect = $redirect;
            }

            // Overwrite redirect in order to send user to Shibboleth logout page and let him return back
            $redirect = $this->config->logout_handler . '?return=' . urlencode($temp_redirect);
        }
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param array $page An object containing all the data for this page.
     */
    function config_form($config, $err, $user_fields) {
        include "config.html";
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     *
     *
     * @param object $config Configuration object
     */
    function process_config($config) {
        global $CFG;

        // set to defaults if undefined
        if (!isset($config->auth_instructions) or empty($config->user_attribute)) {
            $config->auth_instructions = get_string('auth_shib_instructions', 'auth_shibboleth', $CFG->wwwroot . '/auth/shibboleth/index.php');
        }
        if (!isset($config->user_attribute)) {
            $config->user_attribute = '';
        }
        if (!isset($config->convert_data)) {
            $config->convert_data = '';
        }

        if (!isset($config->changepasswordurl)) {
            $config->changepasswordurl = '';
        }

        if (!isset($config->login_name)) {
            $config->login_name = 'Shibboleth Login';
        }

        // Clean idp list
        if (isset($config->organization_selection) && !empty($config->organization_selection) && isset($config->alt_login) && $config->alt_login == 'on') {
            $idp_list = get_idp_list($config->organization_selection);
            if (count($idp_list) < 1) {
                return false;
            }
            $config->organization_selection = '';
            foreach ($idp_list as $idp => $value) {
                $config->organization_selection .= $idp . ', ' . $value[0] . ', ' . $value[1] . "\n";
            }
        }


        // save settings
        set_config('user_attribute', $config->user_attribute, 'auth/shibboleth');

        if (isset($config->organization_selection) && !empty($config->organization_selection)) {
            set_config('organization_selection', $config->organization_selection, 'auth/shibboleth');
        }
        set_config('logout_handler', $config->logout_handler, 'auth/shibboleth');
        set_config('logout_return_url', $config->logout_return_url, 'auth/shibboleth');
        set_config('login_name', $config->login_name, 'auth/shibboleth');
        set_config('convert_data', $config->convert_data, 'auth/shibboleth');
        set_config('auth_instructions', $config->auth_instructions, 'auth/shibboleth');
        set_config('changepasswordurl', $config->changepasswordurl, 'auth/shibboleth');

        // Overwrite alternative login URL if integrated WAYF is used
        if (isset($config->alt_login) && $config->alt_login == 'on') {
            set_config('alt_login', $config->alt_login, 'auth/shibboleth');
            set_config('alternateloginurl', $CFG->wwwroot . '/auth/shibboleth/login.php');
        } else {
            // Check if integrated WAYF was enabled and is now turned off
            // If it was and only then, reset the Moodle alternate URL
            if (isset($this->config->alt_login) and $this->config->alt_login == 'on') {
                set_config('alt_login', 'off', 'auth/shibboleth');
                set_config('alternateloginurl', '');
            }
            $config->alt_login = 'off';
        }

        // Check values and return false if something is wrong
        // Patch Anyware Technologies (14/05/07)
        if (($config->convert_data != '') && (!file_exists($config->convert_data) || !is_readable($config->convert_data))) {
            return false;
        }

        // Check if there is at least one entry in the IdP list
        if (isset($config->organization_selection) && empty($config->organization_selection) && isset($config->alt_login) && $config->alt_login == 'on') {
            return false;
        }

        // ...awag: additional settings for DLB.
        if (!isset($config->editmebisprofileurl)) {

            $config->editmebisprofileurl = '';
        }
        set_config('editmebisprofileurl', $config->editmebisprofileurl, 'auth/shibboleth');

        if (!isset($config->editusersurl)) {

            $config->editusersurl = '';
        }
        set_config('editusersurl', $config->editusersurl, 'auth/shibboleth');

        if (!isset($config->categorierole)) {

            $config->categorierole = 0;
        }
        set_config('categorierole', $config->categorierole, 'auth/shibboleth');

        return true;
    }

    /**
     * Cleans and returns first of potential many values (multi-valued attributes)
     *
     * @param string $string Possibly multi-valued attribute from Shibboleth
     */
    function get_first_string($string) {
        $list = explode(';', $string);
        $clean_string = rtrim($list[0]);

        return $clean_string;
    }

    /**
     * Sets the standard SAML domain cookie that is also used to preselect
     * the right entry on the local wayf
     *
     * @param IdP identifiere
     */
    function set_saml_cookie($selectedIDP) {
        if (isset($_COOKIE['_saml_idp'])) {
            $IDPArray = generate_cookie_array($_COOKIE['_saml_idp']);
        } else {
            $IDPArray = array();
        }
        $IDPArray = appendCookieValue($selectedIDP, $IDPArray);
        setcookie('_saml_idp', generate_cookie_value($IDPArray), time() + (100 * 24 * 3600));
    }

    /**
     * Prints the option elements for the select element of the drop down list
     *
     */
    function print_idp_list() {
        $config = get_config('auth/shibboleth');

        $IdPs = get_idp_list($config->organization_selection);
        if (isset($_COOKIE['_saml_idp'])) {
            $idp_cookie = generate_cookie_array($_COOKIE['_saml_idp']);
            do {
                $selectedIdP = array_pop($idp_cookie);
            } while (!isset($IdPs[$selectedIdP]) && count($idp_cookie) > 0);
        } else {
            $selectedIdP = '-';
        }

        foreach ($IdPs as $IdP => $data) {
            if ($IdP == $selectedIdP) {
                echo '<option value="' . $IdP . '" selected="selected">' . $data[0] . '</option>';
            } else {
                echo '<option value="' . $IdP . '">' . $data[0] . '</option>';
            }
        }
    }

    /**
     * Generate array of IdPs from Moodle Shibboleth settings
     *
     * @param string Text containing tuble/triple of IdP entityId, name and (optionally) session initiator
     * @return array Identifier of IdPs and their name/session initiator
     */
    function get_idp_list($organization_selection) {
        $idp_list = array();

        $idp_raw_list = explode("\n", $organization_selection);

        foreach ($idp_raw_list as $idp_line) {
            $idp_data = explode(',', $idp_line);
            if (isset($idp_data[2])) {
                $idp_list[trim($idp_data[0])] = array(trim($idp_data[1]), trim($idp_data[2]));
            } elseif (isset($idp_data[1])) {
                $idp_list[trim($idp_data[0])] = array(trim($idp_data[1]));
            }
        }

        return $idp_list;
    }

    /**
     * Generates an array of IDPs using the cookie value
     *
     * @param string Value of SAML domain cookie
     * @return array Identifiers of IdPs
     */
    function generate_cookie_array($value) {

        // Decodes and splits cookie value
        $CookieArray = explode(' ', $value);
        $CookieArray = array_map('base64_decode', $CookieArray);

        return $CookieArray;
    }

    /**
     * Generate the value that is stored in the cookie using the list of IDPs
     *
     * @param array IdP identifiers
     * @return string SAML domain cookie value
     */
    function generate_cookie_value($CookieArray) {

        // Merges cookie content and encodes it
        $CookieArray = array_map('base64_encode', $CookieArray);
        $value = implode(' ', $CookieArray);
        return $value;
    }

    /**
     * Append a value to the array of IDPs
     *
     * @param string IdP identifier
     * @param array IdP identifiers
     * @return array IdP identifiers with appended IdP
     */
    function appendCookieValue($value, $CookieArray) {

        array_push($CookieArray, $value);
        $CookieArray = array_reverse($CookieArray);
        $CookieArray = array_unique($CookieArray);
        $CookieArray = array_reverse($CookieArray);

        return $CookieArray;
    }

// ++++ awag: From here on adapted for DLB to use with new LDAP-Portal, Andreas Wagner ++++

    /**
     * Returns the URL for changing the users' passwords, or empty if the default
     * URL can be used.
     *
     * This method is used if can_change_password() returns true in the settings navigation
     * but it is used in toolbar_settings menu of DLB theme without this check.
     * 
     * This method is called only when user is logged in, it may use global $USER.
     *
     * @return moodle_url url of the profile page or null if standard used
     */
    function change_password_url() {
        if (!empty($this->config->changepasswordurl)) {
            return $this->config->changepasswordurl;
        }
        return '';
    }

    /**
     *  the Mebis Profile URL is the url to the idm of Mebis and is different from 
     *  the moodle internal profile. Some parts (i. e. user name, firstname, lastname, email
     *  are synchronized to the internal profile.
     */
    function edit_mebis_profile() {
        if (!empty($this->config->editmebisprofileurl)) {
            return $this->config->editmebisprofileurl;
        }
        return '';
    }

    /**
     * Returns true if this authentication plugin can edit the users'
     * profile.
     *
     * @return bool
     */
    function can_edit_profile() {
        //override if needed
        return true;
    }

    /**
     * Returns the URL for editing the users' moodle-specific settings.
     * For all users (except admins) we use edit.php, which is a modified version
     * of originally moodles edit.php, which allows various settings but only settings
     * regarding this moodle application.
     *
     * @return moodle_url url of the profile page or null if standard used
     */
    function edit_profile_url() {
        // ... return null to use the >>>modified<<< edit.php page
        return null;
    }

    /** returns the URL for editing other users profiles in the LDAP-Portal
     * 
     * @return String
     */
    function edit_users_url() {
        return $this->config->editusersurl;
    }

    /** assign the configurated role in users home school (i. e. in the category, 
     * with the idnummer of users schools)
     * 
     * @global type $DB
     * @param type $user
     * @return boolean) , when user is a nutzerverwl
     */
    private function assign_role_in_home_category(&$user) {
        global $DB;

        if (empty($this->config->categorierole)) {
            return false;
        }

        if (empty($user->institution)) {
            return false;
        }

        // ...get the category with the id of users institution
        if (!$category = $DB->get_record('course_categories', array('idnumber' => $user->institution))) {
            return false;
        }

        // ...check, if role is already assigned.
        $context = context_coursecat::instance($category->id);
        $roles = get_user_roles($context, $user->id);

        // ...if not assign it.
        if (empty($roles) or ( !in_array($this->config->categorierole, $roles))) {
            role_assign($this->config->categorierole, $user->id, $context->id);
        }

        return true;
    }

    /** awag: override the authenticated hook to retrieve additional Shibboletz Header Vars
     * 
     * @global type $SESSION
     * @param type $user
     * @param type $username
     * @param type $password
     */
    function user_authenticated_hook(&$user, $username, $password) {

        if ($user->auth != 'shibboleth') {
            return false;
        }

        // ...Default-Values for the $USER record.
        $user->mebisKlassenListe = array();
        $user->mebisRole = array();
        $user->isTeacher = 0;

        // ... get the mebisKlassenListe for this user, if available.
        if (!empty($_SERVER["mebisKlassenListe"])) {

            $user->mebisKlassenListe = explode(";", $_SERVER["mebisKlassenListe"]);
        }

        // ... now setup mebisRole in Sessiondata, we have no $USER record yet.
        if (!empty($_SERVER["mebisRole"])) {

            $user->mebisRole = explode(";", strtolower($_SERVER["mebisRole"]));
            $user->isTeacher = in_array("lehrer", $user->mebisRole);

            if (in_array('nutzerverwalter', $user->mebisRole)) {
                // assign the $config->categorierole to user, don't care about return value.
                $this->assign_role_in_home_category($user);
            }
        } else {

            debugging('received no mebis-role in auth/shibboleth/auth.php für user: ' . $username);
        }

        // assign beta tester role in system context
        if (!empty($_SERVER["mebisBetaAccess"])) {
            global $DB;
            if ($role = $DB->get_record('role', array('shortname' => 'betatester'))) {
                $ctx = context_system::instance();
                $userroles = get_user_roles($ctx, $user->id);
                if ($_SERVER["mebisBetaAccess"] == "TRUE" && !array_key_exists($role->id, $userroles)) {
                    role_assign($role->id, $user->id, $ctx->id);
                }                
            }
        }
    }
}
