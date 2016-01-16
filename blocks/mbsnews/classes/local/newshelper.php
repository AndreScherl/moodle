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
 * @package   block_mbsnews
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbsnews\local;

class newshelper {

    public static $contextlevelnames = array(
        CONTEXT_SYSTEM => 'contextsystem',
        CONTEXT_COURSECAT => 'contextcategory',
        CONTEXT_COURSE => 'contextcourse');

    private static function add_instanceinfo(&$jobs) {
        global $DB;

        // Collect all instances group by context level.
        $instanceids = array(CONTEXT_COURSE => array(), CONTEXT_COURSECAT => array());
        foreach ($jobs as $job) {
            if (!empty($job->instanceids)) {
                $instanceids[$job->contextlevel] = array_merge($instanceids[$job->contextlevel], explode(',', $job->instanceids));
            }
        }

        // Fetch information as array.
        if (!empty($instanceids[CONTEXT_COURSE])) {

            $courses = $DB->get_records_list('course', 'id', $instanceids[CONTEXT_COURSE]);

            $instanceids[CONTEXT_COURSE] = array();
            foreach ($courses as $course) {
                $instanceids[CONTEXT_COURSE][$course->id] = $course->fullname;
            }
        }

        if (!empty($instanceids[CONTEXT_COURSECAT])) {

            $categories = $DB->get_records_list('course_categories', 'id', $instanceids[CONTEXT_COURSE]);

            $instanceids[CONTEXT_COURSECAT] = array();
            foreach ($categories as $category) {
                $instanceids[CONTEXT_COURSECAT][$category->id] = $category->name;
            }
        }

        foreach ($jobs as $job) {
            if (!empty($job->instanceids)) {
                $instances = array_flip(explode(',', $job->instanceids));
                $job->instanceinfo = array_intersect_key($instanceids[$job->contextlevel], $instances);
            }
        }
    }

    /**
     * Get all existing notification jobs.
     * 
     * @param type $params
     * @param type $table
     * @param type $perpage
     */
    public static function get_jobs($pageparams, $table, $perpage) {
        global $DB, $USER;

        $select = " SELECT * ";
        $from = " FROM {block_mbsnews_job} ";
        $params = array();

        // Page size of table.
        $total = $DB->count_records_sql("SELECT count(*) " . $from, $params);

        $table->pagesize($perpage, $total);
        $limitfrom = $table->get_page_start();
        $sort = $table->get_sql_sort();

        $orderby = " ORDER BY " . $sort;
        $sql = $select . $from . $orderby;

        if (!$jobs = $DB->get_records_sql($sql, $params, $limitfrom, $perpage)) {
            return array();
        }

        // Add the instance information to the jobs. 
        self::add_instanceinfo($jobs);
        return $jobs;
    }

    public static function load_job_instance($id) {
        global $DB;

        $news = $DB->get_record('block_mbsnews_job', array('id' => $id), '*', MUST_EXIST);

        if (empty($news->instanceids)) {
            $news->instanceids = array();
            return $news;
        }

        if ($news->contextlevel < CONTEXT_COURSECAT) {
            $news->instanceids = array();
            return $news;
        }

        $instancesids = explode(',', $news->instanceids);

        $data = array();
        if ($news->contextlevel == CONTEXT_COURSECAT) {

            $data = $DB->get_records_list('course_categories', 'id', $instancesids);
            $name = 'name';
        }

        if ($news->contextlevel == CONTEXT_COURSE) {

            $data = $DB->get_records_list('course', 'id', $instancesids);
            $name = 'fullname';
        }

        $result = array();
        foreach ($data as $date) {
            $result[$date->id] = $date->$name;
        }

        $news->instanceids = $result;
        return $news;
    }

