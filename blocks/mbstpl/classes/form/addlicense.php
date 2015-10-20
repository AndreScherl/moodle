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

class addlicense extends \moodleform {

    function definition() {

        global $CFG;
        require_once($CFG->dirroot.'/blocks/mbstpl/classes/MoodleQuickForm_newlicense.php');

        $form = $this->_form;

        $form->addElement('text', 'newlicense_shortname', get_string('newlicense_shortname', 'block_mbstpl'));
        $form->addElement('text', 'newlicense_fullname', get_string('newlicense_fullname', 'block_mbstpl'));
        $form->addElement('text', 'newlicense_source', get_string('newlicense_source', 'block_mbstpl'));
        $form->setTypes(array(
            'newlicense_shortname' => PARAM_TEXT,
            'newlicense_fullname' => PARAM_TEXT,
            'newlicense_source' => PARAM_TEXT
        ));

        $form->addRule('newlicense_shortname', get_string('newlicense_required', 'block_mbstpl'), 'required');

        $this->add_action_buttons(false, get_string('newlicense_add', 'block_mbstpl'));
    }

    public function validation($data, $files) {
        $shortname = $data['newlicense_shortname'];
        $existinglicense = \block_mbstpl\dataobj\license::fetch(array('shortname' => $shortname));
        if ($existinglicense) {
            return array('newlicense_shortname' => get_string('newlicense_exists', 'block_mbstpl', $shortname));
        }
        return array();
    }

}
