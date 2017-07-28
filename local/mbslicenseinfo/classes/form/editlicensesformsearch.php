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
 * @package     local_mbslicenseinfo
 * @copyright   2016, Franziska Hübler, ISB Bayern
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mbslicenseinfo\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/mbs/classes/form/MoodleQuickForm_lookupset.php');

class editlicensesformsearch extends \moodleform {

    protected function definition() {

        $mform = $this->_form;

        $searcharray = array();
        $searcharray[] =& $mform->createElement('text','filesearch', get_string('searchfiles', 'local_mbslicenseinfo'));
        $searcharray[] =& $mform->createElement('submit', 'submitbutton', get_string('search'));
        $mform->setType('filesearch', PARAM_TEXT);
        $mform->addGroup($searcharray, 'search', get_string('searchfiles', 'local_mbslicenseinfo'), array(' '), false);
    }
}