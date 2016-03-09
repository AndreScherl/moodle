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
 * Form to edit the title (i. e. the logo and the name of a school)
 *
 * @package   block_mbsnews
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbsnews\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/local/mbs/classes/form/MoodleQuickForm_lookupset.php');

class editjob_form extends \moodleform {

    public function definition() {
        global $PAGE, $USER;

        $mform = $this->_form;

        // This is necessary, if you use a client side validation.
        $mform->addElement('header', 'headersettings', get_string('general'));

        $mform->addElement('static', 'sendername', get_string('sendername', 'block_mbsnews'), fullname($USER));

        $mform->addElement('hidden', 'sender', $USER->id);
        $mform->setType('sender', PARAM_INT);

        // Context.
        $choices = array(0 => get_string('select'));
        $choices[CONTEXT_SYSTEM] = get_string('contextsystem', 'block_mbsnews');
        $choices[CONTEXT_COURSECAT] = get_string('contextcategory', 'block_mbsnews');
        $choices[CONTEXT_COURSE] = get_string('contextcourse', 'block_mbsnews');

        $mform->addElement('select', 'contextlevel', get_string('contextlevel', 'block_mbsnews'), $choices);

        $ajaxurl = new \moodle_url('/blocks/mbsnews/ajax.php', array('action' => 'searchinstances'));
        $mform->addElement('lookupset', 'instanceids', get_string('instances', 'block_mbsnews'), $ajaxurl, array('id_contextlevel'));
        $mform->setType('instanceids', PARAM_INT);

        // Roles.
        $choices = array(0 => get_string('select'));
        $mform->addElement('select', 'roleselector', get_string('roleid', 'block_mbsnews'), $choices);
        $mform->disabledIf('roleselector', 'contextlevel', 'eq', 0);

        // Need a hidden element to submit the roleid, when roleselector is filled by AJAX.
        $mform->addElement('hidden', 'roleid', 0, array('id' => 'id_roleid'));
        $mform->setType('roleid', PARAM_INT);

        // Number of Recipients.
        $mform->addElement('static', 'recipients', get_string('recipients', 'block_mbsnews'), \html_writer::tag('span', '', array('id' => 'id_recipients')));

        $mform->addElement('hidden', 'countrecipients', 0, array('id' => 'id_countrecipients'));
        $mform->setType('countrecipients', PARAM_INT);

        // Subject.
        $mform->addElement('text', 'subject', get_string('subject', 'block_mbsnews'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');

        // Editor.
        $mform->addElement('editor', 'fullmessage', get_string('fullmessage', 'block_mbsnews'));
        $mform->setType('fullmessage', PARAM_CLEANHTML);
        $mform->addRule('fullmessage', null, 'required', null, 'server');

        // TODO: Implement duration of display: mark messages as read or only don't display them?
        $choices = array();
        for ($i = 0; $i <= 100; $i++) {
            $choices[$i] = $i;
        }
        $mform->addElement('select', 'duration', get_string('duration', 'block_mbsnews'), $choices);
        $mform->addHelpButton('duration', 'duration', 'block_mbsnews');

        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);

        // Buttons.
        $this->add_action_buttons(true);

        $args = array();
        $args['url'] = $ajaxurl->out();

        $PAGE->requires->yui_module('moodle-block_mbsnews-editjobform', 'M.block_mbsnews.editjobform', array($args));
    }

    /**
     * Setup the editor text properly.
     * 
     * @param object $defaultvalues
     */
    public function set_data($defaultvalues) {

        if (!isset($defaultvalues->fullmessage['text'])) {

            $defaultvalues->fullmessage = array('text' => $defaultvalues->fullmessage);
        }

        parent::set_data($defaultvalues);
    }

}
