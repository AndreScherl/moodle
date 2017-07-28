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
 * Adds new instance of enrol_mbstplaenrl to specified course
 * or edits current instance.
 *
 * @package    enrol_mbstplaenrl
 * @copyright  2016 Yair Spielmann, Synergy Learning for ALP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_mbstplaenrl;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

class edit_form extends \moodleform {

    public function definition() {
        global $DB;

        $mform = $this->_form;

        list($instance) = $this->_customdata;
        $defaultroleid = get_config('enrol_mbstplaenrl', 'defaultrole');

        $mform->addElement('header', 'header', get_string('pluginname_desc', 'enrol_mbstplaenrl'));

        $roleobjs = $DB->get_records('role_context_levels', array('contextlevel' => CONTEXT_COURSE), null, 'roleid');

        $rolenames = role_fix_names($roleobjs);

        $options = array(0 => get_string('select'));
        $options[$defaultroleid] = $rolenames[$defaultroleid]->localname;

        $mform->addElement('select', 'roleid', get_string('defaultrole', 'enrol_mbstplaenrl'), $options);
        $mform->setDefault('roleid', $instance->roleid);

        $this->add_action_buttons(true, ($instance->id ? null : get_string('instance_save', 'enrol_mbstplaenrl')));
    }

}
