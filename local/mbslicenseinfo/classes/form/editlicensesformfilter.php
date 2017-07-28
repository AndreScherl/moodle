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
 * @package local_mbslicenseinfo
 * @copyright 2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mbslicenseinfo\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use \local_mbslicenseinfo\local\mbslicenseinfo as mbslicenseinfo;

class editlicensesformfilter extends \moodleform {

    protected function definition() {

        $mform = $this->_form;
        $onlymine = $this->_customdata['onlymine'];
        $onlyincomplete = $this->_customdata['onlyincomplete'];
        $captype = $this->_customdata['captype'];

        $group = array();
        $choices = array(
            0 => get_string('showcompleteandimcomplete', 'local_mbslicenseinfo'),
            1 => get_string('showonlyincomplete', 'local_mbslicenseinfo')
        );
        $group[] = $mform->createElement('select', 'onlyincomplete', '', $choices);
        $mform->setDefault('onlyincomplete', $onlyincomplete);

        switch ($captype) {

            case mbslicenseinfo::$captype_editall :

                $choices = array(
                    1 => get_string('showownlicenses', 'local_mbslicenseinfo'),
                    0 => get_string('showalllicenses', 'local_mbslicenseinfo')
                );
                $group[] = $mform->createElement('select', 'onlymine', '', $choices);
                $mform->setDefault('onlymine', $onlymine);
                break;

            case mbslicenseinfo::$captype_editown :

                $mform->addElement('hidden', 'onlymine');
                $mform->setType('onlymine', PARAM_INT);
                $mform->setConstant('onlymine', 1);
                break;
            
            case mbslicenseinfo::$captype_viewall :
                
                $mform->addElement('hidden', 'onlymine');
                $mform->setType('onlymine', PARAM_INT);
                $mform->setConstant('onlymine', 0);
                break;
        }

        $group[] = $mform->createElement('submit', 'submitbutton', get_string('setfilter', 'local_mbslicenseinfo'));

        $mform->addGroup($group);

        $url = get_string('editlicenses_notelink', 'local_mbslicenseinfo');
        $text = get_string('editlicenses_note', 'local_mbslicenseinfo');
        $link = \html_writer::link($url, $text, array('class' => 'internal', 'target' => '_blank'));
        $mform->addElement('static', 'editinfo', '', $link);
    }

}
