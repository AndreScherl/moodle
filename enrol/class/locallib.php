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
 * Local stuff for class enrolment plugin.
 *
 * @package    enrol_class
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/enrol/locallib.php');

/**
 * Sync all class course links.
 * @param progress_trace $trace
 * @param int $courseid one course, empty mean all
 * @param int $forceupdateid (optional) SYNERGY LEARNING force sync of instance, even if sync members
 *                                      is disabled (used when creating a new instance).
 * @return int 0 means ok, 1 means error, 2 means plugin disabled
 */
function enrol_class_sync(progress_trace $trace, $courseid = null, $forceupdateid = null) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/group/lib.php");

    // Purge all roles if class sync disabled, those can be recreated later here by cron or CLI.
    if (!enrol_is_enabled('class')) {
        $trace->output('Class sync plugin is disabled, unassigning all plugin roles and stopping.');
        role_unassign_all(array('component'=>'enrol_class'));
        return 2;
    }

    // Unfortunately this may take a long time, this script can be interrupted without problems.
    core_php_time_limit::raise();
    raise_memory_limit(MEMORY_HUGE);

    $trace->output('Starting user enrolment synchronisation...');

    $allroles = get_all_roles();
    $instances = array(); //cache

    $plugin = enrol_get_plugin('class');
    $unenrolaction = $plugin->get_config('unenrolaction', ENROL_EXT_REMOVED_UNENROL);

    // SYNERGY LEARNING - get the user profile fields to use for school/class.
    $globalconfig = get_config('enrol_class');
    $schoolfield = $globalconfig->user_field_schoolid;
    $classfield = $globalconfig->user_field_classname;

    // Iterate through all not enrolled yet users.
    // SYNERGY LEARNING - match users based on their school id + class name.
    // Ignore disabled instances and those with Sync members off (unless we are first creating an instance
    // when $forceupdateid will be set)
    $params = array();
    $onecourse = '';
    if ($courseid) {
        $onecourse = 'AND e.courseid = :courseid';
        $params['courseid'] = $courseid;
    }
    $forceupdate = '';
    if ($forceupdateid) {
        $forceupdate = ' OR e.id = :forceupdateid ';
        $params['forceupdateid'] = $forceupdateid;
    }
    $sql = "SELECT u.id AS 'userid', e.id AS enrolid, ue.status
              FROM {user} u
              JOIN {enrol} e ON ((e.customchar1 = u.{$classfield} OR u.{$classfield} LIKE CONCAT('%;', e.customchar1, ';%'))
                                 AND e.customchar2 = u.{$schoolfield}
                                 AND e.enrol = 'class' AND e.status = :enabled AND (e.customint3 = 1 $forceupdate) $onecourse)
         LEFT JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = u.id)
             WHERE u.deleted = 0 AND (ue.id IS NULL OR ue.status = :suspended)";
    $params['suspended'] = ENROL_USER_SUSPENDED;
    $params['enabled'] = ENROL_INSTANCE_ENABLED;
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach($rs as $ue) {
        if (!isset($instances[$ue->enrolid])) {
            $instances[$ue->enrolid] = $DB->get_record('enrol', array('id'=>$ue->enrolid));
        }
        $instance = $instances[$ue->enrolid];
        if ($ue->status == ENROL_USER_SUSPENDED) {
            $plugin->update_user_enrol($instance, $ue->userid, ENROL_USER_ACTIVE);
            $trace->output("  unsuspending: $ue->userid ==> $instance->courseid via class $instance->customchar1");
        } else {
            $plugin->enrol_user($instance, $ue->userid);
            $trace->output("  enrolling: $ue->userid ==> $instance->courseid via class $instance->customchar1");
        }
    }
    $rs->close();


    // Unenrol as necessary.
    // SYNERGY LEARNING - match users based on their school id + class name.
    // Ignore disabled instances and those with Sync members off (unless we are first creating an instance
    // when $forceupdateid will be set)
    $sql = "SELECT ue.*, e.courseid
              FROM {user_enrolments} ue
              JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'class'
                                 AND e.status = :enabled AND (e.customint3 = 1 $forceupdate) $onecourse)
         LEFT JOIN {user} u ON ((u.{$classfield} = e.customchar1 OR u.{$classfield} LIKE CONCAT('%;', e.customchar1, ';%'))
                                AND u.{$schoolfield} = e.customchar2 AND u.id = ue.userid)
             WHERE u.id IS NULL";
    $params = array('courseid' => $courseid, 'enabled' => ENROL_INSTANCE_ENABLED);
    if ($forceupdateid) {
        $params['forceupdateid'] = $forceupdateid;
    }
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach($rs as $ue) {
        if (!isset($instances[$ue->enrolid])) {
            $instances[$ue->enrolid] = $DB->get_record('enrol', array('id'=>$ue->enrolid));
        }
        $instance = $instances[$ue->enrolid];
        if ($unenrolaction == ENROL_EXT_REMOVED_UNENROL) {
            // Remove enrolment together with group membership, grades, preferences, etc.
            $plugin->unenrol_user($instance, $ue->userid);
            $trace->output("  unenrolling: $ue->userid ==> $instance->courseid via class $instance->customchar1");

        } else { // ENROL_EXT_REMOVED_SUSPENDNOROLES
            // Just disable and ignore any changes.
            if ($ue->status != ENROL_USER_SUSPENDED) {
                $plugin->update_user_enrol($instance, $ue->userid, ENROL_USER_SUSPENDED);
                $context = context_course::instance($instance->courseid);
                role_unassign_all(array('userid'=>$ue->userid, 'contextid'=>$context->id, 'component'=>'enrol_class', 'itemid'=>$instance->id));
                $trace->output("  suspending and unsassigning all roles: $ue->userid ==> $instance->courseid");
            }
        }
    }
    $rs->close();
    unset($instances);


    // Now assign all necessary roles to enrolled users - skip suspended instances and users.
    $onecourse = $courseid ? "AND e.courseid = :courseid" : "";
    $sql = "SELECT e.roleid, ue.userid, c.id AS contextid, e.id AS itemid, e.courseid
              FROM {user_enrolments} ue
              JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'class' AND e.status = :statusenabled $onecourse)
              JOIN {role} r ON (r.id = e.roleid)
              JOIN {context} c ON (c.instanceid = e.courseid AND c.contextlevel = :coursecontext)
              JOIN {user} u ON (u.id = ue.userid AND u.deleted = 0)
         LEFT JOIN {role_assignments} ra ON (ra.contextid = c.id AND ra.userid = ue.userid AND ra.itemid = e.id AND ra.component = 'enrol_class' AND e.roleid = ra.roleid)
             WHERE ue.status = :useractive AND ra.id IS NULL";
    $params = array();
    $params['statusenabled'] = ENROL_INSTANCE_ENABLED;
    $params['useractive'] = ENROL_USER_ACTIVE;
    $params['coursecontext'] = CONTEXT_COURSE;
    $params['courseid'] = $courseid;

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach($rs as $ra) {
        role_assign($ra->roleid, $ra->userid, $ra->contextid, 'enrol_class', $ra->itemid);
        $trace->output("  assigning role: $ra->userid ==> $ra->courseid as ".$allroles[$ra->roleid]->shortname);
    }
    $rs->close();


    // Remove unwanted roles - sync role can not be changed, we only remove role when unenrolled.
    $onecourse = $courseid ? "AND e.courseid = :courseid" : "";
    $sql = "SELECT ra.roleid, ra.userid, ra.contextid, ra.itemid, e.courseid
              FROM {role_assignments} ra
              JOIN {context} c ON (c.id = ra.contextid AND c.contextlevel = :coursecontext)
              JOIN {enrol} e ON (e.id = ra.itemid AND e.enrol = 'class' $onecourse)
         LEFT JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = ra.userid AND ue.status = :useractive)
             WHERE ra.component = 'enrol_class' AND (ue.id IS NULL OR e.status <> :statusenabled)";
    $params = array();
    $params['statusenabled'] = ENROL_INSTANCE_ENABLED;
    $params['useractive'] = ENROL_USER_ACTIVE;
    $params['coursecontext'] = CONTEXT_COURSE;
    $params['courseid'] = $courseid;

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach($rs as $ra) {
        role_unassign($ra->roleid, $ra->userid, $ra->contextid, 'enrol_class', $ra->itemid);
        $trace->output("  unassigning role: $ra->userid ==> $ra->courseid as ".$allroles[$ra->roleid]->shortname);
    }
    $rs->close();


    // Finally sync groups.
    $onecourse = $courseid ? "AND e.courseid = :courseid" : "";

    // Remove invalid.
    $sql = "SELECT gm.*, e.courseid, g.name AS groupname
              FROM {groups_members} gm
              JOIN {groups} g ON (g.id = gm.groupid)
              JOIN {enrol} e ON (e.enrol = 'class' AND e.courseid = g.courseid $onecourse)
              JOIN {user_enrolments} ue ON (ue.userid = gm.userid AND ue.enrolid = e.id)
             WHERE gm.component='enrol_class' AND gm.itemid = e.id AND g.id <> e.customint2";
    $params = array();
    $params['courseid'] = $courseid;

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach($rs as $gm) {
        groups_remove_member($gm->groupid, $gm->userid);
        $trace->output("  removing user from group: $gm->userid ==> $gm->courseid - $gm->groupname");
    }
    $rs->close();

    // Add missing.
    $sql = "SELECT ue.*, g.id AS groupid, e.courseid, g.name AS groupname
              FROM {user_enrolments} ue
              JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'class' $onecourse)
              JOIN {groups} g ON (g.courseid = e.courseid AND g.id = e.customint2)
              JOIN {user} u ON (u.id = ue.userid AND u.deleted = 0)
         LEFT JOIN {groups_members} gm ON (gm.groupid = g.id AND gm.userid = ue.userid)
             WHERE gm.id IS NULL";
    $params = array();
    $params['courseid'] = $courseid;

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach($rs as $ue) {
        groups_add_member($ue->groupid, $ue->userid, 'enrol_class', $ue->enrolid);
        $trace->output("  adding user to group: $ue->userid ==> $ue->courseid - $ue->groupname");
    }
    $rs->close();


    $trace->output('...user enrolment synchronisation finished.');

    return 0;
}

