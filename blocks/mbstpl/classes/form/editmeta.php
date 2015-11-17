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
use \block_mbstpl as mbst;

defined('MOODLE_INTERNAL') || die();

/**
 * Class editmeta
 * @package block_mbstpl
 * Edit tempalte meta form.
 */

class editmeta extends licenseandassetform {
    function definition() {

        $form = $this->_form;
        $cdata = $this->_customdata;
        $template = isset($cdata['template']) ? $cdata['template'] : null;
        $course = isset($cdata['course']) ? $cdata['course'] : null;

        $form->addElement('hidden', 'course', $this->_customdata['courseid']);
        $form->setType('course', PARAM_INT);

        // Add template details if exists.
        if (isset($template) && isset($course)) {
            $form->addElement('header', 'coursemetadata', get_string('coursemetadata', 'block_mbstpl'));
            $form->addElement('static', 'crsname', get_string('course'), $course->fullname);
            $form->addElement('static', 'creationdate', get_string('creationdate', 'block_mbstpl'), userdate($course->timecreated));
            $form->addElement('static', 'lastupdate', get_string('lastupdate', 'block_mbstpl'), userdate($template->timemodified));
            $creator = mbst\course::get_creators($template->id);
            $form->addElement('static', 'creator', get_string('creator', 'block_mbstpl'), $creator);
            $form->setExpanded('coursemetadata');
            $form->closeHeaderBefore('coursemetadata');
        }

        // Add custom questions.
        $questions = $cdata['questions'];
        foreach($questions as $question) {
            if ($question->datatype != 'checklist') {
                $typeclass = mbst\questman\qtype_base::qtype_factory($question->datatype);
                $typeclass::add_template_element($form, $question);
            }
        }

        if (empty($cdata['justtags'])) {
            mbst\questman\qtype_checklist::edit_comments(true);

            $includechecklist = empty($cdata['freeze']);
            $this->define_legalinfo_fieldset($includechecklist);
        } else {
            $this->define_tags();
        }

        if (!empty($cdata['withrating']) && !empty($template->rating)) {
            global $PAGE;
            $renderer = $PAGE->get_renderer('block_mbstpl');
            $ratingelement = $form->addElement('static', 'rating', get_string('ratingavg', 'block_mbstpl'), $renderer->rating($template->rating, false));
            $ratingelement->_type = 'html';
        }

        $this->add_action_buttons(true, get_string('save', 'block_mbstpl'));

        if (!empty($cdata['freeze'])) {
            $form->freeze();
        }
    }

    function definition_after_data() {
        mbst\questman\qtype_checklist::definition_after_data($this->_form);
    }

    function set_data($default_values) {
        if (!empty($this->_customdata['freeze'])) {
            parent::set_data($default_values);
            return;
        }
        if (is_object($default_values)) {
            $default_values = (array)$default_values;
        }
        $data = array();
        foreach($default_values as $key => $value) {
            if (!is_array($value) || !isset($value['text'])) {
                $data[$key] = $value;
                continue;
            }
            $type = $this->_form->getElementType($key);
            if ($type == 'editor') {
                $data[$key] = $value;
            } else {
                $data[$key] = $value['text'];
            }
        }
        parent::set_data($data);
    }
}
