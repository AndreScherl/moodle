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
 * Bulk user registration functions
 *
 * @package    tool
 * @subpackage dlbuploaduser
 * @copyright  2004 onwards Martin Dougiamas (http://dougiamas.com)
 * @modifier   2012 Ulrich Weber
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('UU_USER_ADDNEW', 0);
define('UU_USER_ADDINC', 1);
define('UU_USER_ADD_UPDATE', 2);
define('UU_USER_UPDATE', 3);

define('UU_UPDATE_NOCHANGES', 0);
define('UU_UPDATE_FILEOVERRIDE', 1);
define('UU_UPDATE_ALLOVERRIDE', 2);
define('UU_UPDATE_MISSING', 3);

define('UU_BULK_NONE', 0);
define('UU_BULK_NEW', 1);
define('UU_BULK_UPDATED', 2);
define('UU_BULK_ALL', 3);

define('UU_PWRESET_NONE', 0);
define('UU_PWRESET_WEAK', 1);
define('UU_PWRESET_ALL', 2);

/**
 * Tracking of processed users.
 *
 * This class prints user information into a html table.
 *
 * @package    core
 * @subpackage admin
 * @copyright  2007 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class uu_progress_tracker {
    private $_row;
	/* ULI
    public $columns = array('status', 'line', 'id', 'username', 'firstname', 'lastname', 'email', 'password', 'auth', 'enrolments', 'suspended', 'deleted');
	*/ 
	public $columns = array('status', 'line', 'username', 'firstname', 'lastname', 'email', 'password', 'deleted');

    /**
     * Print table header.
     * @return void
     */
    public function start() {
        $ci = 0;
        echo '<table id="uuresults" class="generaltable boxaligncenter flexible-wrap" summary="'.get_string('uploadusersresult', 'tool_uploaduser').'">';
        echo '<tr class="heading r0">';
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('status').'</th>';
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('uucsvline', 'tool_uploaduser').'</th>';
		/* ULI
        echo '<th class="header c'.$ci++.'" scope="col">ID</th>';
		*/
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('username').'</th>';
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('firstname').'</th>';
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('lastname').'</th>';
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('email').'</th>';
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('password').'</th>';
		/* ULI
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('authentication').'</th>';
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('enrolments', 'enrol').'</th>';
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('suspended', 'auth').'</th>';
		*/
        echo '<th class="header c'.$ci++.'" scope="col">'.get_string('delete').'</th>';
        echo '</tr>';
        $this->_row = null;
    }

    /**
     * Flush previous line and start a new one.
     * @return void
     */
    public function flush() {
        if (empty($this->_row) or empty($this->_row['line']['normal'])) {
            // Nothing to print - each line has to have at least number
            $this->_row = array();
            foreach ($this->columns as $col) {
                $this->_row[$col] = array('normal'=>'', 'info'=>'', 'warning'=>'', 'error'=>'');
            }
            return;
        }
        $ci = 0;
        $ri = 1;
        echo '<tr class="r'.$ri.'">';
        foreach ($this->_row as $key=>$field) {
            foreach ($field as $type=>$content) {
                if ($field[$type] !== '') {
                    $field[$type] = '<span class="uu'.$type.'">'.$field[$type].'</span>';
                } else {
                    unset($field[$type]);
                }
            }
            echo '<td class="cell c'.$ci++.'">';
            if (!empty($field)) {
                echo implode('<br />', $field);
            } else {
                echo '&nbsp;';
            }
            echo '</td>';
        }
        echo '</tr>';
        foreach ($this->columns as $col) {
            $this->_row[$col] = array('normal'=>'', 'info'=>'', 'warning'=>'', 'error'=>'');
        }
    }

    /**
     * Add tracking info
     * @param string $col name of column
     * @param string $msg message
     * @param string $level 'normal', 'warning' or 'error'
     * @param bool $merge true means add as new line, false means override all previous text of the same type
     * @return void
     */
    public function track($col, $msg, $level = 'normal', $merge = true) {
        if (empty($this->_row)) {
            $this->flush(); //init arrays
        }
        if (!in_array($col, $this->columns)) {
            debugging('Incorrect column:'.$col);
            return;
        }
        if ($merge) {
            if ($this->_row[$col][$level] != '') {
                $this->_row[$col][$level] .='<br />';
            }
            $this->_row[$col][$level] .= $msg;
        } else {
            $this->_row[$col][$level] = $msg;
        }
    }

    /**
     * Print the table end
     * @return void
     */
    public function close() {
        $this->flush();
        echo '</table>';
    }
}

