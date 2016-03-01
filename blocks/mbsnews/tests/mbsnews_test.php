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
defined('MOODLE_INTERNAL') || die();

class block_mbsnews_testcase extends advanced_testcase {
    /* public function test_plugin_installed() {
      $config = get_config('block_mbsnews');
      $this->assertTrue(isset($config->maxmessages));
      } */

    private function get_default_data() {
        global $DB, $USER;

        // Generate Job for all users with no expiration.
        $submitdata = new \stdClass();
        $submitdata->sender = $USER->id;
        $submitdata->roleid = 0;
        $submitdata->countrecipients = $DB->count_records('user');
        $submitdata->contextlevel = 0;
        $submitdata->instanceids = array();
        $submitdata->roleselector = 0;
        $submitdata->subject = 'Test news';
        $submitdata->fullmessage = array('text' => 'Messagetext', 'format' => 1);
        $submitdata->duration = 0;
        $submitdata->id = 0;

        return $submitdata;
    }

    private function do_cron() {
        \block_mbsnews\local\newshelper::delete_confirmed_messages();
        \block_mbsnews\local\newshelper::delete_expired_messages();
        \block_mbsnews\local\newshelper::process_notification_jobs();
    }

    public function test_cron() {
        global $DB, $USER;

        $this->resetAfterTest();

        $admin = get_admin();
        \core\session\manager::set_user($admin);
        
        // proceed one message per cron job.
        set_config('maxmessages', 2, 'block_mbsnews');

        $generator = $this->getDataGenerator();

        // Generate users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();

        $submitdata = $this->get_default_data();

        $result = \block_mbsnews\local\newshelper::save_notification_job($submitdata);
        $this->assertEquals(0, $result['error']);

        // Start cron.
        $this->do_cron();

        // Check result, two delivered, job not finished.
        $messages = $DB->get_records('block_mbsnews_message');
        $this->assertEquals(2, count($messages));

        // Continue cron.
        $this->do_cron();
        $messages = $DB->get_records('block_mbsnews_message');
        $this->assertEquals(4, count($messages));

        $firstmessage = $DB->get_record('block_mbsnews_message', array('usertoid' => $USER->id));
        \block_mbsnews\local\newshelper::mark_message_read($firstmessage);

        $this->do_cron();
        $messages = $DB->get_records('block_mbsnews_message');
        $this->assertEquals(6, count($messages));

        // Next cron should mark job as finished;
        $jobs = $DB->get_records('block_mbsnews_job');
        $job = reset($jobs);

        $this->assertEquals(0, $job->timefinished);
        $this->do_cron();

        $jobs = $DB->get_records('block_mbsnews_job');
        $job = reset($jobs);
        $this->assertNotEquals(0, $job->timefinished);

        // Next Cronjob should delete the read message, 5 messages remains.
        $this->do_cron();

        $messages = $DB->get_records('block_mbsnews_message');
        $this->assertEquals(5, count($messages));
        
        // Check message expiration.
        // Set job created before 5 days and expiration 1 day.
        // Should deleted all messages!
        $fivedaysago = time() - 5 * 24 * 3600;
        $DB->set_field('block_mbsnews_job', 'timecreated', $fivedaysago);
        $DB->set_field('block_mbsnews_job', 'duration', 1);
        
        $this->do_cron();

        $messages = $DB->get_records('block_mbsnews_message');
        $this->assertEquals(0, count($messages));
    }
}
