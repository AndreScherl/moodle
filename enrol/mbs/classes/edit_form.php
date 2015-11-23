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
 * Adds new instance of enrol_mbs to specified course
 * or edits current instance.
 *
 * @package    enrol_mbs
 * @copyright  2015 Janek Lasocki-Biczysko, Synergy Learning for ALP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_mbs;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class edit_form extends \moodleform {

    public static function get_days() {
        return array(
            0 => get_string('sunday', 'calendar'),
            1 => get_string('monday', 'calendar'),
            2 => get_string('tuesday', 'calendar'),
            3 => get_string('wednesday', 'calendar'),
            4 => get_string('thursday', 'calendar'),
            5 => get_string('friday', 'calendar'),
            6 => get_string('saturday', 'calendar')
        );
    }

    public function definition() {

        $mform = $this->_form;

        list($instance) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('pluginname_desc', 'enrol_mbs'));

        $mform->addElement('checkbox', 'cron_enable', get_string('cron_enable', 'enrol_mbs'));

        $days = self::get_days();
        $checkboxes = array_map(function($index, $day) {
            return $this->_form->createElement('checkbox', $index, null, $day);
        }, array_keys($days), $days);

        $mform->addGroup($checkboxes, 'cron_days', get_string('cron_days', 'enrol_mbs'), '&nbsp;&nbsp;&nbsp;&nbsp;', true);

        $mform->addElement(new \enrol_mbs\time_selector('cron_time', get_string('cron_time', 'enrol_mbs')));

        $this->add_action_buttons(true, ($instance->id ? null : get_string('instance_save', 'enrol_mbs')));

        // Set defaults from plugin settings.
        $this->set_data(array(
            'cron_enable' => $instance->customint1,
            'cron_time' => array(
                'hour' => $instance->customint2,
                'minute' => $instance->customint3
            ),
            'cron_days' => $this->build_cron_days_data($instance->customtext1, count($checkboxes))
        ));
    }

    private function build_cron_days_data($daysstr, $numdays) {
        $cron_days = array();
        $days = explode(',', $daysstr);
        for ($i = 0; $i < $numdays; $i++) {
            $cron_days[$i] = in_array($i, $days);
        }
        return $cron_days;
    }

}
