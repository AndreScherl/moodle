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
 * Evebt observer
 *
 * @package    enrol_mbs
 * @copyright  2015 Janek Lasocki-Biczysko, Synergy Learning for ALP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_mbs;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer
 */
class observer {

    /**
     * Should be registered as the handler for the \block_mbstpl\event\template_created event
     *
     * @param \block_mbstpl\event\template_created $event
     */
    public static function template_created(\block_mbstpl\event\template_created $event) {

        // We only care about newly created templates.
        $template = \block_mbstpl\dataobj\template::fetch(array('courseid' => $event->objectid));
        if (!$template) {
            return;
        }

        if (!enrol_is_enabled('mbs')) {
            return;
        }

        $plugin = enrol_get_plugin('mbs');
        if (!$plugin->get_config('auto_reset')) {
            return;
        }

        $course = get_course($event->objectid);
        $instanceid = $plugin->add_instance($course, $plugin->get_instance_defaults());

        $instance = \enrol_mbs_plugin::get_instance($instanceid);

        task\reset_course_userdata_task::schedule_single_reset_task($instance);
    }

}
