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
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class activatedraft
 * @package block_mbstpl
 * Main question form
 */

class activatedraft extends \moodleform {
    protected function definition() {
        $form = $this->_form;

        $form->addElement('text', 'formname', get_string('name'));
        $form->setType('formname', PARAM_TEXT);
        $form->addRule('formname', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons(false, get_string('qformactivate', 'block_mbstpl'));
    }

}