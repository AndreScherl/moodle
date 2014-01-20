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
 * @param int $courseid one course, empty mean all
 * @param bool $verbose verbose CLI output
 * @param int $forceupdateid (optional) SYNERGY LEARNING force sync of instance, even if sync members
 *                                      is disabled (used when creating a new instance).
 * @return int 0 means ok, 1 means error, 2 means plugin disabled
 */
function enrol_class_sync($courseid = NULL, $verbose = false, $forceupdateid = null) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/group/lib.php");

    // Purge all roles if class sync disabled, those can be recreated later here by cron or CLI.
    if (!enrol_is_enabled('class')) {
        if ($verbose) {
            mtrace('Class sync plugin is disabled, unassigning all plugin roles and stopping.');
        }
        role_unassign_all(array('component'=>'enrol_class'));
        return 2;
    }

    // Unfortunately this may take a long time, this script can be interrupted without problems.
    @set_time_limit(0);
    raise_memory_limit(MEMORY_HUGE);

    if ($verbose) {
        mtrace('Starting user enrolment synchronisation...');
    }

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
    $onecourse = $courseid ? "AND e.courseid = :courseid" : "";
    $forceupdate = '';
    if ($forceupdateid) {
        $forceupdate = ' OR e.id = :forceupdateid ';
        $params['forceupdateid'] = $forceupdateid;
    }
    $sql = "SELECT u.id AS 'userid', e.id AS enrolid, ue.status
              FROM {user} u
              JOIN {enrol} e ON (e.customchar1 = u.{$classfield} AND e.customchar2 = u.{$schoolfield}
                                 AND e.enrol = 'class' AND e.status = :enabled AND (e.customint3 = 1 $forceupdate) $onecourse)
         LEFT JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = u.id)
             WHERE u.deleted = 0 AND (ue.id IS NULL OR ue.status = :suspended)";
    $params['courseid'] = $courseid;
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
            if ($verbose) {
                mtrace("  unsuspending: $ue->userid ==> $instance->courseid via class $instance->customchar1");
            }
        } else {
            $plugin->enrol_user($instance, $ue->userid);
            if ($verbose) {
                mtrace("  enrolling: $ue->userid ==> $instance->courseid via class $instance->customchar1");
            }
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
         LEFT JOIN {user} u ON (u.{$classfield} = e.customchar1 AND u.{$schoolfield} = e.customchar2 AND u.id = ue.userid)
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
            if ($verbose) {
                mtrace("  unenrolling: $ue->userid ==> $instance->courseid via class $instance->customchar1");
            }

        } else { // ENROL_EXT_REMOVED_SUSPENDNOROLES
            // Just disable and ignore any changes.
            if ($ue->status != ENROL_USER_SUSPENDED) {
                $plugin->update_user_enrol($instance, $ue->userid, ENROL_USER_SUSPENDED);
                $context = context_course::instance($instance->courseid);
                role_unassign_all(array('userid'=>$ue->userid, 'contextid'=>$context->id, 'component'=>'enrol_class', 'itemid'=>$instance->id));
                if ($verbose) {
                    mtrace("  suspending and unsassigning all roles: $ue->userid ==> $instance->courseid");
                }
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
        if ($verbose) {
            mtrace("  assigning role: $ra->userid ==> $ra->courseid as ".$allroles[$ra->roleid]->shortname);
        }
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
        if ($verbose) {
            mtrace("  unassigning role: $ra->userid ==> $ra->courseid as ".$allroles[$ra->roleid]->shortname);
        }
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
        if ($verbose) {
            mtrace("  removing user from group: $gm->userid ==> $gm->courseid - $gm->groupname");
        }
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
        if ($verbose) {
            mtrace("  adding user to group: $ue->userid ==> $ue->courseid - $ue->groupname");
        }
    }
    $rs->close();


    if ($verbose) {
        mtrace('...user enrolment synchronisation finished.');
    }

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
             WHERE u.{$classfield} = :classname AND u.{$schoolfield} = :schoolid AND ue.id IS NULL";
    $params = array('classname' => $classname, 'schoolid' => $schoolid, 'enrolid' => $instance->id);
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
 * @param object $user optional
 * @return array
 */
function enrol_class_get_classes($user = null) {
    global $DB;

    // TODO davo - get this list via LDAP, instead of searching 'user' table.
    $config = get_config('enrol_class');

    $where = '';
    $params = array();
    if ($user !== null) {
        $where = ' WHERE u.'.$config->user_field_schoolid.' = ? ';
        $params[] = $user->{$config->user_field_schoolid};
    }

    $sql = "SELECT DISTINCT u.{$config->user_field_classname} AS classname, COUNT(u.id) AS usercount
              FROM {user} u
              {$where}
             GROUP BY u.{$config->user_field_classname}
             ORDER BY u.{$config->user_field_classname}";
    $rs = $DB->get_recordset_sql($sql, $params);
    $classes = array();
    foreach ($rs as $c) {
        $classes[$c->classname] = array(
            'classname' => $c->classname,
            'name' => $c->classname,
            'users' => $c->usercount,
        );
    }
    $rs->close();

    return $classes;
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
    return array_key_exists($classname, $classes);
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
    $classes = array();
    $instances = $manager->get_enrolment_instances();
    $enrolled = array();
    foreach ($instances as $instance) {
        if ($instance->enrol == 'class') {
            $enrolled[] = $instance->customchar1;
        }
    }

    // TODO davo - get the list via LDAP, instead of searching the 'user' table.
    $config = get_config('enrol_class');
    $schoolfield = $config->user_field_schoolid;
    $classfield = $config->user_field_classname;
    $schoolid = $USER->{$schoolfield};

    $wheres = array();
    $params = array();
    if ($schoolid !== null) {
        $wheres[] = "u.{$schoolfield} = :schoolid";
        $params['schoolid'] = $schoolid;
    }
    if ($search) {
        $wheres[] = $DB->sql_like("u.{$classfield}", ':search', false, false);
        $params['search'] = '%'.$search.'%';
    }

    $where = '';
    if ($wheres) {
        $where = ' WHERE '.implode(' AND ', $wheres);
    }

    $sql = "SELECT DISTINCT u.{$config->user_field_classname} AS classname, COUNT(u.id) AS usercount
              FROM {user} u
              {$where}
             GROUP BY u.{$config->user_field_classname}
             ORDER BY u.{$config->user_field_classname}";
    $rs = $DB->get_recordset_sql($sql, $params, $offset, $limit + 1); // One extra, to see if there are more results.

    foreach ($rs as $c) {
        $classes[$c->classname] = array(
            'classname' => $c->classname,
            'name' => $c->classname,
            'users' => $c->usercount,
            'enrolled' => in_array($c->classname, $enrolled),
            'schoolid' => $schoolid,
        );
    }
    $rs->close();

    // Check to see if there are more results to find (and remove the extra result).
    $more = false;
    if (count($classes) > $limit) {
        $more = true;
        array_pop($classes);
    }

    return array('more' => $more, 'offset' => $offset + $limit, 'classes' => $classes);
}
