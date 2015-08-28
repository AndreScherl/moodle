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
 * @copyright 2015 Janek Lasocki-Biczysko, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

class starrating extends \moodleform {

    public function definition() {

        $strrequired = get_string('required');

        $mform = $this->_form;

        $radioarray = array();
        for ($i = 1; $i <= 5; $i++) {
            $radioarray[] =& $mform->createElement('radio', 'block_mbstpl_rating', '',
                get_string('rating_star', 'block_mbstpl', $i), $i);
        }

        $mform->addGroup($radioarray, 'radioar', get_string('rating', 'block_mbstpl'), null, false);
        $mform->addGroupRule('radioar', $strrequired, 'required');

        $mform->addElement('text', 'block_mbstpl_rating_comment',
            get_string('rating_comments', 'block_mbstpl'), array('maxlength' => 200, 'size' => 100));

        $mform->setType('block_mbstpl_rating_comment', PARAM_RAW_TRIMMED);

        $buttonarray = array();
        $buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('rating_submitbutton', 'block_mbstpl'));
        $buttonarray[] =& $mform->createElement('submit', 'cancel', get_string('rating_cancelbutton', 'block_mbstpl'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

    protected function get_form_identifier() {
        return str_replace('\\', '_', parent::get_form_identifier());
    }
}
