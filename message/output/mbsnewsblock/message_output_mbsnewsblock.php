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
 * Mebis News Block message processor, stores messages to be shown using the mebis news block.
 *
 * @package   message_mbsnewsblock
 * @copyright 2016 Andreas Wagner, ISB
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php'); //included from messagelib (how to fix?)
require_once($CFG->dirroot . '/message/output/lib.php');

class message_output_mbsnewsblock extends message_output {

    public function send_message($eventdata) {
        global $DB;

        static $processorid = null;

        if (empty($processorid)) {
            $processor = $DB->get_record('message_processors', array('name' => 'mbsnewsblock'));
            $processorid = $processor->id;
        }
        $procmessage = new stdClass();
        $procmessage->unreadmessageid = $eventdata->savedmessageid;
        $procmessage->processorid = $processorid;

        // Save this message for later delivery.
        $DB->insert_record('message_working', $procmessage);

        return true;
    }

    /**
     * Creates necessary fields in the messaging config form.
     *
     * @param array $preferences An array of user preferences
     */
    public function config_form($preferences) {
        return null;
    }

    /**
     * Parses the submitted form data and saves it into preferences array.
     *
     * @param stdClass $form preferences form class
     * @param array $preferences preferences array
     */
    public function process_form($form, &$preferences) {
        return true;
    }

    /**
     * Loads the config data from database to put on the form during initial form display
     *
     * @param array $preferences preferences array
     * @param int $userid the user id
     */
    public function load_data(&$preferences, $userid) {
        global $USER;
        return true;
    }

}
