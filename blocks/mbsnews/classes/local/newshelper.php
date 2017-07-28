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

    /**
     * Mark the message as read.
     * 
     * @param object $message
     * @return array with results.
     */
    public static function mark_message_read($message) {
        global $USER, $DB;

        $oldmessageid = $message->id;

        if ($message->usertoid != $USER->id) {
            return array('error' => get_string('errormarkasreadonlyown', 'block_mbsnews'));
        }

        $message->timeconfirmed = time();

        // Try to mark as read.
        try {
            $DB->update_record('block_mbsnews_message', $message);

            $cache = \cache::make('block_mbsnews', 'mebisnews');
            $cache->delete($message->usertoid);

            return array('error' => 0, 'results' => array('id' => $oldmessageid));

        } catch (Exception $e) {

            return array('error' => get_string('errorcannotsetread', 'block_mbsnews'));
        }
    }

    /**
     * Get news the should be displayed for this user.
     * 
     * @param type $user
     */
    public static function get_news($user) {
        global $DB;

        $cache = \cache::make('block_mbsnews', 'mebisnews');

        if ($result = $cache->get($user->id)) {
            return $result;
        }

        $sql = "SELECT m.id, m.timefirstviewed, j.sender, j.subject, j.fullmessage, j.timecreated 
                FROM {block_mbsnews_job} j
                JOIN {block_mbsnews_message} m ON m.jobid = j.id
                WHERE m.usertoid = :userid AND m.timeconfirmed = :timeconfirmed
                ORDER BY j.timecreated DESC";

        $params = array('userid' => $user->id, 'timeconfirmed' => 0);

        if (!$messages = $DB->get_records_sql($sql, $params)) {
            return false;
        }

        $result = new \stdClass();
        $result->messages = $messages;

        // Getting authors of messages.
        $authorids = array();

        foreach ($messages as $message) {
            $authorids[$message->sender] = $message->sender;

            // If  the message is displayed the first time set the date.
            if (empty($message->timefirstviewed)) {
                $now = time();
                $DB->set_field('block_mbsnews_message', 'timefirstviewed', $now, array('id' => $message->id));
            }
        }

        $result->authors = $DB->get_records_list('user', 'id', $authorids);

        $cache->set($user->id, $result);

        return $result;
    }

    /**
     * Add an array with the name of selected instances (i. e. the name of category
     * or course) to the job object.
     *
     * @param object $jobs
     */
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
     * Get all existing notification jobs fro displaying in a flexible table
     * 
     * @param array $pageparams used as SQL params
     * @param flexible_table $table
     * @param int $perpage 
     * 
     * @return array list of job objects
     */
    public static function get_jobs($pageparams, $table, $perpage) {
        global $DB;

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

    /**
     * Load a job object from database and set the attribute instanceids properly.
     * 
     * @param int $id
     * @return object
     */
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
     * @param object $data the submitted data form the editjob form.
     * @return array result saving the job.
     */
    public static function save_notification_job($data) {
        global $DB, $USER;

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
        $job->duration = $data->duration;

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
     * Delete a job and clear the recipients log.
     * 
     * @param obejct $job
     */
    public static function delete_job($job) {
        global $DB;

        $success = $DB->delete_records('block_mbsnews_job', array('id' => $job->id));

        if ($success) {
            // Delete recipients log.
            $success = $DB->delete_records('block_mbsnews_message', array('jobid' => $job->id));
        }
        return $success;
    }

    /**
     * Get the parts of a sql-query for retrieving recipients from the database.
     * Used by cron job and when the count of recipients is calculated.
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
        if (!empty($searchparams['instanceids'])) {

            $cond[] = " ctx.instanceid IN ({$searchparams['instanceids']}) ";
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

        $config = get_config('block_mbsnews');

        $sql = self::get_recipients_sql($searchparams);

        // Count records.
        if (!$count = $DB->count_records_sql($sql->selectcount . $sql->join . $sql->where, $sql->params)) {
            return array('error' => 0, 'results' => array('list' => get_string('recipientsselected', 'block_mbsnews', $count), 'count' => $count));
        }

        if ($count > $config->recipientdisplaylimit) {
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

    private static function get_user($userid) {
        static $userbuffer = array();

        if (!isset($userbuffer[$userid])) {
            $userbuffer[$userid] = \core_user::get_user($userid);
        }
        return $userbuffer[$userid];
    }

    /**
     * Processes a job, which means:
     * 
     * 1. get a job from the table block_mbsnews and retrieve the recipients, which haven't got a message yet.
     * 2. Create messages for the recipients.
     * 3. When there a no more recipients left, mark the job as processed.
     * 
     * @param type $job
     * @param type $maxmessagescount max count of messages to proceed.
     */
    private static function process_job($job, $maxmessagescount) {
        global $DB;

        $sql = self::get_recipients_sql((array) $job);

        $sql->select .= ", m.usertoid ";

        // Get all the users, which are not yet notificated.
        $sql->join .= " LEFT JOIN {block_mbsnews_message} m ON (u.id = m.usertoid) AND (m.jobid = :jobid) ";
        $sql->params['jobid'] = $job->id;

        $sql->where .= " AND (m.usertoid IS NULL) ";

        $query = $sql->select . $sql->join . $sql->where;

        if (!$recipients = $DB->get_records_sql($query, $sql->params, 0, $maxmessagescount)) {
            // All done.
            $job->timefinished = time();
            $DB->update_record('block_mbsnews_job', $job);

            return 0;
        }

        $count = 0;
        foreach ($recipients as $userto) {

            // Log this user as notified.
            $log = new \stdClass();
            $log->jobid = $job->id;
            $log->usertoid = $userto->id;
            $log->timecreated = time();
            $log->timefirstviewed = 0;
            $log->timeconfirmed = 0;

            $DB->insert_record('block_mbsnews_message', $log);
            $count++;

            // Delete users cache.
            $cache = \cache::make('block_mbsnews', 'mebisnews');
            $cache->delete($userto->id);
        }

        mtrace("{$count} messages sent.");

        if ($job->timestarted == 0) {
            $job->timestarted = time();
        }

        $job->countprocessed = $job->countprocessed + $count;
        $DB->update_record('block_mbsnews_job', $job);
        return $count;
    }

    /**
     * Processes all the jobs, i. e. create messages for appropriate recipients
     * in the messages table.
     * 
     * This function is called by a scheduled task.
     * 
     * @return boolean
     */
    public static function process_notification_jobs() {
        global $DB;

        $config = get_config('block_mbsnews');

        // Get next jobs, that are not fully processed.
        if (!$jobs = $DB->get_records('block_mbsnews_job', array('timefinished' => 0), 'timemodified ASC')) {
            mtrace('no message to process...');
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

    /**
     * Delete all the messages, that are belonging to a expired notification job, when
     * duration has a value greater than 0 (number of days to display the message).
     * 
     * @return boolean
     */
    public static function delete_expired_messages() {
        global $DB;

        $sql = "SELECT j.id, count(*) as countmessages
                FROM {block_mbsnews_job} j
                JOIN {block_mbsnews_message} m ON j.id = m.jobid
                WHERE (j.duration > 0) AND (j.duration * 24 * 3600 + j.timecreated < :now)
                AND (j.timefinished > 0)
                GROUP BY j.id
                HAVING (countmessages > 0)";

        if (!$expiredjobs = $DB->get_records_sql($sql, array('now' => time()))) {
            mtrace('... no expired messages.');
            return true;
        }

        $count = 0;
        foreach ($expiredjobs as $job) {

            $DB->delete_records('block_mbsnews_message', array('jobid' => $job->id));

            // Stop processing the message, if there is any processing left, by setting timefinished <> 0.
            $now = time();
            $DB->set_field('block_mbsnews_job', 'timefinished', $now, array('timefinished' => 0, 'id' => $job->id));

            $count++;
        }

        $cache = \cache::make('block_mbsnews', 'mebisnews');
        $cache->purge();

        mtrace('messages of ' . $count . ' expired jobs deleted.');
        return true;
    }
    
    /**
     * Delete all confirmed messages, that are belonging to a finished job.
     */
    public static function delete_confirmed_messages() {
        global $DB;
        
        $sql = "SELECT m.id
                FROM {block_mbsnews_message} m
                JOIN {block_mbsnews_job} j ON j.id = m.jobid
                WHERE m.timeconfirmed > 0 AND j.timefinished > 0";
        
        if (!$midstodelete = $DB->get_records_sql($sql)) {
            return true;
        }

        $DB->delete_records_list('block_mbsnews_message', 'id', array_keys($midstodelete));
        mtrace('deleted: '.count($midstodelete). ' confirmed message(s)');
        
        return true;
    }

}
