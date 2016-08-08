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
 * local mbs external functions
 *
 * @package    local_mbs
 * @copyright  2016 Franziska Hübler, ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/user/externallib.php');

/**
 * local mbs external functions
 *
 * @package     local_mbs
 * @copyright   2016 Franziska Hübler, ISB Bayern
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       moodle 3.1
 */
class local_mbs_external extends external_api {
    
    /**
     * Create a user
     * 
     * @throws invalid_parameter_exception
     * @param array $user The list of fields for the user to create.
     * @return int The user id.
     */
    public static function local_mbs_create_user($user) {
        global $CFG, $DB;
        // Ensure the current user is allowed to run this function.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/user:create', $context);
        
        // Do basic automatic PARAM checks on incoming data, using params description.
        // If any problems are found then exceptions are thrown with helpful error messages.
        $params = self::validate_parameters(self::local_mbs_create_user_parameters(), array('user'=>$user));
        
        $availableauths  = core_component::get_plugin_list('auth');
        unset($availableauths['mnet']);       // These would need mnethostid too.
        unset($availableauths['webservice']); // We do not want new webservice users for now.
        
        // Make sure that the username doesn't already exist.
        if ($DB->record_exists('user', array('username' => $user['username'], 'mnethostid' => $CFG->mnet_localhost_id))) {
            throw new invalid_parameter_exception('Username already exists: '.$user['username']);
        }
        // Make sure auth is valid.
        if (empty($availableauths[$user['auth']])) {
            throw new invalid_parameter_exception('Invalid authentication type: '.$user['auth']);
        }  
        
        $user['password'] = '';
        $user['confirmed'] = true;
        $user['mnethostid'] = $CFG->mnet_localhost_id;
        
        // Validate email.
        if (!empty($user['email'])) {
            if (!validate_email($user['email'])) {
                throw new invalid_parameter_exception('Email address is invalid: '.$user['email']);
            } else if (empty($CFG->allowaccountssameemail) && 
                    $DB->record_exists('user', array('email' => $user['email'], 'mnethostid' => $user['mnethostid']))) {
                throw new invalid_parameter_exception('Email address already exists: '.$user['email']);
            }
        }
        // Make sure that the school exists.
        if (!$DB->record_exists('course_categories', array('idnumber' => $user['institution']))) {
            throw new invalid_parameter_exception('Schoolnumber doesn\'t exist: '.$user['institution']);
        }
        
        // Just in case check text case.
        $user['username'] = trim(core_text::strtolower($user['username']));
    
        // Create the user data now!
        $userid = user_create_user($user, false, true);
        
        $newuser = $DB->get_record('user', array('id' => $userid));
        $userdetails = user_get_user_details_courses($newuser);
        
        return array('user' => $userdetails);     
    }
    
    /**
     * Returns description of local_mbs_create_user parameters
     * @return external_function_parameters
     */
    public static function local_mbs_create_user_parameters() {
        return new external_function_parameters(
            array(
                'user' => new external_single_structure(
                    array(
                        'username' =>
                            new external_value(core_user::get_property_type('username'), 'Username policy is defined in Moodle security config.'),
                        'firstname' =>
                            new external_value(core_user::get_property_type('firstname'), 'The first name(s) of the user'),
                        'lastname' =>
                            new external_value(core_user::get_property_type('lastname'), 'The family name of the user'),
                        'email' =>
                            new external_value(core_user::get_property_type('email'), 'A valid and unique email address', VALUE_OPTIONAL),
                        'auth' =>
                            new external_value(core_user::get_property_type('auth'), 'Auth plugins include manual, ldap, shibboleth, etc',
                                 VALUE_DEFAULT, 'manual', core_user::get_property_null('auth')),
                        'institution' =>
                            new external_value(core_user::get_property_type('institution'), 'The schoolid of the user'),
                        'department' =>
                            new external_value(core_user::get_property_type('department'), 'Class(es) of the user', VALUE_OPTIONAL)
                    )
                )
            )
        );
    }
    
    /**
     * Returns description of local_mbs_create_user result value
     * @return external_description
     */
    public static function local_mbs_create_user_returns() {
        return new external_function_parameters(
            array('user' => \core_user_external::user_description())
        );
    }
    
