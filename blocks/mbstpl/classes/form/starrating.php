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

        global $PAGE;

        $mform = $this->_form;
        $radioname = 'block_mbstpl_rating';

        $radioarray = array();
        for ($i = 1; $i <= 5; $i++) {
            $radioarray[] =& $mform->createElement('radio', $radioname, '', '', $i);
        }

        $mform->addGroup($radioarray, 'radioar', get_string('rating', 'block_mbstpl'), '', false);
        $mform->addGroupRule('radioar', get_string('required'), 'required');

        $this->add_action_buttons(true, get_string('submitbutton', 'block_mbstpl')); 
        
        $PAGE->requires->yui_module('moodle-block_mbstpl-starrating', 'M.block_mbstpl.starrating.init', array($radioname));
    }

    protected function get_form_identifier() {
        return str_replace('\\', '_', parent::get_form_identifier());
    }
}