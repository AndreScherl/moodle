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
 * Report orphaned courses (style and js customisations using html - block)
 * report form.
 *
 * @package    report
 * @subpackage mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagern@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_mbs\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/formslib.php');

class reportcourses_form extends \moodleform {

    protected function definition() {

        $mform = $this->_form;

        $mform->addElement('text', 'coursename', get_string('coursename', 'report_mbs'));
        $mform->setType('coursename', PARAM_TEXT);

        $mform->addElement('text', 'maxparticipantscount', get_string('maxparticipantscount', 'report_mbs'));
        $mform->setType('maxparticipantscount', PARAM_ALPHANUM);

        $mform->addElement('text', 'maxtrainerscount', get_string('maxtrainerscount', 'report_mbs'));
        $mform->setType('maxtrainerscount', PARAM_ALPHANUM);

        $mform->addElement('text', 'maxmodulescount', get_string('maxmodulescount', 'report_mbs'));
        $mform->setType('maxmodulescount', PARAM_ALPHANUM);

        $choices = array(0 => get_string('select'));
        $choices[3600 * 24] = get_string('oneday', 'report_mbs');
        $choices[3600 * 24 * 7] = get_string('oneweek', 'report_mbs');
        $choices[3600 * 24 * 30] = get_string('onemonth', 'report_mbs');
        $choices[3600 * 24 * 180] = get_string('halfyear', 'report_mbs');
        $choices[3600 * 24 * 360] = get_string('oneyear', 'report_mbs');

        $mform->addElement('select', 'lastviewedbefore', get_string('lastviewedbefore', 'report_mbs'), $choices);
        $mform->setType('lastviewedbefore', PARAM_INT);

        $mform->addElement('select', 'lastmodifiedbefore', get_string('lastmodifiedbefore', 'report_mbs'), $choices);
        $mform->setType('lastmodifiedbefore', PARAM_INT);

        $mform->addElement('checkbox', 'showdetails', get_string('showdetails', 'report_mbs'));

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'search', get_string('search', 'report_mbs'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function get_request_data() {

        $filterdata = new \stdClass();

        $filterdata->coursename = optional_param('coursename', '', PARAM_TEXT);
        $filterdata->maxparticipantscount = optional_param('maxparticipantscount', '', PARAM_TEXT);
        $filterdata->maxtrainerscount = optional_param('maxtrainerscount', '', PARAM_TEXT);
        $filterdata->maxmodulescount = optional_param('maxmodulescount', '', PARAM_TEXT);
        $filterdata->lastviewedbefore = optional_param('lastviewedbefore', 0, PARAM_INT);
        $filterdata->lastmodifiedbefore = optional_param('lastmodifiedbefore', 0, PARAM_INT);
        $filterdata->showdetails = optional_param('showdetails', 0, PARAM_INT);

        return $filterdata;
    }

    public function get_url_params($data) {

        $params = array();

        if (!empty($data->coursename)) {
            $params['coursename'] = $data->coursename;
        }

        if (isset($data->maxparticipantscount) and ( $data->maxparticipantscount !== '')) {
            $params['maxparticipantscount'] = $data->maxparticipantscount;
        }

        if (isset($data->maxtrainerscount) and ( $data->maxtrainerscount !== '')) {
            $params['maxtrainerscount'] = $data->maxtrainerscount;
        }

        if (isset($data->maxmodulescount) and ( $data->maxmodulescount !== '')) {
            $params['maxmodulescount'] = $data->maxmodulescount;
        }

        if (!empty($data->lastviewedbefore)) {
            $params['lastviewedbefore'] = $data->lastviewedbefore;
        }

        if (!empty($data->lastmodifiedbefore)) {
            $params['lastmodifiedbefore'] = $data->lastmodifiedbefore;
        }

        if (!empty($data->showdetails)) {
            $params['showdetails'] = $data->showdetails;
        }

        return $params;
    }

}
