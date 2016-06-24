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
 * Note that there is a javascript lookupset.js belonging to this class.
 *
 * @package   local_mbs
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

    /**
     * Create a from element with lookup search.
     * 
     * @param string $elementname name of the element
     * @param string $elementlabel label of the element
     * @param string $ajaxurl the url to call when user is inputting at least 3 characters
     * @param array $ajaxparamids the id of the formelements which values should be send as parameters of the url.
     * @param array $choices an array with key => value pairs that should appear as default.
     * @param array $attributes html attributes of the field
     */
    public function __construct($elementname = null,
                                  $elementlabel = null,
                                  $ajaxurl = '',
                                  $ajaxparamids = array(),
                                  $choices = array(),
                                  $attributes = null) {

        MoodleQuickForm_text::__construct($elementname, $elementlabel, $attributes);
        $this->_type = 'lookupset';
        $this->_selectedkey = $this->getName() . 'selected';
        $this->_ajaxparamnames = $ajaxparamids;
    }
    
    /*
     * Old syntax of class constructor. Deprecated in PHP7.
     */
    public function MoodleQuickForm_lookupset($elementname = null,
                                              $elementlabel = null,
                                              $ajaxurl = '',
                                              $ajaxparamids = array(),
                                              $choices = array(),
                                              $attributes = null) {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::_construct($elementname, $elementlabel, $ajaxurl, $ajaxparamids, $choices, $attributes);
    }

    /**
     * Find values of choices in given values. Values can be default values,
     * constant values or submitted values.
     * 
     * @param array $values
     * @return array
     */
    protected function _findChoices(&$values) {

        if (empty($values)) {
            return null;
        }

        $elementname = $this->getName() . 'selected';

        if (isset($values[$elementname])) {
            return $values[$elementname];
        }
    }

    public function onQuickFormEvent($event, $arg, &$caller) {

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
                // Constant values override both default and submitted ones.
                // Eefault values are overriden by submitted.
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

    public function accept(&$renderer, $required = false, $error = null) {
        parent::accept($renderer, $required, $error);

        global $PAGE;

        $args = array();
        $args['url'] = $this->_ajaxurl->out();
        $args['lookupcount'] = 10;
        $args['name'] = $this->getName();
        $args['ajaxparamnames'] = $this->_ajaxparamnames;
        $args['nameselected'] = $args['name'] . 'selected';
        $PAGE->requires->yui_module('moodle-local_mbs-lookupset', 'M.local_mbs.lookupset.init', array($args), null, true);
        $PAGE->requires->strings_for_js(array('delete'), 'moodle');
        $PAGE->requires->strings_for_js(array('lookupsetmoreresults', 'lookupsetnoresults', 'lookupsetlessletters'), 'local_mbs');
    }

    /**
     * Sets the value of the form element
     *
     * @param     string    $value      Default value of the form element
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setValue($value) {

        if (is_array($value)) {

            $this->_choices = $value;

            if (empty($value)) {
                $value = 0;
            } else {
                $value = 1;
            }
        }
        $this->updateAttributes(array('value' => $value));
    }

    /**
     * Returns HTML for this form element.
     *
     * @return string
     */
    public function toHtml() {
        global $OUTPUT;

        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }

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

    public function exportValue(&$submitValues, $assoc = false) {
        $value = (isset($submitValues[$this->_selectedkey])) ? $submitValues[$this->_selectedkey] : null;
        return $this->_prepareValue($value, $assoc);
    }

    /**
     * Returns the html to be used when the element is frozen
     *
     * @since     Moodle 2.4
     * @return    string Frozen html
     */
    function getFrozenHtml() {

        $html = $this->_getTabs();

        if (empty($this->_choices)) {
            $html .= '<input' . $this->_getAttrString($this->_attributes) . ' disabled="disabled" />';
            return $html;
        }

        $li = '';
        foreach ($this->_choices as $key => $value) {

            $li .= html_writer::tag('li', $value, array('value' => $value));
        }

        $html .= html_writer::tag('ul', $li, array('id' => 'id_' . $this->getName() . '_list'));

        return $html;
    }

//end func getFrozenHtml
}

MoodleQuickForm::registerElementType('lookupset', __FILE__, 'MoodleQuickForm_lookupset');
