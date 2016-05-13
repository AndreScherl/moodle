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


use block_mbstpl as mbst;

defined('MOODLE_INTERNAL') || die();

class adhoc_deploy_revision extends \core\task\adhoc_task {
    public function execute() {
        $details = $this->get_custom_data();
        $template = new mbst\dataobj\template($details->templateid, true, MUST_EXIST);
        try {
            $filename = mbst\backup::backup_revision($template);
            $cid = mbst\backup::restore_revision($template, $filename, $details->reasons);
        } catch (\moodle_exception $e) {
            \block_mbstpl\notifications::notify_error('errordeploying', $e);
            print_r($e->getMessage());
            print_r($e->getTrace());
            print_r($template);
        }
        return true;
    }
}