/**
 * Validation callback function - verified the column line of csv file.
 * Converts standard column names to lowercase.
 * @param csv_import_reader $cir
 * @param array $stdfields standard user fields
 * @param array $profilefields custom profile fields
 * @param moodle_url $returnurl return url in case of any error
 * @return array list of fields
 */
function uu_validate_user_upload_columns(csv_import_reader $cir, $stdfields, $profilefields, moodle_url $returnurl) {
    $columns = $cir->get_columns();

    if (empty($columns)) {
        $cir->close();
        $cir->cleanup();
        print_error('cannotreadtmpfile', 'error', $returnurl);
    }
    if (count($columns) < 2) {
        $cir->close();
        $cir->cleanup();
        print_error('csvfewcolumns', 'error', $returnurl);
    }

    // test columns
    $processed = array();
    foreach ($columns as $key=>$unused) {
        $field = $columns[$key];
        $lcfield = textlib::strtolower($field);
        if (in_array($field, $stdfields) or in_array($lcfield, $stdfields)) {
            // standard fields are only lowercase
            $newfield = $lcfield;

        } else if (in_array($field, $profilefields)) {
            // exact profile field name match - these are case sensitive
            $newfield = $field;

        } else if (in_array($lcfield, $profilefields)) {
            // hack: somebody wrote uppercase in csv file, but the system knows only lowercase profile field
            $newfield = $lcfield;

        } else if (preg_match('/^(cohort|course|group|type|role|enrolperiod)\d+$/', $lcfield)) {
            // special fields for enrolments
            $newfield = $lcfield;

        } else {
            $cir->close();
            $cir->cleanup();
            print_error('invalidfieldname', 'error', $returnurl, $field);
        }
        if (in_array($newfield, $processed)) {
            $cir->close();
            $cir->cleanup();
            print_error('duplicatefieldname', 'error', $returnurl, $newfield);
        }
        $processed[$key] = $newfield;
    }

    return $processed;
}

/**
 * Increments username - increments trailing number or adds it if not present.
 * Varifies that the new username does not exist yet
 * @param string $username
 * @return incremented username which does not exist yet
 */
 /* ULI
function uu_increment_username($username) {
    global $DB, $CFG;

    if (!preg_match_all('/(.*?)([0-9]+)$/', $username, $matches)) {
        $username = $username.'2';
    } else {
        $username = $matches[1][0].($matches[2][0]+1);
    }

    if ($DB->record_exists('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id))) {
        return uu_increment_username($username);
    } else {
        return $username;
    }
}
*/

/**
 * Check if default field contains templates and apply them.
 * @param string template - potential tempalte string
 * @param object user object- we need username, firstname and lastname
 * @return string field value
 */
function uu_process_template($template, $user) {
    if (is_array($template)) {
        // hack for for support of text editors with format
        $t = $template['text'];
    } else {
        $t = $template;
    }
    if (strpos($t, '%') === false) {
        return $template;
    }

    $username  = isset($user->username)  ? $user->username  : '';
    $firstname = isset($user->firstname) ? $user->firstname : '';
    $lastname  = isset($user->lastname)  ? $user->lastname  : '';

    $callback = partial('uu_process_template_callback', $username, $firstname, $lastname);

    $result = preg_replace_callback('/(?<!%)%([+-~])?(\d)*([flu])/', $callback, $t);

    if (is_null($result)) {
        return $template; //error during regex processing??
    }

    if (is_array($template)) {
        $template['text'] = $result;
        return $t;
    } else {
        return $result;
    }
}

/**
 * Internal callback function.
 */
function uu_process_template_callback($username, $firstname, $lastname, $block) {
    switch ($block[3]) {
        case 'u':
            $repl = $username;
            break;
        case 'f':
            $repl = $firstname;
            break;
        case 'l':
            $repl = $lastname;
            break;
        default:
            return $block[0];
    }

    switch ($block[1]) {
        case '+':
            $repl = textlib::strtoupper($repl);
            break;
        case '-':
            $repl = textlib::strtolower($repl);
            break;
        case '~':
            $repl = textlib::strtotitle($repl);
            break;
    }

    if (!empty($block[2])) {
        $repl = textlib::substr($repl, 0 , $block[2]);
    }

    return $repl;
}

