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
 * Bulk user registration script from a comma separated file
 *
 * @package    tool
 * @subpackage dlbuploaduser
 * @copyright  2004 onwards Martin Dougiamas (http://dougiamas.com)
 * @modifier   2012 Ulrich Weber
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once('locallib.php');
require_once('user_form.php');
require_once($CFG->dirroot.'/auth/ldapdlb/auth.php');

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

/********* ULI ************/
$moodle_insert_users = 1;
/**************************/

@set_time_limit(60*60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

require_login();
admin_externalpage_setup('tooldlbuploaduser');
require_capability('moodle/site:dlbuploadusers', get_context_instance(CONTEXT_SYSTEM));

$struserrenamed             = get_string('userrenamed', 'tool_uploaduser');
$strusernotrenamedexists    = get_string('usernotrenamedexists', 'error');
$strusernotrenamedmissing   = get_string('usernotrenamedmissing', 'error');
$strusernotrenamedoff       = get_string('usernotrenamedoff', 'error');
$strusernotrenamedadmin     = get_string('usernotrenamedadmin', 'error');

$struserupdated             = get_string('useraccountupdated', 'tool_uploaduser');
$strusernotupdated          = get_string('usernotupdatederror', 'error');
$strusernotupdatednotexists = get_string('usernotupdatednotexists', 'error');
$strusernotupdatedadmin     = get_string('usernotupdatedadmin', 'error');

$struseruptodate            = get_string('useraccountuptodate', 'tool_uploaduser');

$struseradded               = get_string('newuser');
$strusernotadded            = get_string('usernotaddedregistered', 'error');
$strusernotaddederror       = get_string('usernotaddederror', 'error');

$struserdeleted             = get_string('userdeleted', 'tool_uploaduser');
$strusernotdeletederror     = get_string('usernotdeletederror', 'error');
$strusernotdeletedmissing   = get_string('usernotdeletedmissing', 'error');
$strusernotdeletedoff       = get_string('usernotdeletedoff', 'error');
$strusernotdeletedadmin     = get_string('usernotdeletedadmin', 'error');

$strcannotassignrole        = get_string('cannotassignrole', 'error');
$strdepartmentunknown		= get_string('departmentunknown', 'tool_dlbuploaduser');
$stractionunknown			= get_string('actionunknown', 'tool_dlbuploaduser');

$struserauthunsupported     = get_string('userauthunsupported', 'error');
$stremailduplicate          = get_string('useremailduplicate', 'error');

$errorstr                   = get_string('error');

$stryes                     = get_string('yes');
$strno                      = get_string('no');
$stryesnooptions = array(0=>$strno, 1=>$stryes);

$returnurl = new moodle_url('/admin/tool/dlbuploaduser/index.php');
$bulknurl  = new moodle_url('/admin/user/user_bulk.php');

// ULI
$csvurl = new moodle_url('/admin/tool/dlbuploaduser/exportcsv.php');
$pdfurl = new moodle_url('/admin/tool/dlbuploaduser/exportpdf.php');

$today = time();
$today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);

// array of all valid fields for validation
$STD_FIELDS = array('uid', 'firstname', 'lastname', 'email', 'department', 'action');

$PRF_FIELDS = array();

if ($prof_fields = $DB->get_records('user_info_field')) {
    foreach ($prof_fields as $prof_field) {
        $PRF_FIELDS[] = 'profile_field_'.$prof_field->shortname;
    }
}
unset($prof_fields);

if (empty($iid)) {
    $mform1 = new admin_uploaduser_form1();

    if ($formdata = $mform1->get_data()) {
        $iid = csv_import_reader::get_new_iid('uploaduser');
        $cir = new csv_import_reader($iid, 'uploaduser');

        $content = $mform1->get_file_content('userfile');

        $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
        unset($content);

        if ($readcount === false) {
            print_error('csvloaderror', '', $returnurl);
        } else if ($readcount == 0) {
            print_error('csvemptyfile', 'error', $returnurl);
        }
        // test if columns ok
        $filecolumns = uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);
        // continue to form2

    } else {
        echo $OUTPUT->header();

        echo $OUTPUT->heading_with_help(get_string('uploadusers', 'tool_uploaduser'), 'uploadusers', 'tool_uploaduser');

        $mform1->display();
		
		// Uli: show download-urls
		if (array_key_exists("users_inserted", $SESSION)) {
			if (count($SESSION->users_inserted) > 1) {
				echo $OUTPUT->heading(get_string('download_imported_users', 'tool_dlbuploaduser'));
				echo $OUTPUT->box_start();
				echo $OUTPUT->action_link($csvurl, get_string('csvexport', 'tool_dlbuploaduser'), new popup_action('click', $csvurl,  'popup', array('height' => '800','width' => '600','menubar' => false,'location' => false,'resizable' => true,'toolbar' => false)));
				echo "<p>";
				echo $OUTPUT->action_link($pdfurl, get_string('pdfexport', 'tool_dlbuploaduser'),new popup_action('click', $pdfurl,  'popup', array('height' => '800','width' => '600','menubar' => false,'location' => false,'resizable' => true,'toolbar' => false)));
				echo $OUTPUT->box_end();
			}
		}
		
        echo $OUTPUT->footer();
        die;
    }
} else {
    $cir = new csv_import_reader($iid, 'uploaduser');
    $filecolumns = uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);
}

