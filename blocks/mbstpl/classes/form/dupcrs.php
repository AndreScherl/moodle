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
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\form;

use \block_mbstpl as mbst;
use backup;
use backup_controller;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class dupcrs
 * @package block_mbstpl
 * Create template to course duplication task request.
 */

class dupcrs extends \moodleform {

    function display() {

        global $PAGE, $COURSE;

        // Get list of module types on course.
        $modinfo = get_fast_modinfo($COURSE);
        $modnames = $modinfo->get_used_module_names(true);
        $PAGE->requires->yui_module('moodle-backup-backupselectall', 'M.core_backup.backupselectall',
            array($modnames));

        $PAGE->requires->strings_for_js(array('select', 'all', 'none'), 'moodle');
        $PAGE->requires->strings_for_js(array('showtypes', 'hidetypes'), 'backup');

        parent::display();
    }

    protected function definition() {
        $form = $this->_form;

        $course = $this->_customdata['course'];

        $form->addElement('hidden', 'course', $course->id);
        $form->setType('course', PARAM_INT);

        $readyforstep2 = optional_param('restoreto', false, PARAM_ALPHA)
            && (optional_param("tocat", false, PARAM_INT) || optional_param("tocrs", false, PARAM_INT));

        if ($this->_customdata['step'] == 2 && $readyforstep2) {
            $this->definition_step2();
        } else {
            $this->definition_step1();
        }
    }

    private function definition_step1() {

        $form = $this->_form;

        $form->addElement('hidden', 'step', 2);
        $form->setType('step', PARAM_INT);
        $disabled = array('disabled' => 'disabled');

        if (!empty($this->_customdata['cats'])) {
            $restoreto = $form->addElement('radio', 'restoreto', get_string('restoretonewcourse', 'backup'), '', 'cat');
            $options = array();
            foreach ($this->_customdata['cats'] as $cat) {
                $options[$cat->id] = $cat->name;
            }
            $selectcat = $form->addElement('select', 'tocat', get_string('selectacategory', 'backup'), $options, $disabled);
            $form->disabledIf('tocat', 'restoreto', 'neq', 'cat');
            $this->enable_on_click($restoreto, $selectcat);
        }
        if (!empty($this->_customdata['courses'])) {
            $restoreto = $form->addElement('radio', 'restoreto', get_string('restoretoexistingcourse', 'backup'), '', 'course');
            $options = array();
            foreach ($this->_customdata['courses'] as $crs) {
                $options[$crs->id] = $crs->fullname;
            }
            $selectcourse = $form->addElement('select', 'tocrs', get_string('selectacourse', 'backup'), $options, $disabled);
            $form->disabledIf('tocrs', 'restoreto', 'neq', 'course');
            $this->enable_on_click($restoreto, $selectcourse);
        }
        $form->addRule('restoreto', get_string('required'), 'required', null, 'client');

        $form->addElement('textarea', 'licence', get_string('duplcourselicense', 'block_mbstpl'), array('cols' => 70, 'rows' => 3))->freeze();
        $form->addRule('licence', get_string('required'), 'required', null, 'client');

        $this->set_data(array(
            'licence' => get_string('duplcourselicensedefault', 'block_mbstpl', $this->_customdata['creator'])
        ));

        $this->add_action_buttons(true, get_string('duplcourseforuse1', 'block_mbstpl'));
    }

    private function definition_step2() {

        global $USER, $CFG;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        $form = $this->_form;
        $courseid = $this->_customdata['course']->id;

        $form->addElement('hidden', 'step', 2);
        $form->addElement('hidden', 'doduplicate', 1);
        $form->addElement('hidden', 'licence', optional_param('licence', '', PARAM_TEXT));

        $restoreto = required_param('restoreto', PARAM_ALPHA);
        $form->addElement('hidden', 'restoreto', $restoreto);

        $destparam = $restoreto == 'course' ? 'tocrs' : 'tocat';
        $form->addElement('hidden', $destparam, required_param($destparam, PARAM_INT));

        $form->setTypes(array(
            'step' => PARAM_INT,
            'doduplicate' => PARAM_INT,
            'licence' => PARAM_TEXT,
            'restoreto' => PARAM_ALPHA,
            $destparam => PARAM_INT
        ));

        $form->addElement('static', 'message', '', get_string('selectsectionsandactivities', 'block_mbstpl'));

        $bc = new backup_controller(backup::TYPE_1COURSE, $courseid, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO, backup::MODE_AUTOMATED, $USER->id);
        $builder = new restoreformbuilder($form, $bc->get_plan()->get_tasks());
        $builder->prepare_section_elements();

        $this->add_action_buttons(true, get_string('duplcourseforuse2', 'block_mbstpl'));
    }

    private function enable_on_click($eltoclick, $eltoenable) {
        global $PAGE;

        $eltoclick->_generateId();
        $eltoenable->_generateId();
        $idtoclick = $eltoclick->getAttribute('id');
        $idtoenable = $eltoenable->getAttribute('id');

        $PAGE->requires->js_init_code("$('#$idtoclick').click(function() { $('#$idtoenable').removeAttr('disabled'); });", true);
    }

    public function get_task_settings() {

        $data = $this->get_data();

        $settings = array();
        $backupsettings = array();

        if ($data->restoreto == 'cat') {
            $settings['tocat'] = $data->tocat;
        } else {
            $settings['tocrs'] = $data->tocrs;
        }

        foreach ($data as $key => $value) {
            $matches = array();
            preg_match('/^setting_[a-z]+_(.+)$/', $key, $matches);
            if (isset($matches[1])) {
                $backupsettings[$matches[1]] = $value;
            }
        }

        $settings['backupsettings'] = $backupsettings;

        $settings['licence'] = $data->licence;

        return $settings;
    }
}