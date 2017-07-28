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

use block_mbstpl\dataobj\meta;
use block_mbstpl\user;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/mbs/classes/form/MoodleQuickForm_license.php');
require_once($CFG->dirroot . '/local/mbs/classes/form/MoodleQuickForm_newlicense.php');

abstract class licenseandassetform extends \moodleform {

    protected $licenseindex = 4;

    public static function update_meta_license_from_submitted_data(meta $meta, $data) {
        $submittedlicense = $data->license;
        if ($meta->license != $submittedlicense) {
            $meta->license = $submittedlicense;
            $meta->update();
        }
    }

    /**
     * Adds license information for the contents provided by the course author.
     * 
     * @global type $OUTPUT
     * @param bool $iscreator
     */
    protected function define_license($iscreator) {
        global $OUTPUT; 
        $form = $this->_form;        
        if ($iscreator) {
            $labelstring = get_string('license', 'block_mbstpl');        
            $labelstring .= $OUTPUT->help_icon('license', 'block_mbstpl');
            $form->addElement('license', 'license', $labelstring, null, false);
            $form->addRule('license', null, 'required');
        } else {
            $labelstring = get_string('duplcourselicense', 'block_mbstpl');
            $labelstring .= $OUTPUT->help_icon('license', 'block_mbstpl');
            $template = $this->_customdata['template'];
            $creator = \block_mbstpl\course::get_creators($template->id);
            $licence = $template->get_license();
            $licencelink = \html_writer::link($licence->source, $licence->fullname);
            $licencestring = get_string('duplcourselicensedefault', 'block_mbstpl', array(
                'creator' => $creator,
                'licence' => (string) $licencelink
            ));
            $form->addElement('static', 'licence', $labelstring, $licencestring);        
        }           
    }

    public function set_data($default_values) {
        global $PAGE;
        parent::set_data($default_values);

        $args = array();
        $PAGE->requires->yui_module('moodle-local_mbs-newlicense', 'M.local_mbs.newlicense.init', $args, null, true);
    }

    protected function define_tags($isfrozen = false) {
        $attributes = array('size' => 30);
        if (!$isfrozen) {
            $attributes = array('placeholder' => get_string('tagsplaceholder', 'block_mbstpl'));
        }        
        $this->_form->addElement('text', 'tags', get_string('tags', 'block_mbstpl'), $attributes);
        $this->_form->setType('tags', PARAM_TEXT);
        $this->_form->addHelpButton('tags', 'tagshelpbutton', 'block_mbstpl');
    }

    protected function define_creator() {
        $creator = '';
        if (!empty($this->_customdata['creator'])) {
            $creator = user::format_creator_name($this->_customdata['creator']);
        }
        $this->_form->addElement('static', 'creator', get_string('creator', 'block_mbstpl'), $creator);
    }

    /**
     * Adds legal data questions and license information for the contents provided by the course author.
     * 
     * @param bool $includechecklist
     * @param bool $includecheckbox
     * @param bool $iscreator
     */
    protected function define_legalinfo_fieldset($includechecklist = true, $includecheckbox = true, $iscreator = true) {

        $this->_form->addElement('header', 'legalinfo', get_string('legalinfo', 'block_mbstpl'));

        // License.
        $this->define_license($iscreator);

        // Legal data questions.
        if ($includechecklist) {
            $this->define_questions('checklist');
        }
        if ($includecheckbox) {
            $this->define_questions('checkbox');
        }

        $this->_form->setExpanded('legalinfo');

        $this->_form->closeHeaderBefore('legalinfo');
    }

    protected function define_questions($quedatatype) {
        foreach ($this->_customdata['questions'] as $question) {
            if ($question->datatype == $quedatatype) {
                $typeclass = \block_mbstpl\questman\qtype_base::qtype_factory($question->datatype);
                $typeclass::add_template_element($this->_form, $question);
                $typeclass::add_rule($this->_form, $question);
            }
        }
    }
    
    public function validation($data, $files) {
        $errors = array();
        $questions = $this->_customdata['questions'];
        foreach($questions as $q) {
            $typeclass = \block_mbstpl\questman\qtype_base::qtype_factory($q->datatype);
            $errors = array_merge($errors, $typeclass::validate_question($data, $q));
        }
        return $errors;
    }
}
