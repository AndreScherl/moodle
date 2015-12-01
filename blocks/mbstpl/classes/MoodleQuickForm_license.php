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
 * Drop-down select with a list of available licenses
 *
 * @package   block_mbstpl
 * @copyright 2015 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Would like to namespace this class, but that just doesn't work with MoodleQuickForm.

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/form/select.php');

class MoodleQuickForm_license extends MoodleQuickForm_select {

    const NEWLICENSE_PARAM = '__createnewlicense__';

    private static $courselicenses = null;
    private static $assetlicenses = null;
    private $_withnew;

    /**
     * Get all the licenses to fill the asset dropdown box.
     * 
     * Note that the asset licenses may ONLY include
     * licenses with licensetype 0 (= default) and 1 (=course)
     * 
     * Usercreated licenses must be loaded seperately (see set_data() of the form).
     * 
     * @return array list of available licenses
     */
    private static function get_asset_licenses() {
        global $DB;

        if (self::$assetlicenses === null) {

            self::$assetlicenses = array();

            $select = " type != :type";
            $params = array('type' => \block_mbstpl\dataobj\license::$licensetype['usercreated']);

            $licenses = $DB->get_records_select('block_mbstpl_license', $select, $params);

            foreach ($licenses as $license) {
                self::$assetlicenses[$license->shortname] = $license->fullname;
            }
        }
        return self::$assetlicenses;
    }

    /**
     * Get all the licenses to fill the course license dropdown.
     * 
     * Note that this are the licenses with licensetype 1 (=course)
     * 
     * @return array list of available licenses
     */
    private static function get_course_licenses() {
        global $DB;

        if (self::$courselicenses === null) {

            self::$courselicenses = array();

            $select = " type = :type";
            $params = array('type' => \block_mbstpl\dataobj\license::$licensetype['course']);

            $licenses = $DB->get_records_select('block_mbstpl_license', $select, $params);

            foreach ($licenses as $license) {
                self::$courselicenses[$license->shortname] = $license->fullname;
            }
        }
        return self::$courselicenses;
    }

    function MoodleQuickForm_license($elementName = null, $elementLabel = null,
                                     $attributes = null, $withnew = false) {
        HTML_QuickForm_element::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->_type = 'license';
        $this->_persistantFreeze = true;
        $this->_withnew = $withnew;
    }

    function onQuickFormEvent($event, $arg, &$caller) {
        global $CFG;

        switch ($event) {
            case 'createElement':
                $choices = $this->get_choices($this->_withnew || !empty($arg[3]));
                $this->load($choices);
                $this->setSelected($CFG->sitedefaultlicense);
                break;
        }

        return parent::onQuickFormEvent($event, $arg, $caller);
    }

    private function get_choices($withnew) {

        if ($withnew) {
            $choices[self::NEWLICENSE_PARAM] = get_string('newlicense', 'block_mbstpl');
            $choices = $choices + self::get_asset_licenses();
        } else {
            $choices = self::get_course_licenses();
        }
        return $choices;
    }

    function getFrozenHtml() {
        $html = parent::getFrozenHtml();
        return html_writer::div($html, $this->getAttribute('class'));
    }

}

MoodleQuickForm::registerElementType('license', __FILE__, 'MoodleQuickForm_license');
