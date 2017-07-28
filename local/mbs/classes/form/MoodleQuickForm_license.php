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
 * @package   local_mbs
 * @copyright 2015 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Would like to namespace this class, but that just doesn't work with MoodleQuickForm.

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/form/select.php');

class MoodleQuickForm_license extends MoodleQuickForm_select {

    const NEWLICENSE_PARAM = '__createnewlicense__';

    private $_withnew;

    public function __construct($elementName = null, $elementLabel = null,
                                     $attributes = null, $withnew = false) {
        HTML_QuickForm_element::__construct($elementName, $elementLabel, $attributes);
        $this->_type = 'license';
        $this->_persistantFreeze = true;
        $this->_withnew = $withnew;
    }
    
    /*
     * Old syntax of class constructor. Deprecated in PHP7.
     */
    public function MoodleQuickForm_license($elementName = null, $elementLabel = null,
                                     $attributes = null, $withnew = false) {
        self::__construct($elementName, $elementLabel, $attributes, $withnew);
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
        global $USER;
        if ($withnew) {
            $choices[self::NEWLICENSE_PARAM] = get_string('newlicense', 'local_mbs');
            $licenseobjects = \local_mbs\local\licensemanager::get_licenses(array('userid' => $USER->id, 'enabled' => 1));
            foreach ($licenseobjects as $license) {
                $licenses[$license->shortname] = $license->fullname;
            }
            $choices = array_merge($licenses, $choices);
        } else {
            $lobjects = \block_mbstpl\course::get_course_licenses();
            foreach ($lobjects as $license) {
                $licenses[$license->shortname] = $license->fullname;
            }
            $choices = $licenses;
        }
        return $choices;
    }

    function getFrozenHtml() {
        if (!empty($this->_values[0])) {
            $licenseobject = \local_mbs\local\licensemanager::get_license_by_shortname($this->_values[0]);
        }
        if (!empty($licenseobject)) {
            $licencestring = html_writer::link($licenseobject->source, $licenseobject->fullname);
        } else {
            $licencestring = parent::getFrozenHtml();
        }
        return html_writer::div($licencestring, $this->getAttribute('class'));
    }

}

MoodleQuickForm::registerElementType('license', __FILE__, 'MoodleQuickForm_license');