/**
 * Enrols all of the users in a class through a manual plugin instance.
 *
 * In order for this to succeed the course must contain a valid manual
 * enrolment plugin instance that the user has permission to enrol users through.
 *
 * @param course_enrolment_manager $manager
 * @param string $classname
 * @param string $schoolid
 * @param int $roleid
 * @return int
 */
function enrol_class_enrol_all_users(course_enrolment_manager $manager, $classname, $schoolid, $roleid) {
    global $DB;
    $context = $manager->get_context();
    require_capability('moodle/course:enrolconfig', $context);

    $instance = false;
    $instances = $manager->get_enrolment_instances();
    foreach ($instances as $i) {
        if ($i->enrol == 'manual') {
            $instance = $i;
            break;
        }
    }
    $plugin = enrol_get_plugin('manual');
    // SYNERGY LEARNING - tweak capability check from enrol_cohort.
    if (!$instance || !$plugin || !$plugin->allow_enrol($instance) || !has_capability('enrol/class:enrol', $context)) {
        return false;
    }
    // SYNERGY LEARNING - look up all users with matching classname + schoolid.
    $config = get_config('enrol_class');
    $classfield = $config->user_field_classname;
    $schoolfield = $config->user_field_schoolid;
    $sql = "SELECT u.id
              FROM {user} u
         LEFT JOIN (
                SELECT *
                  FROM {user_enrolments} ue
                 WHERE ue.enrolid = :enrolid
                 ) ue ON ue.userid = u.id
             WHERE (u.{$classfield} = :classname OR u.{$classfield} LIKE :classnamelike)
                  AND u.{$schoolfield} = :schoolid AND ue.id IS NULL";
    $params = array('classname' => $classname, 'classnamelike' => "%;{$classname};%",
                    'schoolid' => $schoolid, 'enrolid' => $instance->id);
    $rs = $DB->get_recordset_sql($sql, $params);
    $count = 0;
    foreach ($rs as $user) {
        $count++;
        $plugin->enrol_user($instance, $user->id, $roleid);
    }
    $rs->close();
    return $count;
}

