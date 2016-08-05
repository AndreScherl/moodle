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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>..

/**
 * Form to search for tasks.
 *
 * @package block_mbstpl
 * @copyright 2016 Andreas Wagner, ISB Bayern
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace block_mbstpl\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

class tasksearchform extends \moodleform {

    public function definition() {

        $form = $this->_form;

        $choices = array(0 => get_string('select'));
        $choices += \block_mbstpl\course::get_statuses_menu();

        $form->addElement('header', 'headerfilter', get_string('advancedsearch', 'block_mbstpl'));

        $form->addElement('select', 'status', get_string('status', 'block_mbstpl'), $choices);

        $choices = array(
            0 => get_string('select'),
            1 => get_string('yes'),
            2 => get_string('no')
        );

        $form->addElement('select', 'userdata', get_string('userdataincluded', 'block_mbstpl'), $choices);
        $form->addElement('date_selector', 'fromdate', get_string('nextruntimefrom', 'block_mbstpl'), array('optional' => 1));
        $form->addElement('date_selector', 'todate', get_string('nextruntimeto', 'block_mbstpl'), array('optional' => 1));

        $form->addElement('submit', 'submitbutton', get_string('search'));
    }

    public function get_url_params($filterdata) {

        $params = array();
        if (!empty($filterdata->status)) {
            $params['status'] = $filterdata->status;
        }

         if (!empty($filterdata->userdata)) {
            $params['userdata'] = $filterdata->userdata;
        }

        return $params;
    }

    public function get_request_data() {

        $filterdata = new \stdClass();
        $filterdata->status = optional_param('status', 0, PARAM_INT);
        $filterdata->userdata = optional_param('userdata', 0, PARAM_INT);

        return $filterdata;
    }

}
