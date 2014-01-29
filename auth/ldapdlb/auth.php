<?php

/**
 * @author Martin Dougiamas
 * @author I�aki Arenaza
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package moodle multiauth
 *
 * Authentication Plugin: LDAP Authentication
 *
 * Authentication using LDAP (Lightweight Directory Access Protocol).
 *
 * 2006-08-28  File created.
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

// See http://support.microsoft.com/kb/305144 to interprete these values.
if (!defined('AUTH_AD_ACCOUNTDISABLE')) {
    define('AUTH_AD_ACCOUNTDISABLE', 0x0002);
}
if (!defined('AUTH_AD_NORMAL_ACCOUNT')) {
    define('AUTH_AD_NORMAL_ACCOUNT', 0x0200);
}
if (!defined('AUTH_NTLMTIMEOUT')) {  // timewindow for the NTLM SSO process, in secs...
    define('AUTH_NTLMTIMEOUT', 10);
}

// UF_DONT_EXPIRE_PASSWD value taken from MSDN directly
if (!defined('UF_DONT_EXPIRE_PASSWD')) {
    define ('UF_DONT_EXPIRE_PASSWD', 0x00010000);
}

// The Posix uid and gid of the 'nobody' account and 'nogroup' group.
if (!defined('AUTH_UID_NOBODY')) {
    define('AUTH_UID_NOBODY', -2);
}
if (!defined('AUTH_GID_NOGROUP')) {
    define('AUTH_GID_NOGROUP', -2);
}

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->libdir.'/ldaplib.php');
require_once($CFG->dirroot.'/auth/ldap/auth.php');

/**
 * LDAP authentication plugin.
 */
class auth_plugin_ldapdlb extends auth_plugin_ldap {
	
	 var $sArr;
	 var $roleArr;

    /**
     * Init plugin config from database settings depending on the plugin auth type.
     */
    function init_plugin($authtype) {
        $this->pluginconfig = 'auth/'.$authtype;
        $this->config = get_config($this->pluginconfig);
        if (empty($this->config->ldapencoding)) {
            $this->config->ldapencoding = 'utf-8';
        }
        if (empty($this->config->user_type)) {
            $this->config->user_type = 'default';
        }

        $ldap_usertypes = ldap_supported_usertypes();
        $this->config->user_type_name = $ldap_usertypes[$this->config->user_type];
        unset($ldap_usertypes);

        $default = ldap_getdefaults();

        // Use defaults if values not given
        foreach ($default as $key => $value) {
            // watch out - 0, false are correct values too
            if (!isset($this->config->{$key}) or $this->config->{$key} == '') {
                $this->config->{$key} = $value[$this->config->user_type];
            }
        }

        // Hack prefix to objectclass
        if (empty($this->config->objectclass)) {
            // Can't send empty filter
            $this->config->objectclass = '(objectClass=*)';
        } else if (stripos($this->config->objectclass, 'objectClass=') === 0) {
            // Value is 'objectClass=some-string-here', so just add ()
            // around the value (filter _must_ have them).
            $this->config->objectclass = '('.$this->config->objectclass.')';
        } else if (strpos($this->config->objectclass, '(') !== 0) {
            // Value is 'some-string-not-starting-with-left-parentheses',
            // which is assumed to be the objectClass matching value.
            // So build a valid filter with it.
            $this->config->objectclass = '(objectClass='.$this->config->objectclass.')';
        } else {
            // There is an additional possible value
            // '(some-string-here)', that can be used to specify any
            // valid filter string, to select subsets of users based
            // on any criteria. For example, we could select the users
            // whose objectClass is 'user' and have the
            // 'enabledMoodleUser' attribute, with something like:
            //
            //   (&(objectClass=user)(enabledMoodleUser=1))
            //
            // In this particular case we don't need to do anything,
            // so leave $this->config->objectclass as is.
        }
		
        if (!empty($this->config->organisationlist)) {
		$strOrganisations = $this->config->organisationlist;
		$lines = explode("\n", $strOrganisations);
		foreach ($lines as $line) {
			$line = trim($line);
			$org = explode("=", $line);
			$key = $org[0];
			$value = $org[1];
			$this->sArr[$key] = $value;
		}
        }
    }

