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
use block_mbstpl\user;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class editmeta
 * @package block_mbstpl
 * Edit tempalte meta form.
 */

class editmeta extends \moodleform {
    protected function definition() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/mbstpl/classes/MoodleQuickForm_license.php');

        $form = $this->_form;
        $cdata = $this->_customdata;

        $form->addElement('hidden', 'course', $this->_customdata['courseid']);
        $form->setType('course', PARAM_INT);

        // Add template details if exists.
        if (!empty($cdata['template']) && !empty($cdata['course'])) {
            $form->addElement('static', 'crsname', get_string('course'), $cdata['course']->fullname);
            $form->addElement('static', 'creationdate',
                get_string('creationdate', 'block_mbstpl'), userdate($cdata['course']->timecreated));
            $form->addElement('static', 'lastupdate',
                get_string('lastupdate', 'block_mbstpl'), userdate($cdata['template']->timemodified));
            $creator = mbst\course::get_creators($cdata['template']->id);
            $form->addElement('static', 'creator', get_string('creator', 'block_mbstpl'), $creator);
        }

        // Add custom questions.
        $questions = $cdata['questions'];
        foreach ($questions as $question) {
            if ($question->datatype != 'checklist') {
                $typeclass = mbst\questman\qtype_base::qtype_factory($question->datatype);
                $typeclass::add_template_element($form, $question);
            }
        }

        // License options.
        $form->addElement('license', 'license', get_string('license', 'block_mbstpl'));
        $form->addRule('license', null, 'required');

        // List of 3rd-party assets.
        $asset = array();
        $asset[] = $form->createElement('hidden', 'asset_id');
        $asset[] = $form->createElement('text', 'asset_url', get_string('url', 'block_mbstpl'),
                                        array(
                                            'size' => '30', 'inputmode' => 'url',
                                            'placeholder' => get_string('url', 'block_mbstpl')
                                        ));
        $asset[] = $form->createElement('license', 'asset_license', get_string('license', 'block_mbstpl'));
        $asset[] = $form->createElement('text', 'asset_owner', get_string('owner', 'block_mbstpl'),
                                        array('size' => '20', 'placeholder' => get_string('owner', 'block_mbstpl')));
        $assetgroup = $form->createElement('group', 'asset', get_string('assets', 'block_mbstpl'), $asset, null, false);

        $repeatcount = isset($this->_customdata['assetcount']) ? $this->_customdata['assetcount'] : 1;
        $repeatcount += 2;
        $repeatopts = array(
            'asset_id' => array('type' => PARAM_INT),
            'asset_url' => array('type' => PARAM_URL),
            'asset_owner' => array('type' => PARAM_TEXT)
        );
        $this->repeat_elements(array($assetgroup), $repeatcount, $repeatopts, 'assets', 'assets_add', 3,
                               get_string('addassets', 'block_mbstpl'));

        // Tags.
        $form->addElement('text', 'tags', get_string('tags', 'block_mbstpl'), array('size' => 30));
        $form->setType('tags', PARAM_TEXT);

        // Creator.
        $creator = '';
        if (!empty($this->_customdata['creator'])) {
            $creator = user::format_creator_name($this->_customdata['creator']);
        }
        $form->addElement('static', 'creator', get_string('creator', 'block_mbstpl'), $creator);

        // Checklist questions.
        mbst\questman\qtype_checklist::edit_comments(true);
        foreach ($questions as $question) {
            if ($question->datatype == 'checklist') {
                $typeclass = mbst\questman\qtype_base::qtype_factory($question->datatype);
                $typeclass::add_template_element($form, $question);
            }
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
        foreach ($default_values as $key => $value) {
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
