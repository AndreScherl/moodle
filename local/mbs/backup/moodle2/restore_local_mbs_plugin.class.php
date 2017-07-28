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
 * Extra steps to trigger during course restore
 *
 * @package   local_mbs
 * @copyright 2015 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class restore_local_mbs_plugin extends restore_local_plugin {
    protected function define_course_plugin_structure() {
        // If this function does not return at least one element, then the after_restore_course function
        // will not be called.
        return array(new restore_path_element('fake_element', $this->get_pathfor('/fake_element')));
    }

    public function process_fake_element($data) {
        // Nothing to do - here just in case (somehow) the fake_element actually matches something in the backup.
    }

    public function after_restore_course() {
        if (class_exists('block_mbstpl\backup')) {
            // Remap the cmids of the elements that should not have userdata propagated when the template course is deployed.
            block_mbstpl\backup::fix_exclude_deploydata_ids($this->get_restoreid());
        }
    }
}