    /**
     * Constructor with initialisation.
     */
    function auth_plugin_ldapdlb() {
        $this->authtype = 'ldapdlb';
        $this->roleauth = 'auth_ldap';
        $this->errorlogtag = '[AUTH LDAPDLB]';
		$this->sArr = array();
		$this->roleArr = array("Lehrer", "Schüler");
        $this->init_plugin($this->authtype);
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username (without system magic quotes)
     * @param string $password The password (without system magic quotes)
     *
     * @return bool Authentication success or failure.
     */
    function user_login($username, $password) {
		global $USER, $SESSION;
		
        if (! function_exists('ldap_bind')) {
            print_error('auth_ldapnotinstalled', 'auth_ldap');
            return false;
        }

        if (!$username or !$password) {    // Don't allow blank usernames or passwords
            return false;
        }

        $extusername = textlib::convert($username, 'utf-8', $this->config->ldapencoding);
        $extpassword = textlib::convert($password, 'utf-8', $this->config->ldapencoding);

        // Before we connect to LDAP, check if this is an AD SSO login
        // if we succeed in this block, we'll return success early.
        //
        $key = sesskey();
        if (!empty($this->config->ntlmsso_enabled) && $key === $password) {
            $cf = get_cache_flags($this->pluginconfig.'/ntlmsess');
            // We only get the cache flag if we retrieve it before
            // it expires (AUTH_NTLMTIMEOUT seconds).
            if (!isset($cf[$key]) || $cf[$key] === '') {
                return false;
            }

            $sessusername = $cf[$key];
            if ($username === $sessusername) {
                unset($sessusername);
                unset($cf);

                // Check that the user is inside one of the configured LDAP contexts
                $validuser = false;
                $ldapconnection = $this->ldap_connect();
                // if the user is not inside the configured contexts,
                // ldap_find_userdn returns false.
                if ($this->ldap_find_userdn($ldapconnection, $extusername)) {
                    $validuser = true;
                }
                $this->ldap_close();

                // Shortcut here - SSO confirmed
                return $validuser;
            }
        } // End SSO processing
        unset($key);

        $ldapconnection = $this->ldap_connect();
        $ldap_user_dn = $this->ldap_find_userdn($ldapconnection, $extusername);
		
        // If ldap_user_dn is empty, user does not exist
        if (!$ldap_user_dn) {
            $this->ldap_close();
            return false;
        }
		// get schooltype
        $ldap_user_schooltype = $this->dn_get_schooltype($ldap_user_dn);

		// Try to bind with current username and password
		$ldap_login = @ldap_bind($ldapconnection, $ldap_user_dn, $extpassword);
        $this->ldap_close();
		if (!($this->config->local_subcontext) or ($this->config->local_subcontext == $ldap_user_schooltype)) {
        	if ($ldap_login) {
				$SESSION->isTeacher = (strtolower($this->dn_get_role($ldap_user_dn)) == "lehrer") ? 1 : 0;
        	    return true;
       		}
       		return false;
		} else {
        	if ($ldap_login) {
				$USER->username = $username;
				$USER->password = $password;
				$SESSION->ldapurl = $this->sArr[$ldap_user_schooltype];
			}
			return false;
		}
    }

    /**
     * Reads user information from ldap and returns it in array()
     *
     * Function should return all information available. If you are saving
     * this information to moodle user-table you should honor syncronization flags
     *
     * @param string $username username
     *
     * @return mixed array with no magic quotes or false on error
     */
    function get_userinfo($username) {
        $extusername = textlib::convert($username, 'utf-8', $this->config->ldapencoding);

        $ldapconnection = $this->ldap_connect();
        if(!($user_dn = $this->ldap_find_userdn($ldapconnection, $extusername))) {
            return false;
        }

        $search_attribs = array();
        $attrmap = $this->ldap_attributes();
        foreach ($attrmap as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $value) {
                if (!in_array($value, $search_attribs)) {
                    array_push($search_attribs, $value);
                }
            }
        }

        if (!$user_info_result = ldap_read($ldapconnection, $user_dn, '(objectClass=*)', $search_attribs)) {
            return false; // error!
        }

        $user_entry = ldap_get_entries_moodle($ldapconnection, $user_info_result);
        if (empty($user_entry)) {
            return false; // entry not found
        }

        $result = array();
        foreach ($attrmap as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            $ldapval = NULL;
            foreach ($values as $value) {
                $entry = array_change_key_case($user_entry[0], CASE_LOWER);
                if (($value == 'dn') || ($value == 'distinguishedname')) {
                    $result[$key] = $user_dn;
                    continue;
                }
                if (!array_key_exists($value, $entry)) {
                    continue; // wrong data mapping!
                }
                if (is_array($entry[$value])) {
                    $newval = textlib::convert($entry[$value][0], $this->config->ldapencoding, 'utf-8');
                } else {
                    $newval = textlib::convert($entry[$value], $this->config->ldapencoding, 'utf-8');
                }
                if (!empty($newval)) { // favour ldap entries that are set
                    $ldapval = $newval;
                }
            }
            if (!is_null($ldapval)) {
                $result[$key] = $ldapval;
            }
        }
		
		$result['institution'] = $this->ldap_find_user_schoolid($ldapconnection, $user_dn);
		$result['city'] = $this->dn_get_school($user_dn);
		
        $this->ldap_close();
        return $result;
    }

    /**
     * Creates a new user on LDAP.
     * By using information in userobject
     * Use user_exists to prevent duplicate usernames
     *
     * @param mixed $userobject  Moodle userobject
     * @param mixed $plainpass   Plaintext password
     */
    function user_create($userobject, $plainpass, $schoolid='', $role='Schüler', $printerror=1) {
        $extusername = textlib::convert($userobject->username, 'utf-8', $this->config->ldapencoding);
        $extpassword = textlib::convert($plainpass, 'utf-8', $this->config->ldapencoding);

        switch ($this->config->passtype) {
            case 'md5':
                $extpassword = '{MD5}' . base64_encode(pack('H*', md5($extpassword)));
                break;
            case 'sha1':
                $extpassword = '{SHA}' . base64_encode(pack('H*', sha1($extpassword)));
                break;
            case 'plaintext':
            default:
                break; // plaintext
        }

        $ldapconnection = $this->ldap_connect();
        $attrmap = $this->ldap_attributes();

        $newuser = array();

        foreach ($attrmap as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $value) {
                if (!empty($userobject->$key) ) {
                    $newuser[$value] = textlib::convert($userobject->$key, 'utf-8', $this->config->ldapencoding);
                }
            }
        }

        //Following sets all mandatory and other forced attribute values
        //User should be creted as login disabled untill email confirmation is processed
        //Feel free to add your user type and send patches to paca@sci.fi to add them
        //Moodle distribution