$mform2 = new admin_uploaduser_form2(null, array('columns'=>$filecolumns, 'data'=>array('iid'=>$iid, 'previewrows'=>$previewrows)));

// If a file has been uploaded, then process it
if ($formdata = $mform2->is_cancelled()) {
    $cir->cleanup(true);
    redirect($returnurl);

} else if ($formdata = $mform2->get_data()) {
	// ULI:  array to enter all inserted users for csv-export
	$SESSION->users_inserted = array();
	$SESSION->institution = 0;
	$csvtitle = array("id", "Benutzername", "Kennwort", "Vorname", "Nachname", "Rolle", "E-Mail");
	array_push($SESSION->users_inserted, $csvtitle);
	
	// ULI: declare all available departments and actions
	$departmentArr = array("Lehrer", "Schüler");
	$actionArr = array("insert", "delete");

    // Print the header
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('uploadusersresult', 'tool_uploaduser'));

    $optype = $formdata->uutype;

    $updatetype        = isset($formdata->uuupdatetype) ? $formdata->uuupdatetype : 0;
	/*********** ULI **************/
	$createpasswords   = 1;
    //$createpasswords   = (!empty($formdata->uupasswordnew) and $optype != UU_USER_UPDATE);
	/******************************/
  
    $allowrenames      = (!empty($formdata->uuallowrenames) and $optype != UU_USER_ADDNEW and $optype != UU_USER_ADDINC);
    $allowdeletes      = (!empty($formdata->uuallowdeletes) and $optype != UU_USER_ADDNEW and $optype != UU_USER_ADDINC);

    // verification moved to two places: after upload and into form2
    $usersnew      = 0;
    $usersupdated  = 0;
    $usersuptodate = 0; //not printed yet anywhere
    $userserrors   = 0;
    $deletes       = 0;
    $deleteerrors  = 0;
    $renames       = 0;
    $renameerrors  = 0;
    $usersskipped  = 0;
    $weakpasswords = 0;

    // caches
    $ccache         = array(); // course cache - do not fetch all courses here, we  will not probably use them all anyway!
    $cohorts        = array();
    $rolecache      = uu_allowed_roles_cache(); // roles lookup cache
    $manualcache    = array(); // cache of used manual enrol plugins in each course
    $supportedauths = uu_supported_auths(); // officially supported plugins that are enabled

    // we use only ldapdlb enrol plugin here, if it is disabled no enrol is done
    if (enrol_is_enabled('ldapdlb')) {
        $manual = enrol_get_plugin('ldapdlb');
    } else {
        $manual = NULL;
    }
		
	// request institution
	if (!has_capability('moodle/site:dlbuploadusers_selectinstitute', get_context_instance(CONTEXT_SYSTEM))) {
		if (!$USER->institution) {
			print_error('institutionerror', 'dlbuploadusers', $returnurl);
		} else {
			$institution = $USER->institution;
		}
	} else {
		$institution = $formdata->institution;
	}
		
    // init csv import helper
    $cir->init();
    $linenum = 1; //column header is first line

    // init upload progress tracker
    $upt = new uu_progress_tracker();
    $upt->start(); // start table
	
    while ($line = $cir->next()) {
        $upt->flush();
        $linenum++;

        $upt->track('line', $linenum);

        $user = new stdClass();

        // add fields to user object
        foreach ($line as $keynum => $value) {
            if (!isset($filecolumns[$keynum])) {
                // this should not happen
                continue;
            }
            $key = $filecolumns[$keynum];
            if (strpos($key, 'profile_field_') === 0) {
                //NOTE: bloody mega hack alert!!
                if (isset($USER->$key) and is_array($USER->$key)) {
                    // this must be some hacky field that is abusing arrays to store content and format
                    $user->$key = array();
                    $user->$key['text']   = $value;
                    $user->$key['format'] = FORMAT_MOODLE;
                } else {
                    $user->$key = $value;
                }
            } else {
                $user->$key = $value;
            }

            if (in_array($key, $upt->columns)) {
                // default value in progress tracking table, can be changed later
                $upt->track($key, s($value), 'normal');
            }
        }
        if (!isset($user->username)) {
	        // prevent warnings below
            $user->username = '';
        }
	
		if ($optype == UU_USER_ADDNEW) {
            // user creation is a special case - the username may be constructed from templates using firstname and lastname
            // better never try this in mixed update types
            $error = false;
            if (!isset($user->firstname) or $user->firstname === '') {
                $upt->track('status', get_string('missingfield', 'error', 'firstname'), 'error');
                $upt->track('firstname', $errorstr, 'error');
                $error = true;
            }
            if (!isset($user->lastname) or $user->lastname === '') {
                $upt->track('status', get_string('missingfield', 'error', 'lastname'), 'error');
                $upt->track('lastname', $errorstr, 'error');
                $error = true;
            }
            if ($error) {
                $userserrors++;
                continue;
            }
        }
		
		
		/***** ULI: translate and check department *****/
		$user->department = ucfirst($user->department);
		$user->department = str_replace("Schueler", "Schüler", $user->department);
		if (!in_array($user->department, $departmentArr)) {
			$upt->track('status', $strdepartmentunknown, 'error');
			$userserrors++;
			continue;
		}
		/*******************************/
		
		/***** ULI: translate and check action *****/
		$user->action = strtolower($user->action);
		if (!in_array($user->action, $actionArr)) {
			$upt->track('status', $stractionunknown, 'error');
			$userserrors++;
			continue;
		}
		/*******************************/
	
		/****** CREATE USERID *******/
		// School-ID (4) + Index (8)
		$user->idnumber = uu_create_idnumber($institution, $user->uid);
		$user->username = uu_create_username($user->idnumber, $user->firstname, $user->lastname);
		/******************************/
		
        // make sure we really have username
        if (empty($user->username)) {
            $upt->track('status', get_string('missingfield', 'error', 'username'), 'error');
            $upt->track('username', $errorstr, 'error');
            $userserrors++;
            continue;
        } 
		
		/************ CHECK USER **************/
		if ($existinguser_ldap = uu_search_user($user->username)) {
			$upt->track('username', $existinguser_ldap->username, 'normal', false);
			$existinguser_ldap->auth = 'shibboleth';
		}
		
		$existinguser_moodle = $DB->get_record('user', array('username'=>$user->username, 'mnethostid'=>$CFG->mnet_localhost_id));
		/**************************************/
	
        // add default values for remaining fields
        $formdefaults = array();
        foreach ($STD_FIELDS as $field) {
            if (isset($user->$field)) {
                continue;
            }
            // all validation moved to form2
            if (isset($formdata->$field)) {
                // process templates
                $user->$field = uu_process_template($formdata->$field, $user);
                $formdefaults[$field] = true;
                if (in_array($field, $upt->columns)) {
                    $upt->track($field, s($user->$field), 'normal');
                }
            }
        }
        foreach ($PRF_FIELDS as $field) {
            if (isset($user->$field)) {
                continue;
            }
            if (isset($formdata->$field)) {
                // process templates
                $user->$field = uu_process_template($formdata->$field, $user);
                $formdefaults[$field] = true;
            }
        }

        // delete user
	if ($user->action == "delete") {
            if (!$allowdeletes) {
                $usersskipped++;
                $upt->track('status', $strusernotdeletedoff, 'warning');
                continue;
            }
	
            if ($existinguser_moodle) {
                if (is_siteadmin($existinguser_moodle->id)) {
                    $upt->track('status', $strusernotdeletedadmin, 'error');
                    $deleteerrors++;
                    continue;
                }
                if (delete_user($existinguser_moodle)) {
                    $upt->track('status', $struserdeleted . " (Moodle)");
                    //$deletes++;
                } else {
                    $upt->track('status', $strusernotdeletederror, 'error');
                    $deleteerrors++;
                }
            } 	
			
	    if ($existinguser_ldap) {
		if (uu_delete_user_ldap($existinguser_ldap->username)) {
                    $upt->track('status', $struserdeleted . " (LDAP)");
                    $deletes++;
                } else {
                    $upt->track('status', $strusernotdeletederror, 'error');
                    $deleteerrors++;
                }				
	    } else {
                $upt->track('status', $strusernotdeletedmissing, 'error');
                $deleteerrors++;
            }
            continue;
        }

        // can we process with update or insert?
        $skip = false;
        switch ($optype) {
            case UU_USER_ADDNEW:
                if ($existinguser_ldap) {
                    $usersskipped++;
                    $upt->track('status', $strusernotadded, 'warning');
                    $skip = true;
                }
                break;

            case UU_USER_ADD_UPDATE:
                break;

            case UU_USER_UPDATE:
                if (!$existinguser_ldap) {
                    $usersskipped++;
                    $upt->track('status', $strusernotupdatednotexists, 'warning');
                    $skip = true;
                }
                break;

            default:
                // unknown type
                $skip = true;
        }

        if ($skip) {
            continue;
        }
		
        if ($existinguser_ldap) {
			$user->username = $existinguser_ldap->username;
			$newuser_ldap = clone $existinguser_ldap;

            $doupdate = false;
            $dologout = false;

            if ($updatetype != UU_UPDATE_NOCHANGES) {
                $allcolumns = array_merge($STD_FIELDS, $PRF_FIELDS);
                foreach ($allcolumns as $column) {
				    if ($column === 'uid' or $column === 'department' or $column === 'action') {
                        // these can not be changed here
                        continue;
                    }
                    if (!property_exists($user, $column) or !property_exists($existinguser_ldap, $column)) {
                        // this should never happen
	                    continue;
                    }
						
					/**** ULI: Values MUST NOT BE EMPTY IN FILE ****/ 
					if (is_null($user->$column) or $user->$column == '') {
                        continue;
                    }
					/***********************************************/
						
                    if ($existinguser_ldap->$column !== $user->$column) {						
                        if ($column === 'email') {
                            if ($DB->record_exists('user', array('email'=>$user->email))) {
                                $upt->track('email', $stremailduplicate, 'warning');
                            }
                            if (!validate_email($user->email)) {
                                $upt->track('email', get_string('invalidemail'), 'warning');
                            }
                        }

                        if (in_array($column, $upt->columns)) {
                            $upt->track($column, s($existinguser_ldap->$column).'-->'.s($user->$column), 'info', false);
                        }
						
                        $newuser_ldap->$column = $user->$column;
                        $doupdate = true;
                    }
                }
            }

            try {
                $auth = get_auth_plugin($existinguser_ldap->auth);
            } catch (Exception $e) {
                $upt->track('auth', get_string('userautherror', 'error', s($existinguser_ldap->auth)), 'error');
                $upt->track('status', $strusernotupdated, 'error');
                $userserrors++;
                continue;
            }
            $isinternalauth = $auth->is_internal();

            if ($doupdate) {
                // we want only users that were really updated
				
				/*** ULI: UPDATE USER IN LDAP ***/
				$ldapdlb = new auth_plugin_ldapdlb();
				$ldapdlb->user_update($existinguser_ldap, $newuser_ldap);
				/*********************************/

                $upt->track('status', $struserupdated);
                $usersupdated++;

                events_trigger('user_updated', $existinguser_ldap);

            } else {
                // no user information changed
                $upt->track('status', $struseruptodate);
                $usersuptodate++;
            }

            if ($dologout and $existintuser_moodle) {
                session_kill_user($existinguser_moodle->id);
            }

        } else {
            // save the new user to the database			
            $user->confirmed    = 1;
            $user->timemodified = time();
            $user->timecreated  = time();
            $user->mnethostid   = $CFG->mnet_localhost_id; // we support ONLY local accounts here, sorry
			//$user->institution = $institution;
			$user->lang			= 'de';
			$user->country 		= 'DE';

			$user->auth = 'shibboleth';
				
			if (!empty($user->email)) {
           		if (!validate_email($user->email)) {
                	$upt->track('email', get_string('invalidemail'), 'warning');
				}
            }
			
			/***** ULI: CREATE PASSWORD *****/
			$forcechangepassword = true;
			$user->password = uu_create_password();
			$moodle_password = hash_internal_user_password($user->password);
			/*******************************/
			
	        // create user - insert_record ignores any extra properties
			
			/****  ULI: CREATE USER IN LDAP ****/
			$ldapdlb = new auth_plugin_ldapdlb();
			$newuser = $ldapdlb->user_create($user, $user->password, $institution, $user->department, 0);
			
			if ($newuser) {
				$upt->track('status', $struseradded);
				$upt->track('username', $user->username, 'normal', false);
				$upt->track('password', $user->password);
				
				$csvuser = array($user->uid, $user->username, $user->password, 
								 $user->firstname, $user->lastname, 
								 $user->department, $user->email);			
				array_push($SESSION->users_inserted, $csvuser);
			} else {
				$upt->track('status', $strusernotadded);
			}
			/***********************************/
			
					
			/******ULI: CREATE USER IN MOODLE ****/
			if ($moodle_insert_users and $newuser) {
				$user->password = "not cached";
				unset($user->action);
				unset($user->uid);
				unset($user->department);
				
            	$user->id = $DB->insert_record('user', $user);

            	// save custom profile fields data
            	profile_save_data($user);

            	if ($forcechangepassword) {
            	    set_user_preference('auth_forcepasswordchange', 1, $user);
            	}
			}
			/***********************************/

            $usersnew++;

            events_trigger('user_created', $user);
        }
    }
	
    $upt->close(); // close table

    $cir->close();
    $cir->cleanup(true);
	
    echo $OUTPUT->box_start('boxwidthnarrow boxaligncenter generalbox', 'uploadresults');
    echo '<p>';
    if ($optype != UU_USER_UPDATE) {
        echo get_string('userscreated', 'tool_uploaduser').': '.$usersnew.'<br />';
    }
    if ($optype == UU_USER_UPDATE or $optype == UU_USER_ADD_UPDATE) {
        echo get_string('usersupdated', 'tool_uploaduser').': '.$usersupdated.'<br />';
    }
    if ($allowdeletes) {
        echo get_string('usersdeleted', 'tool_uploaduser').': '.$deletes.'<br />';
        echo get_string('deleteerrors', 'tool_uploaduser').': '.$deleteerrors.'<br />';
    }
    if ($allowrenames) {
        echo get_string('usersrenamed', 'tool_uploaduser').': '.$renames.'<br />';
        echo get_string('renameerrors', 'tool_uploaduser').': '.$renameerrors.'<br />';
    }
    if ($usersskipped) {
        echo get_string('usersskipped', 'tool_uploaduser').': '.$usersskipped.'<br />';
    }
    echo get_string('usersweakpassword', 'tool_uploaduser').': '.$weakpasswords.'<br />';
    echo get_string('errors', 'tool_uploaduser').': '.$userserrors.'</p>';
    echo $OUTPUT->box_end();
	
	// Uli store institution
	$SESSION->institution = $institution;
	
	if (count($SESSION->users_inserted) > 1) {
		echo $OUTPUT->heading(get_string('download_imported_users', 'tool_dlbuploaduser'));
		echo $OUTPUT->box_start();
		echo $OUTPUT->action_link($csvurl, get_string('csvexport', 'tool_dlbuploaduser'), new popup_action('click', $csvurl,  'popup', array('height' => '800','width' => '600','menubar' => false,'location' => false,'resizable' => true,'toolbar' => false)));
		echo "<p>";
		echo $OUTPUT->action_link($pdfurl, get_string('pdfexport', 'tool_dlbuploaduser'),new popup_action('click', $pdfurl,  'popup', array('height' => '800','width' => '600','menubar' => false,'location' => false,'resizable' => true,'toolbar' => false)));
		echo $OUTPUT->box_end();
		echo "<p>";
	}

	echo $OUTPUT->continue_button($returnurl);
    echo $OUTPUT->footer();
    die;
}