/**
 * SYNERGY LEARNING - get a list of roles we are allowed to allocate during enrolment.
 *
 * @return array (roleid => display name)
 */
function enrol_class_get_available_roles() {
    $roles = get_roles_with_capability('enrol/class:assignable', CAP_ALLOW, context_system::instance());
    return role_fix_names($roles, null, ROLENAME_BOTH, true);
}

/**
 * Gets all the classes the user is able to view.
 * SYNERGY LEARNING - very different form the original cohort enrolment.
 *
 * @param object $user
 * @return array
 */
function enrol_class_get_classes($user) {
    if (empty($user->mebisKlassenListe)) {
        return array();
    }
    return $user->mebisKlassenListe;
}

/**
 * Check if class exists and user is allowed to enrol it.
 *
 * @global moodle_database $DB
 * @param string $classname Class ID
 * @return boolean
 */
function enrol_class_can_view_class($classname) {
    global $USER;
    $classes = enrol_class_get_classes($USER);
    return in_array($classname, $classes);
}

/**
 * Gets classes the user is able to view.
 * SYNERGY LEARNING - very different from original cohort enrolment.
 *
 * @global moodle_database $DB
 * @param course_enrolment_manager $manager
 * @param int $offset limit output from
 * @param int $limit items to output per load
 * @param string $search search string
 * @return array    Array(more => bool, offset => int, classes => array)
 */
