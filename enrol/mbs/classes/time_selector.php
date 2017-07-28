<?php

namespace enrol_mbs;

defined('MOODLE_INTERNAL') || die();

class time_selector extends \MoodleQuickForm_group {

    function time_selector($elementName = null, $elementLabel = null, $attributes = null) {
        $this->HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_appendName = true;
        $this->_type = 'time_selector';
    }

    function _createElements() {

        $hours = array();
        $minutes = array();

        for ($i = 0; $i <= 23; $i++) {
            $hours[$i] = sprintf("%02d", $i);
        }
        for ($i = 0; $i < 60; $i += 15) {
            $minutes[$i] = sprintf("%02d", $i);
        }

        $this->_elements = array();
        $this->_elements[] = @\MoodleQuickForm::createElement('select', 'hour', get_string('hour', 'form'), $hours, $this->getAttributes(), true);
        $this->_elements[] = @\MoodleQuickForm::createElement('select', 'minute', get_string('minute', 'form'), $minutes, $this->getAttributes(), true);

        foreach ($this->_elements as $element){
            $element->setHiddenLabel(true);
        }
    }

}
