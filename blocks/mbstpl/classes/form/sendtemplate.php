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

require_once($CFG->libdir.'/formslib.php');

/**
 * Class sendtemplate
 * @package block_mbstpl
 * Main question form
 */
class sendtemplate extends \moodleform {
    function definition() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/mbstpl/classes/MoodleQuickForm_license.php');

        $form = $this->_form;

        $form->addElement('hidden', 'course', $this->_customdata['courseid']);
        $form->setType('course', PARAM_INT);

        $form->addElement('text', 'coursename', get_string('coursename', 'block_mbstpl'));
        $form->setType('coursename', PARAM_TEXT);

        $form->addElement('date_time_selector', 'sendtpldate', get_string('sendtpldate', 'block_mbstpl'));

        // Add custom questions.
        $questions = $this->_customdata['questions'];
        foreach ($questions as $question) {
            if ($question->datatype != 'checklist') {
                $typeclass = mbst\questman\qtype_base::qtype_factory($question->datatype);
                $typeclass::add_template_element($form, $question);
            }
        }

        $radioarray = array();
        $radioarray[] = $form->createElement('radio', 'withanon', '', get_string('withanon', 'block_mbstpl'), 1);
        $radioarray[] = $form->createElement('radio', 'withanon', '', get_string('withoutanon', 'block_mbstpl'), 0);
        $form->addGroup($radioarray, 'incluserdata', get_string('incluserdata', 'block_mbstpl'), array(' ', ' '), false);

        $form->addElement('checkbox', 'copyright', get_string('copyright', 'block_mbstpl'));
        $form->addRule('copyright', get_string('required'), 'required');

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
        $creator = user::format_creator_name($this->_customdata['creator']);
        $form->addElement('static', 'creator', get_string('creator', 'block_mbstpl'), $creator);

        // Checklist questions.
        foreach ($questions as $question) {
            if ($question->datatype == 'checklist') {
                $typeclass = mbst\questman\qtype_base::qtype_factory($question->datatype);
                $typeclass::add_template_element($form, $question);
            }
        }

        $this->add_action_buttons(true, get_string('sendforreviewing', 'block_mbstpl'));

        $form->freeze(array('coursename', 'sendtpldate'));
    }

    function definition_after_data() {
        parent::definition_after_data();
        mbst\questman\qtype_base::definition_after_data($this->_form);
    }
}
