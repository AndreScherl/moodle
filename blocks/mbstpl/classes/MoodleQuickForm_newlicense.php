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
 * @package   block_mbstpl
 * @copyright 2015 Janek Lasocki-Biczysko, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Would like to namespace this class, but that just doesn't work with MoodleQuickForm.

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/form/group.php');

class MoodleQuickForm_newlicense extends MoodleQuickForm_group {

    private $_licensename;

    function MoodleQuickForm_newlicense($elementName = null, $licensename = null) {

        $this->_licensename = $licensename;

        $elements = array_map(function($elname) {
            $elname = 'newlicense_' . $elname;
            $attrs = array('placeholder' => get_string($elname, 'block_mbstpl'));
            return @MoodleQuickForm::createElement('text', $elname, '', $attrs);
        }, array('shortname', 'fullname', 'source'));

        parent::__construct($elementName, null, $elements, null, false);
    }

    function accept(&$renderer, $required = false, $error = null) {
        parent::accept($renderer, $required, $error);

        global $PAGE;
        $PAGE->requires->yui_module('moodle-block_mbstpl-newlicense', 'M.block_mbstpl.newlicense.init', array(
            $this->getName(), $this->_licensename
        ), null, true);
    }

}

MoodleQuickForm::registerElementType('newlicense', __FILE__, 'MoodleQuickForm_newlicense');