        switch ($this->config->user_type)  {
            case 'edir':
                $newuser['objectClass']   = array('inetOrgPerson', 'organizationalPerson', 'person', 'top');
                $newuser['uniqueId']      = $extusername;
                $newuser['logindisabled'] = 'TRUE';
                $newuser['userpassword']  = $extpassword;
                $uadd = ldap_add($ldapconnection, $this->config->user_attribute.'='.ldap_addslashes($extusername).','.$this->config->create_context, $newuser);
                break;
            case 'rfc2307':
            case 'rfc2307bis':
                // posixAccount object class forces us to specify a uidNumber
                // and a gidNumber. That is quite complicated to generate from
                // Moodle without colliding with existing numbers and without
                // race conditions. As this user is supposed to be only used
                // with Moodle (otherwise the user would exist beforehand) and
                // doesn't need to login into a operating system, we assign the
                // user the uid of user 'nobody' and gid of group 'nogroup'. In
                // addition to that, we need to specify a home directory. We
                // use the root directory ('/') as the home directory, as this
                // is the only one can always be sure exists. Finally, even if
                // it's not mandatory, we specify '/bin/false' as the login
                // shell, to prevent the user from login in at the operating
                // system level (Moodle ignores this).

                $newuser['objectClass']   = array('posixAccount', 'inetOrgPerson', 'organizationalPerson', 'person', 'top');
                $newuser['cn']            = $extusername;
                $newuser['uid']           = $extusername;
                $newuser['uidNumber']     = AUTH_UID_NOBODY;
                $newuser['gidNumber']     = AUTH_GID_NOGROUP;
                $newuser['homeDirectory'] = '/';
                $newuser['loginShell']    = '/bin/false';

                // IMPORTANT:
                // We have to create the account locked, but posixAccount has
                // no attribute to achive this reliably. So we are going to
                // modify the password in a reversable way that we can later
                // revert in user_activate().
                //
                // Beware that this can be defeated by the user if we are not
                // using MD5 or SHA-1 passwords. After all, the source code of
                // Moodle is available, and the user can see the kind of
                // modification we are doing and 'undo' it by hand (but only
                // if we are using plain text passwords).
                //
                // Also bear in mind that you need to use a binding user that
                // can create accounts and has read/write privileges on the
                // 'userPassword' attribute for this to work.

                $newuser['userPassword']  = '*'.$extpassword;
                $uadd = ldap_add($ldapconnection, $this->config->user_attribute.'='.ldap_addslashes($extusername).','.$this->config->create_context, $newuser);
                break;
            case 'ad':
                // User account creation is a two step process with AD. First you
                // create the user object, then you set the password. If you try
                // to set the password while creating the user, the operation
                // fails.

                // Passwords in Active Directory must be encoded as Unicode
                // strings (UCS-2 Little Endian format) and surrounded with
                // double quotes. See http://support.microsoft.com/?kbid=269190
                if (!function_exists('mb_convert_encoding')) {
                    print_error('auth_ldap_no_mbstring', 'auth_ldap');
                }

                // Check for invalid sAMAccountName characters.
                if (preg_match('#[/\\[\]:;|=,+*?<>@"]#', $extusername)) {
					if ($printerror) {
                    	print_error ('auth_ldap_ad_invalidchars', 'auth_ldap');
					} else {
						return false;
					}
                }

                // First create the user account, and mark it as disabled.
                $newuser['objectClass'] = array('top', 'person', 'user', 'organizationalPerson');
                $newuser['sAMAccountName'] = $extusername;
                $newuser['userAccountControl'] = AUTH_AD_NORMAL_ACCOUNT |
                                                 AUTH_AD_ACCOUNTDISABLE;
                $userdn = 'cn='.ldap_addslashes($extusername).','.$this->config->create_context;
                if (!ldap_add($ldapconnection, $userdn, $newuser)) {
                   	print_error('auth_ldap_ad_create_req', 'auth_ldap');
                }

                // Now set the password
                unset($newuser);
                $newuser['unicodePwd'] = mb_convert_encoding('"' . $extpassword . '"',
                                                             'UCS-2LE', 'UTF-8');
                if(!ldap_modify($ldapconnection, $userdn, $newuser)) {
                    // Something went wrong: delete the user account and error out
                    ldap_delete ($ldapconnection, $userdn);
                   	print_error('auth_ldap_ad_create_req', 'auth_ldap');  
                }
                $uadd = true;
                break;
			case 'default':
				$newuser['objectClass']   = array('inetOrgPerson', 'organizationalPerson', 'person', 'top');
                $newuser['uid']           = $extusername;
				$newuser['cn']            = $userobject->firstname;
                $newuser['sn']			  = $userobject->lastname;
				$newuser['employeenumber']  = $userobject->idnumber;
				$newuser['userPassword']  = $extpassword;
				
				// find school in LDAP
				$schooldn = $this->ldap_find_schooldn($ldapconnection, $schoolid);
				if (!$schooldn) {
					print_error('auth_ldapdlb_invalidschoolid', 'auth_ldapdlb');
				}
					
				// check user role
				if (!in_array($role, $this->roleArr)) {
					if ($printerror) {
                    	print_error('auth_ldapdlb_invalidrole', 'auth_ldapdlb');
					} else {
						$this->ldap_close();
						return false;
					}  
				}
						
				$dnArr = array_reverse(explode(",", ldap_dn2ufn($schooldn)));
				$ldap_school = trim($dnArr[2]);
				$ldap_schooltype = trim($dnArr[1]);
							
				$userdn = $this->config->user_attribute . '=' . ldap_addslashes($extusername).
						  ',ou='. ldap_addslashes($role) . 
						  ',ou='. ldap_addslashes($ldap_school) . 
						  ',ou='. ldap_addslashes($ldap_schooltype) .
						  ',' . $this->config->create_context;
								
                $uadd = ldap_add($ldapconnection, $userdn, $newuser);
				if (!$uadd) {
					if ($printerror) {
                    	print_error('auth_ldap_ad_create_req', 'auth_ldap');
					} else {
						$this->ldap_close();
						return false;
					}    
                }
				break;
            default:
                print_error('auth_ldap_unsupportedusertype', 'auth_ldap', '', $this->config->user_type_name);
        }
        $this->ldap_close();
        return $uadd;
    }

    /**
     * Sign up a new user ready for confirmation.
     * Password is passed in plaintext.
     *
     * @param object $user new user object
     * @param boolean $notify print notice with link and terminate
     */
    function user_signup($user, $notify=true) {
        global $CFG, $DB, $PAGE, $OUTPUT;

        require_once($CFG->dirroot.'/user/profile/lib.php');

        if ($this->user_exists($user->username)) {
            print_error('auth_ldap_user_exists', 'auth_ldap');
        }

        $plainslashedpassword = $user->password;
        unset($user->password);

        if (! $this->user_create($user, $plainslashedpassword)) {
            print_error('auth_ldap_create_error', 'auth_ldap');
        }

        $user->id = $DB->insert_record('user', $user);

        // Save any custom profile field information
        profile_save_data($user);

        $this->update_user_record($user->username);
        update_internal_user_password($user, $plainslashedpassword);

        $user = $DB->get_record('user', array('id'=>$user->id));
        events_trigger('user_created', $user);

        if (! send_confirmation_email($user)) {
            print_error('noemail', 'auth_ldap');
        }

        if ($notify) {
            $emailconfirm = get_string('emailconfirm');
            $PAGE->set_url('/auth/ldapdlb/auth.php');
            $PAGE->navbar->add($emailconfirm);
            $PAGE->set_title($emailconfirm);
            $PAGE->set_heading($emailconfirm);
            echo $OUTPUT->header();
            notice(get_string('emailconfirmsent', '', $user->email), "{$CFG->wwwroot}/index.php");
        } else {
            return true;
        }
    }
	
	    /**
     * Called when the user record is updated.
     *
     * Modifies user in external LDAP server. It takes olduser (before
     * changes) and newuser (after changes) compares information and
     * saves modified information to external LDAP server.
     *
     * @param mixed $olduser     Userobject before modifications    (without system magic quotes)
     * @param mixed $newuser     Userobject new modified userobject (without system magic quotes)
     * @return boolean result
     *
     */
    function user_update($olduser, $newuser) {
        global $USER;

        if (isset($olduser->username) and isset($newuser->username) and $olduser->username != $newuser->username) {
            error_log($this->errorlogtag.get_string('renamingnotallowed', 'auth_ldap'));
            return false;
        }

        if (isset($olduser->auth) and $olduser->auth != $this->authtype) {
            return true; // just change auth and skip update
        }

        $attrmap = $this->ldap_attributes();
        // Before doing anything else, make sure we really need to update anything
        // in the external LDAP server.
        $update_external = false;
        foreach ($attrmap as $key => $ldapkeys) {
            if (!empty($this->config->{'field_updateremote_'.$key})) {
                $update_external = true;
                break;
            }
        }
        if (!$update_external) {
            return true;
        }

        $extoldusername = textlib::convert($olduser->username, 'utf-8', $this->config->ldapencoding);

        $ldapconnection = $this->ldap_connect();

        $search_attribs = array();
        foreach ($attrmap as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $value) {
                if (!in_array($value, $search_attribs)) {
                    array_push($search_attribs, $value);
                }
            }
        }

        if(!($user_dn = $this->ldap_find_userdn($ldapconnection, $extoldusername))) {
            return false;
        }

        $user_info_result = ldap_read($ldapconnection, $user_dn, '(objectClass=*)', $search_attribs);
	
        if ($user_info_result) {
            $user_entry = ldap_get_entries_moodle($ldapconnection, $user_info_result);
		
            if (empty($user_entry)) {
                $attribs = join (', ', $search_attribs);
                error_log($this->errorlogtag.get_string('updateusernotfound', 'auth_ldap',
                                                          array('userdn'=>$user_dn,
                                                                'attribs'=>$attribs)));
                return false; // old user not found!
            } else if (count($user_entry) > 1) {
                error_log($this->errorlogtag.get_string('morethanoneuser', 'auth_ldap'));
                return false;
            }

            $user_entry = array_change_key_case($user_entry[0], CASE_LOWER);

            foreach ($attrmap as $key => $ldapkeys) {
                // Only process if the moodle field ($key) has changed and we
                // are set to update LDAP with it
			
                if (isset($olduser->$key) and isset($newuser->$key)
                  and $olduser->$key !== $newuser->$key
                  and !empty($this->config->{'field_updateremote_'. $key})) {
					  
                    // For ldap values that could be in more than one
                    // ldap key, we will do our best to match
                    // where they came from
                    $ambiguous = true;
                    $changed   = false;
                    if (!is_array($ldapkeys)) {
                        $ldapkeys = array($ldapkeys);
                    }
                    if (count($ldapkeys) < 2) {
                        $ambiguous = false;
                    }

                    $nuvalue = textlib::convert($newuser->$key, 'utf-8', $this->config->ldapencoding);
                    empty($nuvalue) ? $nuvalue = array() : $nuvalue;
                    $ouvalue = textlib::convert($olduser->$key, 'utf-8', $this->config->ldapencoding);

                    foreach ($ldapkeys as $ldapkey) {
                        $ldapkey   = $ldapkey;
                        $ldapvalue = $user_entry[$ldapkey][0];
                        if (!$ambiguous) {
                            // Skip update if the values already match
                            if ($nuvalue !== $ldapvalue) {
                                // This might fail due to schema validation
                                if (@ldap_modify($ldapconnection, $user_dn, array($ldapkey => $nuvalue))) {
									//echo "Feld wurde aktualisiert: " . $key . "<br>";
                                    continue;
                                } else {
                                    error_log($this->errorlogtag.get_string ('updateremfail', 'auth_ldap',
                                                                             array('errno'=>ldap_errno($ldapconnection),
                                                                                   'errstring'=>ldap_err2str(ldap_errno($ldapconnection)),
                                                                                   'key'=>$key,
                                                                                   'ouvalue'=>$ouvalue,
                                                                                   'nuvalue'=>$nuvalue)));
                                    continue;
                                }
                            }
                        } else {
                            // Ambiguous. Value empty before in Moodle (and LDAP) - use
                            // 1st ldap candidate field, no need to guess
                            if ($ouvalue === '') { // value empty before - use 1st ldap candidate
                                // This might fail due to schema validation
                                if (@ldap_modify($ldapconnection, $user_dn, array($ldapkey => $nuvalue))) {
                                    $changed = true;
                                    continue;
                                } else {
                                    error_log($this->errorlogtag.get_string ('updateremfail', 'auth_ldap',
                                                                             array('errno'=>ldap_errno($ldapconnection),
                                                                                   'errstring'=>ldap_err2str(ldap_errno($ldapconnection)),
                                                                                   'key'=>$key,
                                                                                   'ouvalue'=>$ouvalue,
                                                                                   'nuvalue'=>$nuvalue)));
                                    continue;
                                }
                            }

                            // We found which ldap key to update!
                            if ($ouvalue !== '' and $ouvalue === $ldapvalue ) {
                                // This might fail due to schema validation
                                if (@ldap_modify($ldapconnection, $user_dn, array($ldapkey => $nuvalue))) {
                                    $changed = true;
                                    continue;
                                } else {
                                    error_log($this->errorlogtag.get_string ('updateremfail', 'auth_ldap',
                                                                             array('errno'=>ldap_errno($ldapconnection),
                                                                                   'errstring'=>ldap_err2str(ldap_errno($ldapconnection)),
                                                                                   'key'=>$key,
                                                                                   'ouvalue'=>$ouvalue,
                                                                                   'nuvalue'=>$nuvalue)));
                                    continue;
                                }
                            }
                        }
                    }

                    if ($ambiguous and !$changed) {
                        error_log($this->errorlogtag.get_string ('updateremfailamb', 'auth_ldap',
                                                                 array('key'=>$key,
                                                                       'ouvalue'=>$ouvalue,
                                                                       'nuvalue'=>$nuvalue)));
                    }
                }
            }
        } else {
            error_log($this->errorlogtag.get_string ('usernotfound', 'auth_ldap'));
            $this->ldap_close();
            return false;
        }

        $this->ldap_close();
        return true;
    }
	
	/**
     * Changes userpassword in LDAP
     *
     * Called when the user password is updated. It assumes it is
     * called by an admin or that you've otherwise checked the user's
     * credentials
     *
     * @param  object  $user        User table object
     * @param  string  $newpassword Plaintext password (not crypted/md5'ed)
     * @return boolean result
     *
     */
    function user_update_password($user, $newpassword) {
        global $USER, $DB;

        $result = false;
        $username = $user->username;

        $extusername = textlib::convert($username, 'utf-8', $this->config->ldapencoding);
        $extpassword = textlib::convert($newpassword, 'utf-8', $this->config->ldapencoding);

        switch ($this->config->passtype) {
            case 'md5':
                $extpassword = '{MD5}' . base64_encode(pack('H*', md5($extpassword)));
                break;
            case 'sha1':
                $extpassword = '{SHA}' . base64_encode(pack('H*', sha1($extpassword)));
                break;
            case 'plaintext':
            default:
                break; // plaintext
        }

        $ldapconnection = $this->ldap_connect();

        $user_dn = $this->ldap_find_userdn($ldapconnection, $extusername);

        if (!$user_dn) {
            error_log($this->errorlogtag.get_string ('nodnforusername', 'auth_ldap', $user->username));
            return false;
        }

        // Send LDAP the password in cleartext, it will md5 it itself
        $result = ldap_modify($ldapconnection, $user_dn, array('userPassword' => $extpassword));
        if (!$result) {
            error_log($this->errorlogtag.get_string ('updatepasserror', 'auth_ldap',
                                                     array('errno'=>ldap_errno($ldapconnection),
                                                           'errstring'=>ldap_err2str(ldap_errno($ldapconnection)))));
        }

        $this->ldap_close();
		
		// change authentication-type from ldap to shibboleth
		if ($user->auth == "ldapdlb") {
			$user->auth = "shibboleth";
			$DB->update_record('user', $user);	
		}
		
        return $result;
    }


     /**
     * Changes user-mail in LDAP
     *
     * Called when the user mail is updated.
     *
     * @param  object  $user        User table object
     * @param  string  $newmail New Mail string
     * @return boolean result
     *
     */
    function user_update_mail($user, $newmail) {
       global $USER, $DB;

        $result = false;
        $username = $user->username;

        $extusername = textlib::convert($username, 'utf-8', $this->config->ldapencoding);
        $extmail = textlib::convert($newmail, 'utf-8', $this->config->ldapencoding);

        $ldapconnection = $this->ldap_connect();

        $user_dn = $this->ldap_find_userdn($ldapconnection, $extusername);

        if (!$user_dn) {
            error_log($this->errorlogtag.get_string ('nodnforusername', 'auth_ldap', $user->username));
            return false;
        }

        // Send LDAP the mail
        $result = ldap_modify($ldapconnection, $user_dn, array('mail' => $extmail));
        if (!$result) {
            error_log($this->errorlogtag.get_string ('updatepasserror', 'auth_ldap',
                                                     array('errno'=>ldap_errno($ldapconnection),
                                                           'errstring'=>ldap_err2str(ldap_errno($ldapconnection)))));
        }

        $this->ldap_close();
        return $result;
    }

    /**
     * Will get called before the login page is shownr. Ff NTLM SSO
     * is enabled, and the user is in the right network, we'll redirect
     * to the magic NTLM page for SSO...
     *
     */
    function loginpage_hook() {
        global $CFG, $SESSION;

        // HTTPS is potentially required
        //httpsrequired(); - this must be used before setting the URL, it is already done on the login/index.php

        if (($_SERVER['REQUEST_METHOD'] === 'GET'         // Only on initial GET of loginpage
             || ($_SERVER['REQUEST_METHOD'] === 'POST'
                 && (get_referer() != strip_querystring(qualified_me()))))
                                                          // Or when POSTed from another place
                                                          // See MDL-14071
            && !empty($this->config->ntlmsso_enabled)     // SSO enabled
            && !empty($this->config->ntlmsso_subnet)      // have a subnet to test for
            && empty($_GET['authldap_skipntlmsso'])       // haven't failed it yet
            && (isguestuser() || !isloggedin())           // guestuser or not-logged-in users
            && address_in_subnet(getremoteaddr(), $this->config->ntlmsso_subnet)) {

            // First, let's remember where we were trying to get to before we got here
            if (empty($SESSION->wantsurl)) {
                $SESSION->wantsurl = (array_key_exists('HTTP_REFERER', $_SERVER) &&
                                      $_SERVER['HTTP_REFERER'] != $CFG->wwwroot &&
                                      $_SERVER['HTTP_REFERER'] != $CFG->wwwroot.'/' &&
                                      $_SERVER['HTTP_REFERER'] != $CFG->httpswwwroot.'/login/' &&
                                      $_SERVER['HTTP_REFERER'] != $CFG->httpswwwroot.'/login/index.php')
                    ? $_SERVER['HTTP_REFERER'] : NULL;
            }

            // Now start the whole NTLM machinery.
            if(!empty($this->config->ntlmsso_ie_fastpath)) {
                // Shortcut for IE browsers: skip the attempt page
                if(check_browser_version('MSIE')) {
                    $sesskey = sesskey();
                    redirect($CFG->wwwroot.'/auth/ldapdlb/ntlmsso_magic.php?sesskey='.$sesskey);
                } else {
                    redirect($CFG->httpswwwroot.'/login/index.php?authldap_skipntlmsso=1');
                }
            } else {
                redirect($CFG->wwwroot.'/auth/ldapdlb/ntlmsso_attempt.php');
            }
        }

        // No NTLM SSO, Use the normal login page instead.

        // If $SESSION->wantsurl is empty and we have a 'Referer:' header, the login
        // page insists on redirecting us to that page after user validation. If
        // we clicked on the redirect link at the ntlmsso_finish.php page (instead
        // of waiting for the redirection to happen) then we have a 'Referer:' header
        // we don't want to use at all. As we can't get rid of it, just point
        // $SESSION->wantsurl to $CFG->wwwroot (after all, we came from there).
        if (empty($SESSION->wantsurl)
            && (get_referer() == $CFG->httpswwwroot.'/auth/ldapdlb/ntlmsso_finish.php')) {

            $SESSION->wantsurl = $CFG->wwwroot;
        }
    }
	
	function logoutpage_hook() {
		global $redirect;
		$redirect = $this->config->logouturl;
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
        global $CFG, $OUTPUT;

        if (!function_exists('ldap_connect')) { // Is php-ldap really there?
            echo $OUTPUT->notification(get_string('auth_ldap_noextension', 'auth_ldap'));
            return;
        }

        include($CFG->dirroot.'/auth/ldapdlb/config.html');
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     */
    function process_config($config) {
        // Set to defaults if undefined
        if (!isset($config->logouturl)) {
             $config->logouturl = '';
        }
		if (!isset($config->host_url)) {
             $config->host_url = '';
        }
        if (empty($config->ldapencoding)) {
         $config->ldapencoding = 'utf-8';
        }
        if (!isset($config->contexts)) {
             $config->contexts = '';
        }
		if (!isset($config->local_subcontext)) {
             $config->local_subcontext = '';
        }
        if (!isset($config->user_type)) {
             $config->user_type = 'default';
        }
        if (!isset($config->user_attribute)) {
             $config->user_attribute = '';
        }
        if (!isset($config->search_sub)) {
             $config->search_sub = '';
        }
        if (!isset($config->opt_deref)) {
             $config->opt_deref = LDAP_DEREF_NEVER;
        }
        if (!isset($config->preventpassindb)) {
             $config->preventpassindb = 0;
        }
        if (!isset($config->bind_dn)) {
            $config->bind_dn = '';
        }
        if (!isset($config->bind_pw)) {
            $config->bind_pw = '';
        }
        if (!isset($config->ldap_version)) {
            $config->ldap_version = '3';
        }
        if (!isset($config->objectclass)) {
            $config->objectclass = '';
        }
		if (!isset($config->organisationlist)) {
            $config->organisationlist = '';
        }
        if (!isset($config->memberattribute)) {
            $config->memberattribute = '';
        }
        if (!isset($config->memberattribute_isdn)) {
            $config->memberattribute_isdn = '';
        }
        if (!isset($config->creators)) {
            $config->creators = '';
        }
        if (!isset($config->create_context)) {
            $config->create_context = '';
        }
        if (!isset($config->expiration)) {
            $config->expiration = '';
        }
        if (!isset($config->expiration_warning)) {
            $config->expiration_warning = '10';
        }
        if (!isset($config->expireattr)) {
            $config->expireattr = '';
        }
        if (!isset($config->gracelogins)) {
            $config->gracelogins = '';
        }
        if (!isset($config->graceattr)) {
            $config->graceattr = '';
        }
        if (!isset($config->auth_user_create)) {
            $config->auth_user_create = '';
        }
        if (!isset($config->forcechangepassword)) {
            $config->forcechangepassword = 0;
        }
        if (!isset($config->stdchangepassword)) {
            $config->stdchangepassword = 0;
        }
        if (!isset($config->passtype)) {
            $config->passtype = 'plaintext';
        }
        if (!isset($config->changepasswordurl)) {
            $config->changepasswordurl = '';
        }
        if (!isset($config->removeuser)) {
            $config->removeuser = AUTH_REMOVEUSER_KEEP;
        }
		if (!isset($config->autodeleteinterval)) {
            $config->autodeleteinterval = 0;
        }
        if (!isset($config->ntlmsso_enabled)) {
            $config->ntlmsso_enabled = 0;
        }
        if (!isset($config->ntlmsso_subnet)) {
            $config->ntlmsso_subnet = '';
        }
        if (!isset($config->ntlmsso_ie_fastpath)) {
            $config->ntlmsso_ie_fastpath = 0;
        }
        if (!isset($config->ntlmsso_type)) {
            $config->ntlmsso_type = 'ntlm';
        }

        // Save settings
		set_config('logouturl', trim($config->logouturl), $this->pluginconfig);
        set_config('host_url', trim($config->host_url), $this->pluginconfig);
        set_config('ldapencoding', trim($config->ldapencoding), $this->pluginconfig);
        set_config('contexts', trim($config->contexts), $this->pluginconfig);
		set_config('local_subcontext', trim($config->local_subcontext), $this->pluginconfig);
        set_config('user_type', moodle_strtolower(trim($config->user_type)), $this->pluginconfig);
        set_config('user_attribute', moodle_strtolower(trim($config->user_attribute)), $this->pluginconfig);
        set_config('search_sub', $config->search_sub, $this->pluginconfig);
        set_config('opt_deref', $config->opt_deref, $this->pluginconfig);
        set_config('preventpassindb', $config->preventpassindb, $this->pluginconfig);
        set_config('bind_dn', trim($config->bind_dn), $this->pluginconfig);
        set_config('bind_pw', $config->bind_pw, $this->pluginconfig);
        set_config('ldap_version', $config->ldap_version, $this->pluginconfig);
        set_config('objectclass', trim($config->objectclass), $this->pluginconfig);
		set_config('organisationlist', trim($config->organisationlist), $this->pluginconfig);
        set_config('memberattribute', moodle_strtolower(trim($config->memberattribute)), $this->pluginconfig);
        set_config('memberattribute_isdn', $config->memberattribute_isdn, $this->pluginconfig);
        set_config('creators', trim($config->creators), $this->pluginconfig);
        set_config('create_context', trim($config->create_context), $this->pluginconfig);
        set_config('expiration', $config->expiration, $this->pluginconfig);
        set_config('expiration_warning', trim($config->expiration_warning), $this->pluginconfig);
        set_config('expireattr', moodle_strtolower(trim($config->expireattr)), $this->pluginconfig);
        set_config('gracelogins', $config->gracelogins, $this->pluginconfig);
        set_config('graceattr', moodle_strtolower(trim($config->graceattr)), $this->pluginconfig);
        set_config('auth_user_create', $config->auth_user_create, $this->pluginconfig);
        set_config('forcechangepassword', $config->forcechangepassword, $this->pluginconfig);
        set_config('stdchangepassword', $config->stdchangepassword, $this->pluginconfig);
        set_config('passtype', $config->passtype, $this->pluginconfig);
        set_config('changepasswordurl', trim($config->changepasswordurl), $this->pluginconfig);
        set_config('removeuser', $config->removeuser, $this->pluginconfig);
		set_config('autodeleteinterval', $config->autodeleteinterval, $this->pluginconfig);
        set_config('ntlmsso_enabled', (int)$config->ntlmsso_enabled, $this->pluginconfig);
        set_config('ntlmsso_subnet', trim($config->ntlmsso_subnet), $this->pluginconfig);
        set_config('ntlmsso_ie_fastpath', (int)$config->ntlmsso_ie_fastpath, $this->pluginconfig);
        set_config('ntlmsso_type', $config->ntlmsso_type, 'auth/ldapdlb');

        return true;
    }

    /**
     * Search specified contexts for school-UD and return the school dn
     * like: ou=suborg,o=org. 
     *
     * @param resource $ldapconnection a valid LDAP connection
     * @param string $schoolid the schoolID to search (in external LDAP encoding, no db slashes)
     * @return mixed the school dn (external LDAP encoding) or false
     */	
	function ldap_find_schooldn($ldapconnection, $schoolid) {
        $ldap_contexts = explode(';', $this->config->contexts);
        if (!empty($this->config->create_context)) {
            array_push($ldap_contexts, $this->config->create_context);
        }
		
		return ldap_find_userdn($ldapconnection, $schoolid, $ldap_contexts, $this->config->objectclass,
                                'uniqueIdentifier', $this->config->search_sub);
	}
	
	 /**
     * Get Schoolname from dn
     *
     * @param string $sn a dn to search (in external LDAP encoding, no db slashes)
     * @return mixed the school name (external LDAP encoding) or false
     */	
	function dn_get_school($dn) {
        $dnArr = array_reverse(explode(",", ldap_dn2ufn($dn)));
		if (count($dnArr) < 2)
			return 0;
		return trim($dnArr[2]);
	}
	
	/**
     * Get Schooltype from dn
     *
     * @param string $sn a dn to search (in external LDAP encoding, no db slashes)
     * @return mixed the school type (external LDAP encoding) or false
     */	
	function dn_get_schooltype($dn) {
        $dnArr = array_reverse(explode(",", ldap_dn2ufn($dn)));
		if (count($dnArr) < 1)
			return 0;
		return trim($dnArr[1]);
	}
	
	/**
     * Get Role from dn
     *
     * @param string $sn a dn to search (in external LDAP encoding, no db slashes)
     * @return 1 if user is teacher
     */	
	function dn_get_role($dn) {
        $dnArr = array_reverse(explode(",", ldap_dn2ufn($dn)));
		if (count($dnArr) < 1)
			return 0;
		return trim($dnArr[3]);
	}
	
	/**
     * Search specified contexts for user-dn and return the school id
     *
     * @param resource $ldapconnection a valid LDAP connection
     * @param string $user_dn the user-dn to searchs)
     * @return mixed the school-id or false
     */
    function ldap_find_user_schoolid($ldapconnection, $user_dn) {
		$school_name = $this->dn_get_school($user_dn);
			
		$ldap_context = array($this->config->create_context);
		$school_dn = ldap_find_userdn($ldapconnection, $school_name, $ldap_context, $this->config->objectclass,
                                'ou', $this->config->search_sub);
								
		if (!$school_info_result = ldap_read($ldapconnection, $school_dn, '(objectClass=*)')) {
            return false; // error!
        }
		$school_entry = ldap_get_entries_moodle($ldapconnection, $school_info_result);
					
		return $school_entry[0]['uniqueIdentifier'][0];
    }
	
	
	/**
     * Search specified contexts for user-ID and return the user dn 
     *
     * @param resource $ldapconnection a valid LDAP connection
     * @param string $userid the userID to search (in external LDAP encoding, no db slashes)
     * @return mixed the username (external LDAP encoding) or false
     */	
	function ldap_find_user_userid($user_id) {
		$ldapconnection = $this->ldap_connect();
		$ldap_context = array($this->config->create_context);
		
		$user_dn = ldap_find_userdn($ldapconnection, $user_id, $ldap_context, $this->config->objectclass,
                                'employeeNumber', $this->config->search_sub);
	
		if (!$user_dn) {
            return false; // error!
        }
		
		if (!$user_info_result = ldap_read($ldapconnection, $user_dn, '(objectClass=*)')) {
            return false; // error!
        }
		$user_entry = ldap_get_entries_moodle($ldapconnection, $user_info_result);
		$this->ldap_close();
							
		return $user_entry[0]['uid'][0];
	}
	
	
	/**
	* Delete a user from ldap-directory
	*
	* @param userdn
	*/
	function ldap_user_delete($username) {
		$ldapconnection = $this->ldap_connect();
		
		if ($user_dn = $this->ldap_find_userdn($ldapconnection, $username)) {		
			if (ldap_delete ($ldapconnection, $user_dn)) {
				return true;
			} else {
				return false;
			}
		}
		
		return false;
	}


	/**
	* Create a username that is not used by ldap-directory
	*
	* @param firstname
	* @param lastname
	* @return username
	*/
	function ldap_create_username($firstname, $lastname) {
		$username = textlib::convert($firstname . "." . $lastname, $this->config->ldapencoding);
		$ldapconnection = $this->ldap_connect();
		$ldap_context = array($this->config->contexts);
		
		// create needle
		$strFn = textlib::convert($firstname, 'utf-8', $this->config->ldapencoding);
		$strLn = textlib::convert($lastname, 'utf-8', $this->config->ldapencoding);
		$ldap_username = $strFn . "." . $strLn;
		
		// normalize username 
		setlocale(LC_ALL, 'de_DE');
		$ldap_username = iconv("UTF-8", "ASCII//TRANSLIT", $ldap_username);
			
		// search usernames in ldap
		$i=1;
		$tmpname = $ldap_username;
			
		while ($i<100) {
			$user_dn = ldap_find_userdn($ldapconnection, $tmpname, $ldap_context, $this->config->objectclass, 'uid', 1);	
											
			if (!$user_dn) {
				$this->ldap_close();
            	return $tmpname; 
			}
			
			$tmpname = $ldap_username . $i;
			$i++;
		}
			
		$this->ldap_close();
		return false;	
	}
	
	/**
	* Auto delete users after a defined period of time
	*/
	function cron() {// Delete users who haven't confirmed within required period
	    global $CFG, $DB;
		
		$timenow  = time();
    	srand ((double) microtime() * 10000000);
    	$random100 = rand(0,100);
    	if (($random100 < 20) and (!empty($this->config->autodeleteinterval))) {     // Approximately 20% of the time.
    		mtrace("Running clean-up task: ldapdlb");
    		$cuttime = $timenow - ($this->config->autodeleteinterval * 24 * 3600);
    		$rs = $DB->get_recordset_sql ("SELECT *
        	                             FROM {user}
           		                          WHERE auth = 'ldapdlb' and firstaccess = 0 and deleted = 0
          	                               AND timecreated < ?", array($cuttime));
    		foreach ($rs as $user) {
       			delete_user($user); // we MUST delete user properly first			
	   			if ($this->ldap_user_delete($user->username)) {
        			mtrace(" Deleted unconfirmed user for ".fullname($user, true)." ($user->id)");
				}
			}
			$rs->close();
  	    }
	}
} // End of the class