function enrol_class_search_classes(course_enrolment_manager $manager, $offset = 0, $limit = 25, $search = '') {
    global $DB, $USER;

    $schoolfield = get_config('enrol_class', 'user_field_schoolid');
    $schoolid = $USER->{$schoolfield};
    $allclasses = enrol_class_get_classes($USER);
    $instances = $manager->get_enrolment_instances();
    $enrolled = array();
    $enrolids = array();
    foreach ($instances as $instance) {
        if ($instance->enrol == 'class' && $instance->customchar2 == $schoolid) { // Only include enrol from the same school.
            $enrolled[] = $instance->customchar1;
            $enrolids[] = $instance->id;
        }
    }

    // SYNERGY LEARNING - count how many enrolments there are for each class.
    $usercount = 0;
    if ($enrolids) {
        list($esql, $params) = $DB->get_in_or_equal($enrolids, SQL_PARAMS_NAMED);
        $sql = "SELECT e.customchar1, COUNT(ue.id)
              FROM {enrol} e
              JOIN {user_enrolments} ue ON ue.enrolid = e.id
             WHERE e.id {$esql}
             GROUP BY e.customchar1";
        $usercount = $DB->get_records_sql_menu($sql, $params);
    }

    // SYNERGY LEARNING - filter out any classes that do not match the search string.
    if ($search) {
        $classes = array();
        foreach ($allclasses as $class) {
            if (strpos($class, $search) !== false) {
                $classes[] = $class;
            }
        }
    } else {
        $classes = $allclasses;
    }

    // Only return the number of results requested.
    $totalcount = count($classes);
    $classes = array_slice($classes, $offset, $limit);

    // Format the results as required by the javascript.
    $ret = array();
    foreach ($classes as $class) {
        $ret[$class] = array(
            'classname' => $class,
            'name' => $class,
            'users' => isset($usercount[$class]) ? $usercount[$class] : 0,
            'enrolled' => in_array($class, $enrolled),
        );
    }

    return array('more' => (($offset + $limit) < $totalcount), 'offset' => $offset + $limit, 'classes' => $ret);
}

class enrol_class_handler {
    public static function check_class_field(core\event\base $event) {
        global $DB;
        if ($classfield = get_config('enrol_class', 'user_field_classname')) {
            $classes = $DB->get_field('user', $classfield, array('id' => $event->relateduserid));
            if (strpos($classes, ';') !== false) {
                // A semicolon indicates there are multiple classes for this user
                // - make sure the field starts + ends with a semicolon as well.
                $updclasses = ';'.trim($classes, ';').';';
                if ($updclasses != $classes) {
                    $DB->set_field('user', $classfield, $updclasses, array('id' => $event->relateduserid));
                }
            }
        }
    }
}