    /**
     * Save a notification job after editjob.php submit
     * 
     * @param type $submitteddata
     * @return array result array for saving the job.
     */
    public static function save_notification_job($data) {
        global $DB, $USER;

        print_r($data);

        $job = new \stdClass();
        $job->roleid = (empty($data->roleid)) ? 0 : $data->roleid;
        $job->contextlevel = $data->contextlevel;

        if (!empty($data->instanceids)) {
            $job->instanceids = implode(',', array_keys($data->instanceids));
        } else {
            $job->instanceids = '';
        }

        $job->sender = $USER->id;
        $job->subject = $data->subject;
        $job->fullmessage = $data->fullmessage['text'];

        if ($data->id == 0) {

            $job->countrecipients = $data->countrecipients;
            $job->countprocessed = 0;
            $job->timestarted = 0;
            $job->timefinished = 0;
            $job->timecreated = time();
            $job->timemodified = $job->timecreated;

            $DB->insert_record('block_mbsnews_job', $job);
        } else {

            if (!$exists = $DB->get_record('block_mbsnews_job', array('id' => $data->id))) {
                return array('error' => 1, 'message' => get_string('errornewsjobsaved', 'block_mbsnews'));
            }

            $job->id = $data->id;
            $job->countrecipients = $data->countrecipients;
            $job->countprocessed = $exists->countprocessed;
            $job->timestarted = $exists->timestarted;
            $job->timefinished = $exists->timefinished;
            $job->timemodified = time();
            $DB->update_record('block_mbsnews_job', $job);
        }

        return array('error' => 0, 'message' => get_string('newsjobsaved', 'block_mbsnews'));
    }

    /**
     * Get SQL Objekt for retrieving recipients from the database.
     * 
     * @param array $searchparams
     * @return \stdClass
     */
    private static function get_recipients_sql($searchparams) {

        $config = get_config('block_mbsnews');

        $sql = new \stdClass();
        $sql->selectcount = " SELECT  count(DISTINCT u.id) ";
        $sql->select = " SELECT DISTINCT u.* ";
        $sql->join = " FROM {user} u ";
        $sql->params = array();

        // Exclude deleted users.
        $cond = array(" u.deleted = 0");

        // Include auth users.
        if (!empty($config->includeauth)) {

            $auths = explode(',', $config->includeauth);

            $authcond = array();
            foreach ($auths as $auth) {
                $authcond[] = " u.auth = '{$auth}' ";
            }
            $cond[] = implode(" OR ", $authcond);
        }

        // Check roleid.
        if (!empty($searchparams['contextlevel'])) {

            $sql->join .= " JOIN {role_assignments} ra ON ra.userid = u.id ";
            $sql->join .= " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :contextlevel ";
            $sql->params['contextlevel'] = $searchparams['contextlevel'];

            if ($searchparams['contextlevel'] == CONTEXT_COURSECAT) {
                $cond[] = ' ctx.depth = :contextdepth ';

                // Note that context depth is cat depth + 1!
                $sql->params['contextdepth'] = \local_mbs\local\schoolcategory::$schoolcatdepth + 1;
            }
        }

        // Check roleid.
        if (!empty($searchparams['roleid'])) {
            $cond[] = " ra.roleid = :roleid ";
            $sql->params['roleid'] = $searchparams['roleid'];
        }

        // Check instanceids.
        if (!empty($searchparams['instanceidsselected'])) {

            $instancesids = explode('_', $searchparams['instanceidsselected']);

            $instancecond = array();

            foreach ($instancesids as $instanceid) {
                $instancecond[] = " ctx.instanceid = '{$instanceid}' ";
            }

            $cond[] = implode(' OR ', $instancecond);
        }

        $sql->where = "WHERE (" . implode(') AND (', $cond) . ")";
        return $sql;
    }

