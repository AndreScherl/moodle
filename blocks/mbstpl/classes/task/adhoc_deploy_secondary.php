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
 * @package block_mbstpl
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\task;


use block_mbstpl\backup;

defined('MOODLE_INTERNAL') || die();

class adhoc_deploy_secondary extends \core\task\adhoc_task {

    private $courseid;

    public function get_courseid() {
        return $this->courseid;
    }

    public function execute($rethrowexception = false) {
        $details = $this->get_custom_data();
        $template = new \block_mbstpl\dataobj\template($details->tplid, true, MUST_EXIST);
        try {
            $filename = backup::backup_secondary($template, $details->settings);
            $coursefromtpl = backup::restore_secondary($template, $filename, $details->settings, $details->requesterid);
            $this->courseid = $coursefromtpl->courseid;
            backup::build_html_block($coursefromtpl, $template);
            \block_mbstpl\user::enrol_teacher($this->courseid, $details->requesterid);
            \block_mbstpl\notifications::email_duplicated($details->requesterid, $this->courseid);
        } catch (\moodle_exception $e) {
            \block_mbstpl\notifications::notify_error('errordeploying', $e);
            if ($rethrowexception) {
                throw $e;
            }
            print_r($e->getMessage());
            print_r($e->getTrace());
            print_r($template);
        }

        return true;
    }
}