    /**
     * Update a user with a user object (will compare against the ID)
     *
     * @throws invalid_parameter_exception
     * @param stdClass $user The user to update.
     * @return int The user id.
     */
    public static function local_mbs_update_user($user) {
        global $CFG, $DB;
        // Ensure the current user is allowed to run this function.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/user:update', $context);
        
        // Do basic automatic PARAM checks on incoming data, using params description.
        // If any problems are found then exceptions are thrown with helpful error messages.
        $params = self::validate_parameters(self::local_mbs_update_user_parameters(), array('update'=>$user));
        
        // Make sure that the user already exists.
        if (!$DB->record_exists('user', array('id' => $user['id'], 'mnethostid' => $CFG->mnet_localhost_id))) {
            throw new invalid_parameter_exception('User with id '.$user['id'].' not found.');
        }
        // Make sure auth is valid.
        if (isset($user['auth'])) {
            $availableauths  = core_component::get_plugin_list('auth');
            unset($availableauths['mnet']);       // These would need mnethostid too.
            unset($availableauths['webservice']); // We do not want new webservice users for now.

            if (empty($availableauths[$user['auth']])) {
                throw new invalid_parameter_exception('Invalid authentication type: '.$user['auth']);
            }  
        }
        // Validate email if set and if not empty.
        if (isset($user['email']) && !empty($user['email'])) {
            if (!validate_email($user['email'])) {
                throw new invalid_parameter_exception('Email address is invalid: '.$user['email']);
            } else if (empty($CFG->allowaccountssameemail) && 
                    $DB->record_exists('user', array('email' => $user['email'], 'mnethostid' => $user['mnethostid']))) {
                throw new invalid_parameter_exception('Email address already exists: '.$user['email']);
            }
        }
        // Make sure that the school exists.
        if (isset($user['institution'])) {
            if (!$DB->record_exists('course_categories', array('idnumber' => $user['institution']))) {
                throw new invalid_parameter_exception('Schoolnumber doesn\'t exist: '.$user['institution']);
            }
        }        
        // Just in case check text case.
        if (isset($user['username'])) {
            $user['username'] = trim(core_text::strtolower($user['username']));
        }
        
        // Update user.
        $DB->update_record('user', $user);
        
        $userupdated = $DB->get_record('user', array('id' => $user['id']));
        $userdetails = user_get_user_details_courses($userupdated);        
        return array('user' => $userdetails);   
    }
    
    /**
     * Returns description of local_mbs_update_user parameters
     * @return external_function_parameters
     */
    public static function local_mbs_update_user_parameters() {
        return new external_function_parameters(
            array(
                'update' => new external_single_structure(
                    array(
                        'id' => new external_value(core_user::get_property_type('id'), 'ID of the user'),
                        'username' =>
                            new external_value(core_user::get_property_type('username'), 'Username policy is defined in Moodle security config.', 
                                VALUE_OPTIONAL),
                        'firstname' =>
                            new external_value(core_user::get_property_type('firstname'), 'The first name(s) of the user', VALUE_OPTIONAL),
                        'lastname' =>
                            new external_value(core_user::get_property_type('lastname'), 'The family name of the user', VALUE_OPTIONAL),
                        'email' =>
                            new external_value(core_user::get_property_type('email'), 'A valid and unique email address', VALUE_OPTIONAL),
                        'auth' =>
                            new external_value(core_user::get_property_type('auth'), 'Auth plugins include manual, ldap, shibboleth, etc', 
                                VALUE_OPTIONAL),
                        'institution' =>
                            new external_value(core_user::get_property_type('institution'), 'The schoolid of the user', VALUE_OPTIONAL),
                        'department' =>
                            new external_value(core_user::get_property_type('department'), 'Class(es) of the user', VALUE_OPTIONAL),
                        'suspended' => new external_value(core_user::get_property_type('suspended'), 
                                'This attribute will be used to enable/suspend the locally created user account, 0 -> enable, 1 -> suspend', VALUE_OPTIONAL)
                    )
                )
            )
        );    
    }
    
    /**
     * Returns description of local_mbs_update_user result value
     * @return external_description
     */
    public static function local_mbs_update_user_returns() {
        return new external_function_parameters(
            array('user' => \core_user_external::user_description())
        );
    }
    
    /**
     * Delete user (will compare against the ID)
     *
     * @throws invalid_parameter_exception, moodle_exception
     * @param int $userid The id of the user to delete.
     * @return bool Success.
     */
    public static function local_mbs_delete_user($userid) {
        global $CFG, $DB, $USER;
        // Ensure the current user is allowed to run this function.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/user:delete', $context);
        
        // Do basic automatic PARAM checks on incoming data, using params description.
        // If any problems are found then exceptions are thrown with helpful error messages.
        $params = self::validate_parameters(self::local_mbs_delete_user_parameters(), array('userid'=>$userid));
        
        // Make sure that the user already exists.
        if (!$DB->record_exists('user', array('id' => $userid, 'mnethostid' => $CFG->mnet_localhost_id))) {
            throw new invalid_parameter_exception('User with id '.$userid.' not found.');
        }
        
        $user = $DB->get_record('user', array('id' => $userid, 'deleted' => 0), '*', MUST_EXIST);
        // Must not allow deleting of admins or self!!!
        if (is_siteadmin($user)) {
            throw new moodle_exception('useradminodelete', 'error');
        }
        if ($USER->id == $user->id) {
            throw new moodle_exception('usernotdeletederror', 'error');
        }
        
        $success = user_delete_user($user);
        return array('deleted' => $success);  
    }
    
    /**
     * Returns description of local_mbs_delete_user parameters
     * @return external_function_parameters
     */
    public static function local_mbs_delete_user_parameters() {
        return new external_function_parameters(
            array('userid' => new external_value(core_user::get_property_type('id'), 'user id'))
        );
    }
    
    /**
     * Returns description of local_mbs_delete_user result value
     * @return external_description
     */
    public static function local_mbs_delete_user_returns() {
        return new external_function_parameters(
            array('deleted' => new external_value(PARAM_BOOL, 'true if success'))
        );
    }
}

