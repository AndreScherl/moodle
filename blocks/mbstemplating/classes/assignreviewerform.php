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
 * @package block_mbstemplating
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstemplating;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class assignreviewerform
 * @package block_mbstemplating
 * Main question form
 */

class assignreviewerform extends \moodleform {
    function definition() {
        $form = $this->_form;

        $form->addElement('hidden', 'course', $this->_customdata['courseid']);
        $form->setType('course', PARAM_INT);

        $options = array();
        foreach($this->_customdata['users'] as $user) {
            $options[$user->id] = $user->firstname .' ' . $user->lastname;
        }
        $form->addElement('select', 'reviewerid', get_string('selectedreviewer', 'block_mbstemplating'), $options);

        $this->add_action_buttons(true, get_string('assignreviewer', 'block_mbstemplating'));
    }

}