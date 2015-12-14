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
 * Form for selecting activities that should include user data.
 *
 * @package   block_mbstpl
 * @copyright 2015 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\form;

use cm_info;
use coding_exception;
use html_writer;
use moodle_exception;
use moodleform;
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

class sendtemplate_activities extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        $courseid = $this->_customdata['courseid'];
        $form1data = $this->_customdata['form1data'];

        $mform->addElement('hidden', 'course', $courseid);
        $mform->setType('course', PARAM_INT);
        self::validate_first_form_data($form1data);
        $mform->addElement('hidden', 'form1data', $form1data);
        $mform->setType('form1data', PARAM_ALPHANUM);

        $this->add_activities_list();

        $this->add_action_buttons(true, get_string('sendforreviewing', 'block_mbstpl'));
    }

    private function add_activities_list() {
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];

        $modinfo = get_fast_modinfo($courseid);
        $format = course_get_format($modinfo->get_course());

        // Heading at the start.
        $mform->addElement('header', 'coursesettings', get_string('includeactivities', 'backup'));

        foreach ($modinfo->get_sections() as $sectionnum => $cmids) {
            $mform->addElement('html', html_writer::start_div('grouped_settings section_level'));
            $mform->addElement('html', html_writer::tag('h3', $format->get_section_name($sectionnum)));
            $first = true;
            foreach ($cmids as $cmid) {
                $mform->addElement('html', html_writer::start_div('grouped_settings activity_level'));
                $this->add_activity_settings($mform, $modinfo->get_cm($cmid), $first);
                $mform->addElement('html', html_writer::end_div());
                $first = false;
            }
            $mform->addElement('html', html_writer::end_div());
        }
    }

    private function add_activity_settings(MoodleQuickForm $mform, cm_info $cm, $first) {
        // Add the activity name.
        $activitytitle = $cm->get_formatted_name().'&nbsp;'.html_writer::empty_tag('img', array('src' => $cm->get_icon_url()));
        $mform->addElement('html', html_writer::start_div('include_setting activity_level'));
        $mform->addElement('static', 'title'.$cm->id, $activitytitle, '&nbsp;');
        $mform->addElement('html', html_writer::end_div());

        // Add the userdata element.
        $mform->addElement('html', html_writer::start_div('normal_setting'));
        $mform->addElement('advcheckbox', 'userdata-'.$cm->id, get_string('includeuserinfo', 'backup'));
        $mform->addElement('html', html_writer::end_div());
        $mform->setDefault('userdata-'.$cm->id, 1);

        // Add the deploy userdata element.
        $mform->addElement('html', html_writer::start_div('normal_setting'));
        $mform->addElement('advcheckbox', 'deploydata-'.$cm->id, get_string('deployuserinfo', 'block_mbstpl'));
        $mform->addElement('html', html_writer::end_div());
        $mform->setDefault('deploydata-'.$cm->id, 1);
        $mform->disabledIf('deploydata-'.$cm->id, 'userdata-'.$cm->id, 'notchecked');
    }

    public function display() {
        // Wrap the form in a 'path-backup' div, in order to get the appropriate styling.
        echo html_writer::start_div('path-backup');
        parent::display();
        echo html_writer::end_div();
    }

    public static function get_userdata_ids($data) {
        $ret = array();
        foreach ($data as $itemname => $itemvalue) {
            $parts = explode('-', $itemname, 2);
            if (count($parts) < 2 || $parts[0] != 'userdata') {
                continue;
            }
            if (!$itemvalue) {
                continue;
            }
            $ret[] = intval($parts[1]);
        }
        return $ret;
    }

    public static function get_exclude_deploydata_ids($data) {
        $ret = array();
        foreach ($data as $itemname => $itemvalue) {
            $parts = explode('-', $itemname, 2);
            if (count($parts) < 2 || $parts[0] != 'deploydata') {
                continue;
            }
            $userdatafield = 'userdata-'.$parts[1];
            if (empty($data->{$userdatafield})) {
                $ret[] = intval($parts[1]); // No userdata => always prevent deployment data.
            } else if (!$itemvalue) {
                $ret[] = intval($parts[1]); // Deloyment setting not selected => prevent deployment data.
            }
        }
        return $ret;
    }

    // -----------------------------------------------------
    // Store/retrieve the data from the first template form
    // -----------------------------------------------------

    public static function save_first_form_data($data) {
        global $SESSION;

        if (!$data) {
            throw new coding_exception('Must specify valid data to store');
        }

        $hash = sha1(serialize($data));

        if (!isset($SESSION->block_mbstpl_template_form)) {
            $SESSION->block_mbstpl_template_form = array();
        }
        $SESSION->block_mbstpl_template_form[$hash] = $data;
        return $hash;
    }

    private static function validate_first_form_data($hash) {
        global $SESSION;

        if (!isset($SESSION->block_mbstpl_template_form[$hash])) {
            throw new moodle_exception('missingtemplatedata', 'block_mbstpl');
        }
    }

    public static function retrieve_first_form_data($hash) {
        global $SESSION;

        self::validate_first_form_data($hash);

        $ret = $SESSION->block_mbstpl_template_form[$hash];
        return $ret;
    }

    public static function clear_first_form_data($hash) {
        global $SESSION;
        unset($SESSION->block_mbstpl_template_form[$hash]);
    }
}
