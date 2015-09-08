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
 * report pimped courses (style and js customisations using html - block)
 * report form.
 * 
 * @package    report
 * @subpackage mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagern@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/lib/formslib.php');

class reportpimped_form extends moodleform {

    protected function definition() {

        $mform = $this->_form;

        $searchpattern = $this->_customdata['searchpattern'];

        if (empty($searchpattern)) {
            $config = get_config('report_mbs');
            $searchpattern = $config->searchpattern;
        }

        $mform->addElement('text', 'searchpattern', get_string('searchpattern', 'report_mbs'));
        $mform->setDefault('searchpattern', $searchpattern);
        $mform->setType('searchpattern', PARAM_RAW);
        $mform->addHelpButton('searchpattern', 'searchpattern', 'report_mbs');

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'search', get_string('search', 'report_mbs'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

    }
}