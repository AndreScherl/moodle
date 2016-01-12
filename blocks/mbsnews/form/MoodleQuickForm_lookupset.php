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
 * Lookup search element builiding a dynamical list
 *
 * @package   block_mbsnews
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Would like to namespace this class, but that just doesn't work with MoodleQuickForm.

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/form/text.php');

class MoodleQuickForm_lookupset extends MoodleQuickForm_text {

    private $_selectedkey;
    private $_choices = array();
    private $_ajaxurl;
    private $_ajaxparamnames;

    function MoodleQuickForm_lookupset($elementName = null,
                                       $elementLabel = null, $ajaxurl = '', $ajaxparamnames = array(), 
                                       $choices = array(), $attributes = null) {

        MoodleQuickForm_text::MoodleQuickForm_text($elementName, $elementLabel, $attributes);
        $this->_type = 'lookupset';
        $this->_selectedkey = $this->getName() . 'selected';
        $this->_ajaxparamnames = $ajaxparamnames;
    }

    /**
     * Find values of choices in given values. Values can be default values,
     * constant values or submitted values.
     * 
     * @param array $values
     * @return array
     */
    function _findChoices(&$values) {
        
        if (empty($values)) {
            return null;
        }
        
        $elementName = $this->getName() . 'selected';
        if (isset($values[$elementName])) {
            return $values[$elementName];
        } 
    }

    function onQuickFormEvent($event, $arg, &$caller) {

        switch ($event) {
            case 'addElement':
                if (!empty($arg[4])) {
                    $this->_choices = $arg[4];
                }

                if (empty($arg[2])) {
                    self::raiseError();
                }

                $this->_ajaxurl = $arg[2];
                break;

            case 'updateValue':
                // constant values override both default and submitted ones
                // default values are overriden by submitted
                $value = $this->_findChoices($caller->_constantValues);
                if (null === $value) {
                    $value = $this->_findChoices($caller->_submitValues);
                    if (null === $value) {
                        $value = $this->_findChoices($caller->_defaultValues);
                    }
                }
                if (null !== $value) {
                    $this->_choices = $value;
                }
                break;
        }

        return parent::onQuickFormEvent($event, $arg, $caller);
    }

    function accept(&$renderer, $required = false, $error = null) {
        parent::accept($renderer, $required, $error);

        global $PAGE;

        $args = array();
        $args['url'] = $this->_ajaxurl->out();
        $args['lookupcount'] = 10;
        $args['name'] = $this->getName();
        $args['ajaxparamnames'] = $this->_ajaxparamnames;
        $args['nameselected'] = $args['name'] . 'selected';
        $PAGE->requires->yui_module('moodle-block_mbsnews-lookupset', 'M.block_mbsnews.lookupset.init', array($args), null, true);
        $PAGE->requires->strings_for_js(array('delete'), 'moodle');
    }

    /**
     * Returns HTML for this form element.
     *
     * @return string
     */
    function toHtml() {
        global $OUTPUT;

        $html = $this->_getTabs();

        $li = '';

        foreach ($this->_choices as $key => $value) {

            $hiddenfield = html_writer::empty_tag('input', array('type' => 'hidden', 'value' => $value, 'name' => $this->_selectedkey . "[$key]"));

            $deletepix = $OUTPUT->pix_icon('t/delete', get_string('delete'));

            $deletebutton = html_writer::tag('span', $deletepix, array('class' => 'flookupset-delete'));

            $li .= html_writer::tag('li', $value . ' ' . $deletebutton . $hiddenfield, array('value' => $value));
        }

        $html .= html_writer::tag('ul', $li, array('id' => 'id_' . $this->getName() . '_list'));
        $html .= '<input' . $this->_getAttrString($this->_attributes) . ' style="display:none" />';
        $html .= html_writer::tag('div', '', array('id' => 'id_' . $this->getName() . '_list_hidden', 'class' => 'hidden'));
        $html .= html_writer::empty_tag('input', array('id' => 'id_' . $this->getName() . '_search', 'type' => 'text'));

        return $html;
    }
    
    function exportValue(&$submitValues, $assoc = false) {
        $value = (isset($submitValues[$this->_selectedkey])) ? $submitValues[$this->_selectedkey] : null;
        return $this->_prepareValue($value, $assoc);
    }

}

MoodleQuickForm::registerElementType('lookupset', __FILE__, 'MoodleQuickForm_lookupset');
