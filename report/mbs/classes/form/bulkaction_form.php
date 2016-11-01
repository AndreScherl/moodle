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
 * @package    report_mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagner@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_mbs\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/formslib.php');

class bulkaction_form extends \moodleform {

    protected function definition() {

        $mform = $this->_form;

        $choices = ['' => get_string('select')];
        $choices += \report_mbs\local\reportcourses::get_actions_menu();

        $mform->addElement('select', 'bulkaction', get_string('bulkaction', 'report_mbs'), $choices);

        $mform->addElement('hidden', 'courseids', '', array('id' => 'id_courseids'));
        $mform->setType('courseids', PARAM_TEXT);

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'doaction', get_string('doaction', 'report_mbs'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}
