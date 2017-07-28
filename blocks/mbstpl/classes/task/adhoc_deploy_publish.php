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
 * @copyright 2016 Andreas Wagner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\task;

defined('MOODLE_INTERNAL') || die();

class adhoc_deploy_publish extends \core\task\adhoc_task {

    public function execute($rethrowexception = false) {

        $tempdetails = $this->get_custom_data();
        $template = new \block_mbstpl\dataobj\template(array('id' => $tempdetails->id), true, MUST_EXIST);

        try {
            \block_mbstpl\backup::backup_published($template->courseid, $template);

            if (!\block_mbstpl\course::publish($template)) {
                throw new \moodle_exception('missingpermisson', 'block_mbstpl', $template->courseid);
            }

        } catch (\moodle_exception $e) {

            \block_mbstpl\notifications::notify_error('errordeploypublish', $e);
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
