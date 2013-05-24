<?php
// This file is part of the category backup plugin for Moodle - http://moodle.org/
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
 * Form for choosing category to backup in category backup plugin
 *
 * @package    local_categorybackup
 * @copyright  2012 Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/course/lib.php');

class local_categorybackup_restore_form extends moodleform {
    function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'restoreform', get_string('restorecategory', 'local_categorybackup'));

        $categorylist = array(0 => get_string('top'));
        $parentlist = array();
        make_categories_list($categorylist, $parentlist);
        $mform->addElement('select', 'destcategory', get_string('destcategory', 'local_categorybackup'), $categorylist);
        $mform->addHelpButton('destcategory', 'destcategory', 'local_categorybackup');
        $mform->addElement('text', 'backuppath', get_string('backuppath', 'local_categorybackup'), array('size' => 60));
        $mform->addHelpButton('backuppath', 'backuppath', 'local_categorybackup');

        $this->add_action_buttons(false, get_string('dorestore', 'local_categorybackup'));
    }
}