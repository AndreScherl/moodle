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
 * Event handler for class enrolment plugin.
 *
 * We try to keep everything in sync via listening to events,
 * it may fail sometimes, so we always do a full sync in cron too.
 */
class enrol_class_handler {
    /**
     * Event processor - class member added.
     * @param stdClass $ca
     * @return bool
     */
    public static function member_added($ca) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/group/lib.php");

        if (!enrol_is_enabled('class')) {
            return true;
        }

        // Does any enabled class instance want to sync with this class?
        $sql = "SELECT e.*, r.id as roleexists
                  FROM {enrol} e
             LEFT JOIN {role} r ON (r.id = e.roleid)
                 WHERE e.customint1 = :classid AND e.enrol = 'class'
              ORDER BY e.id ASC";
        if (!$instances = $DB->get_records_sql($sql, array('classid'=>$ca->classid))) {
            return true;
        }

        $plugin = enrol_get_plugin('class');
        foreach ($instances as $instance) {
            if ($instance->status != ENROL_INSTANCE_ENABLED ) {
                // No roles for disabled instances.
                $instance->roleid = 0;
            } else if ($instance->roleid and !$instance->roleexists) {
                // Invalid role - let's just enrol, they will have to create new sync and delete this one.
                $instance->roleid = 0;
            }
            unset($instance->roleexists);
            // No problem if already enrolled.
            $plugin->enrol_user($instance, $ca->userid, $instance->roleid, 0, 0, ENROL_USER_ACTIVE);

            // Sync groups.
            if ($instance->customint2) {
                if (!groups_is_member($instance->customint2, $ca->userid)) {
                    if ($group = $DB->get_record('groups', array('id'=>$instance->customint2, 'courseid'=>$instance->courseid))) {
                        groups_add_member($group->id, $ca->userid, 'enrol_class', $instance->id);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Event processor - class member removed.
     * @param stdClass $ca
     * @return bool
     */
    public static function member_removed($ca) {
        global $DB;

        // Does anything want to sync with this class?
        if (!$instances = $DB->get_records('enrol', array('customint1'=>$ca->classid, 'enrol'=>'class'), 'id ASC')) {
            return true;
        }

        $plugin = enrol_get_plugin('class');
        $unenrolaction = $plugin->get_config('unenrolaction', ENROL_EXT_REMOVED_UNENROL);

        foreach ($instances as $instance) {
            if (!$ue = $DB->get_record('user_enrolments', array('enrolid'=>$instance->id, 'userid'=>$ca->userid))) {
                continue;
            }
            if ($unenrolaction == ENROL_EXT_REMOVED_UNENROL) {
                $plugin->unenrol_user($instance, $ca->userid);

            } else {
                if ($ue->status != ENROL_USER_SUSPENDED) {
                    $plugin->update_user_enrol($instance, $ue->userid, ENROL_USER_SUSPENDED);
                    $context = context_course::instance($instance->courseid);
                    role_unassign_all(array('userid'=>$ue->userid, 'contextid'=>$context->id, 'component'=>'enrol_class', 'itemid'=>$instance->id));
                }
            }
        }

        return true;
    }

    /**
     * Event processor - class deleted.
     * @param stdClass $class
     * @return bool
     */
    public static function deleted($class) {
        global $DB;

        // Does anything want to sync with this class?
        if (!$instances = $DB->get_records('enrol', array('customint1'=>$class->id, 'enrol'=>'class'), 'id ASC')) {
            return true;
        }

        $plugin = enrol_get_plugin('class');
        $unenrolaction = $plugin->get_config('unenrolaction', ENROL_EXT_REMOVED_UNENROL);

        foreach ($instances as $instance) {
            if ($unenrolaction == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
                $context = context_course::instance($instance->courseid);
                role_unassign_all(array('contextid'=>$context->id, 'component'=>'enrol_class', 'itemid'=>$instance->id));
                $plugin->update_status($instance, ENROL_INSTANCE_DISABLED);
            } else {
                $plugin->delete_instance($instance);
            }
        }

        return true;
    }
}


/**
 * Sync all class course links.
 * @param int $courseid one course, empty mean all
 * @param bool $verbose verbose CLI output
 * @return int 0 means ok, 1 means error, 2 means plugin disabled
 */
function enrol_class_sync($courseid = NULL, $verbose = false) {
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


    // Iterate through all not enrolled yet users.
    $onecourse = $courseid ? "AND e.courseid = :courseid" : "";
    $sql = "SELECT cm.userid, e.id AS enrolid, ue.status
              FROM {class_members} cm
              JOIN {enrol} e ON (e.customint1 = cm.classid AND e.enrol = 'class' $onecourse)
              JOIN {user} u ON (u.id = cm.userid AND u.deleted = 0)
         LEFT JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = cm.userid)
             WHERE ue.id IS NULL OR ue.status = :suspended";
    $params = array();
    $params['courseid'] = $courseid;
    $params['suspended'] = ENROL_USER_SUSPENDED;
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach($rs as $ue) {
        if (!isset($instances[$ue->enrolid])) {
            $instances[$ue->enrolid] = $DB->get_record('enrol', array('id'=>$ue->enrolid));
        }
        $instance = $instances[$ue->enrolid];
        if ($ue->status == ENROL_USER_SUSPENDED) {
            $plugin->update_user_enrol($instance, $ue->userid, ENROL_USER_ACTIVE);
            if ($verbose) {
                mtrace("  unsuspending: $ue->userid ==> $instance->courseid via class $instance->customint1");
            }
        } else {
            $plugin->enrol_user($instance, $ue->userid);
            if ($verbose) {
                mtrace("  enrolling: $ue->userid ==> $instance->courseid via class $instance->customint1");
            }
        }
    }
    $rs->close();


    // Unenrol as necessary.
    $sql = "SELECT ue.*, e.courseid
              FROM {user_enrolments} ue
              JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'class' $onecourse)
         LEFT JOIN {class_members} cm ON (cm.classid = e.customint1 AND cm.userid = ue.userid)
             WHERE cm.id IS NULL";
    $rs = $DB->get_recordset_sql($sql, array('courseid'=>$courseid));
    foreach($rs as $ue) {
        if (!isset($instances[$ue->enrolid])) {
            $instances[$ue->enrolid] = $DB->get_record('enrol', array('id'=>$ue->enrolid));
        }
        $instance = $instances[$ue->enrolid];
        if ($unenrolaction == ENROL_EXT_REMOVED_UNENROL) {
            // Remove enrolment together with group membership, grades, preferences, etc.
            $plugin->unenrol_user($instance, $ue->userid);
            if ($verbose) {
                mtrace("  unenrolling: $ue->userid ==> $instance->courseid via class $instance->customint1");
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
 * @global moodle_database $DB
 * @param course_enrolment_manager $manager
 * @param int $classid
 * @param int $roleid
 * @return int
 */
function enrol_class_enrol_all_users(course_enrolment_manager $manager, $classid, $roleid) {
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
    if (!$instance || !$plugin || !$plugin->allow_enrol($instance) || !has_capability('enrol/'.$plugin->get_name().':enrol', $context)) {
        return false;
    }
    $sql = "SELECT com.userid
              FROM {class_members} com
         LEFT JOIN (
                SELECT *
                  FROM {user_enrolments} ue
                 WHERE ue.enrolid = :enrolid
                 ) ue ON ue.userid=com.userid
             WHERE com.classid = :classid AND ue.id IS NULL";
    $params = array('classid' => $classid, 'enrolid' => $instance->id);
    $rs = $DB->get_recordset_sql($sql, $params);
    $count = 0;
    foreach ($rs as $user) {
        $count++;
        $plugin->enrol_user($instance, $user->userid, $roleid);
    }
    $rs->close();
    return $count;
}

/**
 * Gets all the classes the user is able to view.
 *
 * @global moodle_database $DB
 * @param course_enrolment_manager $manager
 * @return array
 */
function enrol_class_get_classes(course_enrolment_manager $manager) {
    global $DB;
    $context = $manager->get_context();
    $classes = array();
    $instances = $manager->get_enrolment_instances();
    $enrolled = array();
    foreach ($instances as $instance) {
        if ($instance->enrol == 'class') {
            $enrolled[] = $instance->customint1;
        }
    }
    list($sqlparents, $params) = $DB->get_in_or_equal(get_parent_contexts($context));
    $sql = "SELECT id, name, idnumber, contextid
              FROM {class}
             WHERE contextid $sqlparents
          ORDER BY name ASC, idnumber ASC";
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $c) {
        $context = context::instance_by_id($c->contextid);
        if (!has_capability('moodle/class:view', $context)) {
            continue;
        }
        $classes[$c->id] = array(
            'classid'=>$c->id,
            'name'=>format_string($c->name, true, array('context'=>context::instance_by_id($c->contextid))),
            'users'=>$DB->count_records('class_members', array('classid'=>$c->id)),
            'enrolled'=>in_array($c->id, $enrolled)
        );
    }
    $rs->close();
    return $classes;
}

/**
 * Check if class exists and user is allowed to enrol it.
 *
 * @global moodle_database $DB
 * @param int $classid Class ID
 * @return boolean
 */
function enrol_class_can_view_class($classid) {
    global $DB;
    $class = $DB->get_record('class', array('id' => $classid), 'id, contextid');
    if ($class) {
        $context = context::instance_by_id($class->contextid);
        if (has_capability('moodle/class:view', $context)) {
            return true;
        }
    }
    return false;
}

/**
 * Gets classes the user is able to view.
 *
 * @global moodle_database $DB
 * @param course_enrolment_manager $manager
 * @param int $offset limit output from
 * @param int $limit items to output per load
 * @param string $search search string
 * @return array    Array(more => bool, offset => int, classes => array)
 */
function enrol_class_search_classes(course_enrolment_manager $manager, $offset = 0, $limit = 25, $search = '') {
    global $DB;
    $context = $manager->get_context();
    $classes = array();
    $instances = $manager->get_enrolment_instances();
    $enrolled = array();
    foreach ($instances as $instance) {
        if ($instance->enrol == 'class') {
            $enrolled[] = $instance->customint1;
        }
    }

    list($sqlparents, $params) = $DB->get_in_or_equal($context->get_parent_context_ids());

    // Add some additional sensible conditions.
    $tests = array('contextid ' . $sqlparents);

    // Modify the query to perform the search if required.
    if (!empty($search)) {
        $conditions = array(
            'name',
            'idnumber',
            'description'
        );
        $searchparam = '%' . $DB->sql_like_escape($search) . '%';
        foreach ($conditions as $key=>$condition) {
            $conditions[$key] = $DB->sql_like($condition, "?", false);
            $params[] = $searchparam;
        }
        $tests[] = '(' . implode(' OR ', $conditions) . ')';
    }
    $wherecondition = implode(' AND ', $tests);

    $sql = "SELECT id, name, idnumber, contextid, description
              FROM {class}
             WHERE $wherecondition
          ORDER BY name ASC, idnumber ASC";
    $rs = $DB->get_recordset_sql($sql, $params, $offset);

    // Produce the output respecting parameters.
    foreach ($rs as $c) {
        // Track offset.
        $offset++;
        // Check capabilities.
        $context = context::instance_by_id($c->contextid);
        if (!has_capability('moodle/class:view', $context)) {
            continue;
        }
        if ($limit === 0) {
            // We have reached the required number of items and know that there are more, exit now.
            $offset--;
            break;
        }
        $classes[$c->id] = array(
            'classid' => $c->id,
            'name'     => shorten_text(format_string($c->name, true, array('context'=>context::instance_by_id($c->contextid))), 35),
            'users'    => $DB->count_records('class_members', array('classid'=>$c->id)),
            'enrolled' => in_array($c->id, $enrolled)
        );
        // Count items.
        $limit--;
    }
    $rs->close();
    return array('more' => !(bool)$limit, 'offset' => $offset, 'classes' => $classes);
}
