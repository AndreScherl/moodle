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
use block_mbstpl\dataobj\asset;
use block_mbstpl\dataobj\license;
use block_mbstpl\user;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/mbs/classes/form/MoodleQuickForm_license.php');
require_once($CFG->dirroot . '/local/mbs/classes/form/MoodleQuickForm_newlicense.php');

abstract class licenseandassetform extends \moodleform {

    const ASSET_SPACES = 3;
    protected $licenseindex = 4;

    /**
     * Returns the submitted license shortname. If a new license was submitted,
     * will create a new license and return that license's shortname.
     *
     * @param object $data
     * @return string
     */
    protected static function get_submitted_license_shortname($data, $idx) {

        if ($data->asset_license[$idx] == \MoodleQuickForm_license::NEWLICENSE_PARAM) {

            $license = new license(array(
                'shortname' => $data->newlicense_shortname[$idx],
                'fullname' => $data->newlicense_fullname[$idx],
                'source' => $data->newlicense_source[$idx],
                'type' => \block_mbstpl\dataobj\license::$licensetype['usercreated']
            ));
            $license->insert();

            return $license->shortname;
        }

        return $data->asset_license[$idx];
    }

    public static function update_assets_from_submitted_data(meta $meta, $data) {
        global $CFG;

        foreach ($data->asset_id as $idx => $assetid) {

            $url = isset($data->asset_url[$idx]) ? trim($data->asset_url[$idx]) : '';
            $license = isset($data->asset_license[$idx]) ? $data->asset_license[$idx] : $CFG->defaultsitelicense;
            $owner = isset($data->asset_owner[$idx]) ? trim($data->asset_owner[$idx]) : '';
            $source = isset($data->asset_source[$idx]) ? trim($data->asset_source[$idx]) : '';
            $hasdata = $url || $owner || $source;

            if ($assetid) {
                $asset = new asset(array('id' => $assetid, 'metaid' => $meta->id), true, MUST_EXIST);
                if (!$hasdata) {
                    $asset->delete();
                }
            } else {
                $asset = new asset(array('metaid' => $meta->id), false);
            }
            if ($hasdata) {
                // Create new (user-) license, if necessary.
                $submittedlicense = self::get_submitted_license_shortname($data, $idx);

                $asset->url = $url;
                $asset->license = $submittedlicense;
                $asset->owner = $owner;
                $asset->source = $source;
                if ($asset->id) {
                    $asset->update();
                } else {
                    $asset->insert();
                }
            }
        }
    }

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

    protected function define_assets() {

        $form = $this->_form;
        $cdata = $this->_customdata;

        // List of 3rd-party assets.
        $asset = array();
        $asset[0] = $form->createElement('hidden', 'asset_id');
        $asset[1] = $form->createElement('text', 'asset_url', get_string('url', 'block_mbstpl'), array(
            'size' => '30', 'inputmode' => 'url',
            'placeholder' => get_string('url', 'block_mbstpl')
        ));
        $asset[2] = $form->createElement('text', 'asset_owner', get_string('owner', 'block_mbstpl'), array('size' => '20', 'placeholder' => get_string('owner', 'block_mbstpl')));
        $asset[3] = $form->createElement('text', 'asset_source', get_string('source', 'block_mbstpl'), array('size' => '20', 'placeholder' => get_string('source', 'block_mbstpl')));

        // When changing the order of the asset elements, it is necessary to change the licenseindex too.
        $asset[4] = $form->createElement('license', 'asset_license', get_string('license', 'block_mbstpl'), array('class' => 'mbstpl-asset-license'), true);
        // This index is used to update the license dropdown, when a usercreated license was saved
        // Example: this is set to 4, because the license field has this index in $asset array.
        $this->licenseindex = 4;

        if (empty($cdata['freeze'])) {

            /* @var $newlicense \MoodleQuickForm_newlicense */
            $asset[] = $form->createElement('text', 'newlicense_shortname', '', array('placeholder' => get_string('newlicense_shortname', 'local_mbs')));
            $asset[] = $form->createElement('text', 'newlicense_fullname', '', array('placeholder' => get_string('newlicense_fullname', 'local_mbs')));
            $asset[] = $form->createElement('text', 'newlicense_source', '', array('placeholder' => get_string('newlicense_source', 'local_mbs')));

            $form->setTypes(array(
                'newlicense_shortname' => PARAM_TEXT,
                'newlicense_fullname' => PARAM_TEXT,
                'newlicense_source' => PARAM_URL
            ));
        }

        $assetgroup = $form->createElement('group', 'asset', get_string('assets', 'block_mbstpl'), $asset, null, false);

        $repeatcount = empty($this->_customdata['assetcount']) ? self::ASSET_SPACES : $this->_customdata['assetcount'];
        if (empty($this->_customdata['freeze']) && ($extraslots = $repeatcount % self::ASSET_SPACES)) {
            $repeatcount += self::ASSET_SPACES - $extraslots;
        }

        $repeatopts = array(
            'asset_id' => array('type' => PARAM_INT),
            'asset_url' => array('type' => PARAM_URL),
            'asset_owner' => array('type' => PARAM_TEXT),
            'asset_source' => array('type' => PARAM_TEXT)
        );
        $this->repeat_elements(array($assetgroup), $repeatcount, $repeatopts, 'assets', 'assets_add', 3, get_string('addassets', 'block_mbstpl'), true);
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

        // Assets.
        $this->define_assets();

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

    function validation($data, $files) {

        // If a new license is being created, ensure that one doesn't exists with the same shortname.

        if (!empty($data['asset_license'])) {

            foreach ($data['asset_license'] as $idx => $licenseshortname) {

                if ($licenseshortname == \MoodleQuickForm_license::NEWLICENSE_PARAM) {

                    $shortname = $data['newlicense_shortname'][$idx];
                    $existinglicense = license::fetch(array('shortname' => $shortname));

                    if ($existinglicense) {
                        return array("asset[$idx]" => get_string('newlicense_exists', 'local_mbs', $shortname));
                    }

                    if (empty($data['newlicense_fullname'][$idx])) {
                        return array("asset[$idx]" => get_string('newlicense_fullnamerequired', 'local_mbs', $shortname));
                    }
                }
            }

            return array();
        }
    }

}
