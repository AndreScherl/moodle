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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

class licenseandassetform extends \moodleform {

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
                $asset->url = $url;
                $asset->license = $license;
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
        $submittedlicense = self::get_submitted_license_shortname($data);
        if ($meta->license != $submittedlicense) {
            $meta->license = $submittedlicense;
            $meta->update();
        }
    }

    /**
     * Returns the submitted license shortname. If a new license was submitted,
     * will create a new license and return that license's shortname.
     *
     * @param object $data
     * @return string
     */
    public static function get_submitted_license_shortname($data) {

        if ($data->license == \MoodleQuickForm_license::NEWLICENSE_PARAM) {

            $license = new license(array(
                'shortname' => $data->newlicense_shortname,
                'fullname' => $data->newlicense_fullname,
                'source' => $data->newlicense_source
            ));
            $license->insert();

            return $license->shortname;
        }

        return $data->license;
    }

    function definition() {

        global $CFG;
        require_once($CFG->dirroot.'/blocks/mbstpl/classes/MoodleQuickForm_license.php');
        require_once($CFG->dirroot.'/blocks/mbstpl/classes/MoodleQuickForm_newlicense.php');

        $form = $this->_form;

        // License options.
        $form->addElement('license', 'license', get_string('license', 'block_mbstpl'), null, true);

        /* @var $newlicense \MoodleQuickForm_newlicense */
        $newlicense = $form->addElement('newlicense', 'newlicense', 'license');

        if (optional_param('license', null, PARAM_ALPHAEXT) != \MoodleQuickForm_license::NEWLICENSE_PARAM) {
            foreach ($newlicense->getElements() as $newlicenseelement) {
                /* @var $newlicenseelement \MoodleQuickForm_text */
                $newlicenseelement->updateAttributes(array('disabled' => 'disabled'));
            }
        } else {
            $form->addGroupRule('newlicense', array(
                'newlicense_shortname' => array(array(get_string('newlicense_required', 'block_mbstpl'), 'required'))
            ), 'required');
        }

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
        $asset[] = $form->createElement('text', 'asset_source', get_string('source', 'block_mbstpl'),
            array('size' => '20', 'placeholder' => get_string('source', 'block_mbstpl')));
        $assetgroup = $form->createElement('group', 'asset', get_string('assets', 'block_mbstpl'), $asset, null, false);

        $repeatcount = isset($this->_customdata['assetcount']) ? $this->_customdata['assetcount'] : 1;
        $repeatcount += 2;
        $repeatopts = array(
            'asset_id' => array('type' => PARAM_INT),
            'asset_url' => array('type' => PARAM_URL),
            'asset_owner' => array('type' => PARAM_TEXT),
            'asset_source' => array('type' => PARAM_TEXT)
        );
        $this->repeat_elements(array($assetgroup), $repeatcount, $repeatopts, 'assets', 'assets_add', 3,
            get_string('addassets', 'block_mbstpl'));

    }

    function validation($data, $files) {

        // If a new license is being created, ensure that one doesn't exists with the same shortname.
        if ($data['license'] == \MoodleQuickForm_license::NEWLICENSE_PARAM) {
            $shortname = $data['newlicense_shortname'];
            $existinglicense = license::fetch(array('shortname' => $shortname));
            if ($existinglicense) {
                return array('newlicense' => get_string('newlicense_exists', 'block_mbstpl', $shortname));
            }
        }

        return array();
    }

}
