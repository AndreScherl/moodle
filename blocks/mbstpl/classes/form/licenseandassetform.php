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

    protected function define_license() {
        $form = $this->_form;
        $form->addElement('license', 'license', get_string('license', 'block_mbstpl'), null, false);
        $form->addRule('license', null, 'required');
    }

    public function set_data($default_values) {
        global $PAGE;
        parent::set_data($default_values);

        $args = array();
        $PAGE->requires->yui_module('moodle-local_mbs-newlicense', 'M.local_mbs.newlicense.init', $args, null, true);
    }

    protected function define_tags() {
        $this->_form->addElement('text', 'tags', get_string('tags', 'block_mbstpl'), array('size' => 30));
        $this->_form->setType('tags', PARAM_TEXT);
    }

    protected function define_creator() {
        $creator = '';
        if (!empty($this->_customdata['creator'])) {
            $creator = user::format_creator_name($this->_customdata['creator']);
        }
        $this->_form->addElement('static', 'creator', get_string('creator', 'block_mbstpl'), $creator);
    }

    protected function define_legalinfo_fieldset($includechecklist = true, $includecheckbox = true) {

        $this->_form->addElement('header', 'legalinfo', get_string('legalinfo', 'block_mbstpl'));

        // License.
        $this->define_license();

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
}