// Print the header
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('uploaduserspreview', 'tool_uploaduser'));

// NOTE: this is JUST csv processing preview, we must not prevent import from here if there is something in the file!!
//       this was intended for validation of csv formatting and encoding, not filtering the data!!!!
//       we definitely must not process the whole file!

// preview table data
$data = array();
$cir->init();
$linenum = 1; //column header is first line
while ($linenum <= $previewrows and $fields = $cir->next()) {
    $linenum++;
    $rowcols = array();
    $rowcols['line'] = $linenum;
    foreach($fields as $key => $field) {
        $rowcols[$filecolumns[$key]] = s($field);
    }
    $rowcols['status'] = array();

	/*
    if (isset($rowcols['username'])) {
        $stdusername = clean_param($rowcols['username'], PARAM_USERNAME);
        if ($rowcols['username'] !== $stdusername) {
            $rowcols['status'][] = get_string('invalidusernameupload');
        }
        if ($userid = $DB->get_field('user', 'id', array('username'=>$stdusername, 'mnethostid'=>$CFG->mnet_localhost_id))) {
            $rowcols['username'] = html_writer::link(new moodle_url('/user/profile.php', array('id'=>$userid)), $rowcols['username']);
        }
    } else {
        $rowcols['status'][] = get_string('missingusername');
    }
	*/

    if (isset($rowcols['email'])) {
        if (!validate_email($rowcols['email'])) {
            $rowcols['status'][] = get_string('invalidemail');
        }
        if ($DB->record_exists('user', array('email'=>$rowcols['email']))) {
            $rowcols['status'][] = $stremailduplicate;
        }
    }

    if (isset($rowcols['city'])) {
        $rowcols['city'] = trim($rowcols['city']);
        if (empty($rowcols['city'])) {
            $rowcols['status'][] = get_string('fieldrequired', 'error', 'city');
        }
    }

    $rowcols['status'] = implode('<br />', $rowcols['status']);
    $data[] = $rowcols;
}
if ($fields = $cir->next()) {
    $data[] = array_fill(0, count($fields) + 2, '...');
}
$cir->close();

$table = new html_table();
$table->id = "uupreview";
$table->attributes['class'] = 'generaltable';
$table->tablealign = 'center';
$table->summary = get_string('uploaduserspreview', 'tool_uploaduser');
$table->head = array();
$table->data = $data;

$table->head[] = get_string('uucsvline', 'tool_uploaduser');
foreach ($filecolumns as $column) {
    $table->head[] = $column;
}
$table->head[] = get_string('status');

echo html_writer::tag('div', html_writer::table($table), array('class'=>'flexible-wrap'));

/// Print the form

$mform2->display();
echo $OUTPUT->footer();
die;

