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
 * Special question type for displaying a checklist of the criteria the course
 * should meet.
 *
 * @package   block_mbstpl
 * @copyright 2015 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\questman;

defined('MOODLE_INTERNAL') || die();

class qtype_checklist extends qtype_base {

    const ANSWER_YES = 1;
    const ANSWER_NO = 2;
    const ANSWER_NA = 3;

    protected static $editcomments = false;
    protected static $checkremove = array();

    public static function extend_form(\MoodleQuickForm $form, $islocked = false) {
        $expln = \html_writer::tag('p', get_string('checklistexpln', 'block_mbstpl'));
        $form->addElement('html', $expln);
        $form->addElement('hidden', 'defaultdata', null);
        $form->setType('defaultdata', PARAM_RAW);
    }

    public static function add_template_element(\MoodleQuickForm $form, $question) {
        $label = \html_writer::label(format_string($question->title), 'id_'.$question->fieldname, true,
                                     array('class' => 'mbstpl-questionlabel'));
        $label = \html_writer::div($label);
        $form->addElement('html', $label);

        $radiogroup = array(
            $form->createElement('radio', $question->fieldname, '', get_string('yes'), self::ANSWER_YES),
            $form->createElement('radio', $question->fieldname, '', get_string('no'), self::ANSWER_NO),
            $form->createElement('radio', $question->fieldname, '', get_string('na', 'block_mbstpl'), self::ANSWER_NA),
        );
        $form->addGroup($radiogroup, $question->fieldname.'_group', '', null, false);

        $commentname = $question->fieldname.'_comment';
        $form->addElement('textarea', $commentname, get_string('comment', 'block_mbstpl'), array('rows' => 3, 'cols' => 30));
        $form->setType($commentname, PARAM_TEXT);
        if (!self::$editcomments) {
            self::$checkremove[] = $commentname; // Note the name of the field, so it can be removed if there is no comment.
            $form->hardFreeze(array($commentname));
        }
    }

    protected static function definition_after_data_internal(\MoodleQuickForm $form) {
        foreach (self::$checkremove as $fieldname) {
            if (!$form->elementExists($fieldname)) {
                continue;
            }
            if (trim($form->getElementValue($fieldname)) == '') {
                $form->removeElement($fieldname);
            }
        }
        self::$checkremove = array();
    }

    /**
     * Allow reviewers to edit comments (authors can only view them).
     *
     * @param bool $edit
     */
    public static function edit_comments($edit) {
        self::$editcomments = $edit;
    }
}