/**
 * Returns list of auth plugins that are enabled and known to work.
 *
 * If ppl want to use some other auth type they have to include it
 * in the CSV file next on each line.
 *
 * @return array type=>name
 */
function uu_supported_auths() {
    // only following plugins are guaranteed to work properly
    $whitelist = array('manual', 'nologin', 'none', 'email');
    $plugins = get_enabled_auth_plugins();
    $choices = array();
    foreach ($plugins as $plugin) {
        if (!in_array($plugin, $whitelist)) {
            continue;
        }
        $choices[$plugin] = get_string('pluginname', "auth_{$plugin}");
    }

    return $choices;
}

/**
 * Returns list of roles that are assignable in courses
 * @return array
 */
function uu_allowed_roles() {
    // let's cheat a bit, frontpage is guaranteed to exist and has the same list of roles ;-)
    $roles = get_assignable_roles(get_context_instance(CONTEXT_COURSE, SITEID), ROLENAME_ORIGINALANDSHORT);
    return array_reverse($roles, true);
}

/**
 * Returns mapping of all roles using short role name as index.
 * @return array
 */
function uu_allowed_roles_cache() {
	$rolecache = array();
    $allowedroles = get_assignable_roles(get_context_instance(CONTEXT_COURSE, SITEID), ROLENAME_SHORT);
    foreach ($allowedroles as $rid=>$rname) {
        $rolecache[$rid] = new stdClass();
        $rolecache[$rid]->id   = $rid;
        $rolecache[$rid]->name = $rname;
        if (!is_numeric($rname)) { // only non-numeric shortnames are supported!!!
            $rolecache[$rname] = new stdClass();
            $rolecache[$rname]->id   = $rid;
            $rolecache[$rname]->name = $rname;
        }
    }
    return $rolecache;
}



/**
 * Returns idnumber
 * create new idnumber including schoolnumber and sequence-number
  * @param string $school_id
  * @param string $user_id
  * @return idnumber
 */
function uu_create_idnumber($school_id, $user_id) {
	$idnumber = '';
	
	$strSid = sprintf('%04d', trim($school_id));
	$strUid = sprintf('%08d', trim($user_id));
	
	$idnumber = $strSid . $strUid;
	
	return $idnumber;
}


/**
 * Returns username
 * check if user exists and create new username including sequence-number
  * @param string $firstname
  * @param string $lastname
  * @return username
 */
function uu_create_username($idnumber, $firstname, $lastname) {
	if ($username = uu_search_user_id($idnumber)) {
		return $username;
	} else {
		$ldap = new auth_plugin_ldapdlb();
		$username = $ldap->ldap_create_username($firstname, $lastname);
	}	
	return $username;
}


/**
 * Returns a new password
 * @return password
 */
function uu_create_password() {	
	$chars = "abcdefghijkmnopqrstuvwxyzABCDEFGHKMNPQRSTUVWXYZ23456789";
    srand((double)microtime()*1000000);
    $i = 0;
    $password = '#*' ;

    while ($i <= 5) {
        $num = rand() % 33;
        $tmp = substr($chars, $num, 1);
        $password = $password . $tmp;
        $i++;
    }

    return $password;
}

/**
 * Search existing user in ldap. Returns a user-object
 * @param string $username
 * @return user-object
 */
function uu_search_user($username) {
	$user = new stdClass();
	$ldap = new auth_plugin_ldapdlb();
	if ($userArr = $ldap->get_userinfo($username)) {
		foreach($userArr as $key=>$value) {
			$user->$key = $value;
		}
		
		return $user;
	} else {
		return false;
	}
}

/**
 * Search existing user in ldap. Returns a username
 * @param string $user_id
 * @return user-object
 */
function uu_search_user_id($user_id) {
	$ldap = new auth_plugin_ldapdlb();
	return $ldap->ldap_find_user_userid($user_id);
}
		

function uu_delete_user_ldap($username) {
	$ldap = new auth_plugin_ldapdlb();
	if ($ldap->ldap_user_delete($username)) {
		return true;
	} else {
		return false;
	}
}

function uu_write_users_csv($userlist, $school_id) {
	$delimiter = "semicolon";
    $count = count($userlist);
	
	$csvArr = array();
	foreach ($userlist as $row) {
		$rowArr = array();
		foreach ($row as $user) {
			$entry = utf8_decode($user);
			array_push($rowArr, $entry);
		}
		array_push ($csvArr, $rowArr);
	}
	
	data_export_csv($csvArr, $delimiter, $school_id, $count);
	die();
}