    /**
     * Search for recipients in two steps (assuming there are many results)
     * 1. Count results
     * 2. If there are lower than 10 retrieve the details of the users.
     * 
     * @param array $searchparams
     * @return array result array contains error flag and result as a string. 
     */
    public static function search_recipients($searchparams) {
        global $DB;

        $sql = self::get_recipients_sql($searchparams);

        // Count records.
        if (!$count = $DB->count_records_sql($sql->selectcount . $sql->join . $sql->where, $sql->params)) {
            return array('error' => 0, 'results' => array('list' => get_string('recipientsselected', 'block_mbsnews', $count), 'count' => $count));
        }

        if ($count > 5) {
            return array('error' => 0, 'results' => array('list' => get_string('recipientsselected', 'block_mbsnews', $count), 'count' => $count));
        }

        // Get records.
        $users = $DB->get_records_sql($sql->select . $sql->join . $sql->where, $sql->params);

        $usernames = array();
        foreach ($users as $user) {
            $url = new \moodle_url('/user/profile.php', array('id' => $user->id));
            $usernames[] = \html_writer::link($url, fullname($user), array('target' => '_blank'));
        }

        $list = implode(", ", $usernames);

        return array('error' => 0, 'results' => array('list' => $list, 'count' => count($usernames)));
    }

    /**
     * Processes a job, which means:
     * 
     * 1. get a job from the table block_mbsnews and retrieve the recipients, which haven't got a message yet.
     * 2. Create messages for the recipients.
     * 3. When there a no more recipients left, mark the job as processed.
     * 
     * @param type $job
     * @param type $maxmessagescount  max count of messages to proceed.
     */
    private static function process_job($job, $recipientslimit) {
        global $DB;

        $sql = self::get_recipients_sql((array) $job);

        $sql->select .= ", m.id as mid, mr.id as mreadid ";

        // Get all the users, which are not yet notificated.
        $sql->join .= " LEFT JOIN {message} m ON (m.useridto = u.id) AND (m.contexturlname = :jobid1) ";
        $sql->params['jobid1'] = 'Mebis News: '.$job->id;

        // Get all the users, which are not yet notificated.
        $sql->join .= " LEFT JOIN {message_read} mr ON (mr.useridto = u.id) AND (mr.contexturlname = :jobid2) ";
        $sql->params['jobid2'] = 'Mebis News: '.$job->id;

        $sql->where .= " AND ((m.id IS NULL) AND (mr.id IS NULL))";

        $query = $sql->select . $sql->join . $sql->where;

        if (!$recipients = $DB->get_records_sql($query, $sql->params, 0, $recipientslimit)) {
            // all done.
            $job->timefinished = time();
            $DB->update_record('block_mbsnews_job', $job);
            return 0;
        }

        // Create messages.
        $userfrom = \core_user::get_user($job->sender);
        
        $eventdata = new \stdClass();
        $eventdata->component = 'block_mbsnews';
        $eventdata->name = 'mbsnewsnotification';
        $eventdata->userfrom = $userfrom;
        $eventdata->notification = 1;
        $eventdata->subject = $job->subject;
        $eventdata->fullmessage = $job->fullmessage;
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml = $job->fullmessage;
        $eventdata->smallmessage = '';
        $eventdata->contexturlname = 'Mebis News: '.$job->id;

        $count = 0;
        foreach ($recipients as $userto) {
            
            $eventdata->userto = \core_user::get_user($userto->id);
            if (message_send($eventdata)) {
                $count++;
            }
        }
        
        mtrace("{$count} messages sent.");
        
        if ($job->timestarted == 0) {
            $job->timestarted = time();
        }
        
        $job->countprocessed = $job->countprocessed + $count;
        $DB->update_record('block_mbsnews_job', $job);
        return $count;
    }

    public static function process_notification_jobs() {
        global $DB;

        $config = get_config('block_mbsnews');

        // Get next jobs, that are not fully processed.
        if (!$jobs = $DB->get_records('block_mbsnews_job', array('timefinished' => 0), 'timemodified ASC')) {
            mtrace('nothing to do...');
            return true;
        }

        $countprocessed = 0;
        $messagesleft = $config->maxmessages;
        foreach ($jobs as $job) {

            $countprocessed += self::process_job($job, $messagesleft);
            $messagesleft = max(0, $messagesleft - $countprocessed);

            if ($messagesleft == 0) {
                mtrace('... max messages reached, have a break...');
                return true;
            }
        }

        mtrace('...all left messages sent.');
        return true;
    }

}
