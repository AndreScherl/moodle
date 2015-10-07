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

use backup_setting,
    backup_root_task,
    base_task,
    html_writer;

defined('MOODLE_INTERNAL') || die();

/*
 * // --------- lifted from backup/util/ui/backup_moodleform.class.php
 * // --------- with a few minor modifications
 */
class restoreformbuilder {

    /**
     * True if we have a course div open, false otherwise
     * @var bool
     */
    protected $coursediv = false;
    /**
     * True if we have a section div open, false otherwise
     * @var bool
     */
    protected $sectiondiv = false;
    /**
     * True if we have an activity div open, false otherwise
     * @var bool
     */
    protected $activitydiv = false;

    private $_form;
    private $tasks;

    public function __construct(\MoodleQuickForm $mform, $tasks) {
        $this->_form = $mform;
        $this->tasks = $tasks;
    }

    public function prepare_section_elements() {

        $addsettings = array();
        $dependencies = array();
        $courseheading = false;

        foreach ($this->tasks as $task) {
            if (!($task instanceof backup_root_task)) {
                if (!$courseheading) {
                    // If we havn't already display a course heading to group nicely.
                    $this->add_heading('coursesettings', get_string('includeactivities', 'backup'));
                    $courseheading = true;
                }
                // First add each setting.
                foreach ($task->get_settings() as $setting) {
                    $setting->set_value(1);
                    $addsettings[] = array($setting, $task);
                }
                // The add all the dependencies.
                foreach ($task->get_settings() as $setting) {
                    $setting->set_value(1);
                    $dependencies[] = $setting;
                }
            }
        }

        $this->add_settings($addsettings);

        foreach ($dependencies as $depsetting) {
            $this->add_dependencies($depsetting);
        }
    }


    /**
     * Adds a heading to the form
     * @param string $name
     * @param string $text
     */
    private function add_heading($name , $text) {
        $this->_form->addElement('header', $name, $text);
    }

    private function add_setting(backup_setting $setting, base_task $task=null) {
        return $this->add_settings(array(array($setting, $task)));
    }
    /**
     * Adds multiple backup_settings as elements to the form
     * @param array $settingstasks Consists of array($setting, $task) elements
     * @return bool
     */
    private function add_settings(array $settingstasks) {
        global $OUTPUT;

        $defaults = array();
        foreach ($settingstasks as $st) {
            list($setting, $task) = $st;
            // If the setting cant be changed or isn't visible then add it as a fixed setting.
            if (!$setting->get_ui()->is_changeable() || $setting->get_visibility() != backup_setting::VISIBLE) {
                $this->add_fixed_setting($setting, $task);
                continue;
            }

            // First add the formatting for this setting.
            $this->add_html_formatting($setting);

            // Then call the add method with the get_element_properties array.
            call_user_func_array(array($this->_form, 'addElement'), $setting->get_ui()->get_element_properties($task, $OUTPUT));
            $this->_form->setType($setting->get_ui_name(), $setting->get_param_validation());
            $defaults[$setting->get_ui_name()] = $setting->get_value();
            if ($setting->has_help()) {
                list($identifier, $component) = $setting->get_help();
                $this->_form->addHelpButton($setting->get_ui_name(), $identifier, $component);
            }
            $this->_form->addElement('html', html_writer::end_tag('div'));
        }
        $this->_form->setDefaults($defaults);
        return true;
    }

    private function add_dependencies(backup_setting $setting) {
        $mform = $this->_form;
        // Apply all dependencies for backup.
        foreach ($setting->get_my_dependency_properties() as $key => $dependency) {
            call_user_func_array(array($this->_form, 'disabledIf'), $dependency);
        }
    }

    /**
     * Adds HTML formatting for the given backup setting, needed to group/segment
     * correctly.
     * @param backup_setting $setting
     */
    private function add_html_formatting(backup_setting $setting) {
        $mform = $this->_form;
        $isincludesetting = (strpos($setting->get_name(), '_include') !== false);
        if ($isincludesetting && $setting->get_level() != backup_setting::ROOT_LEVEL) {
            switch ($setting->get_level()) {
                case backup_setting::COURSE_LEVEL:
                    if ($this->activitydiv) {
                        $this->_form->addElement('html', html_writer::end_tag('div'));
                        $this->activitydiv = false;
                    }
                    if ($this->sectiondiv) {
                        $this->_form->addElement('html', html_writer::end_tag('div'));
                        $this->sectiondiv = false;
                    }
                    if ($this->coursediv) {
                        $this->_form->addElement('html', html_writer::end_tag('div'));
                    }
                    $mform->addElement('html', html_writer::start_tag('div', array('class' => 'grouped_settings course_level')));
                    $mform->addElement('html', html_writer::start_tag('div', array('class' => 'include_setting course_level')));
                    $this->coursediv = true;
                    break;
                case backup_setting::SECTION_LEVEL:
                    if ($this->activitydiv) {
                        $this->_form->addElement('html', html_writer::end_tag('div'));
                        $this->activitydiv = false;
                    }
                    if ($this->sectiondiv) {
                        $this->_form->addElement('html', html_writer::end_tag('div'));
                    }
                    $mform->addElement('html', html_writer::start_tag('div', array('class' => 'grouped_settings section_level')));
                    $mform->addElement('html', html_writer::start_tag('div', array('class' => 'include_setting section_level')));
                    $this->sectiondiv = true;
                    break;
                case backup_setting::ACTIVITY_LEVEL:
                    if ($this->activitydiv) {
                        $this->_form->addElement('html', html_writer::end_tag('div'));
                    }
                    $mform->addElement('html', html_writer::start_tag('div', array('class' => 'grouped_settings activity_level')));
                    $mform->addElement('html', html_writer::start_tag('div', array('class' => 'include_setting activity_level')));
                    $this->activitydiv = true;
                    break;
                default:
                    $mform->addElement('html', html_writer::start_tag('div', array('class' => 'normal_setting')));
                    break;
            }
        } else if ($setting->get_level() == backup_setting::ROOT_LEVEL) {
            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'root_setting')));
        } else {
            $mform->addElement('html', html_writer::start_tag('div', array('class' => 'normal_setting')));
        }
    }

    private function add_fixed_setting(backup_setting $setting, base_task $task) {
        global $OUTPUT;
        $settingui = $setting->get_ui();
        if ($setting->get_visibility() == backup_setting::VISIBLE) {
            $this->add_html_formatting($setting);
            switch ($setting->get_status()) {
                case backup_setting::LOCKED_BY_PERMISSION:
                    $icon = ' '.$OUTPUT->pix_icon('i/permissionlock', get_string('lockedbypermission', 'backup'), 'moodle',
                        array('class' => 'smallicon lockedicon permissionlock'));
                    break;
                case backup_setting::LOCKED_BY_CONFIG:
                    $icon = ' '.$OUTPUT->pix_icon('i/configlock', get_string('lockedbyconfig', 'backup'), 'moodle',
                        array('class' => 'smallicon lockedicon configlock'));
                    break;
                case backup_setting::LOCKED_BY_HIERARCHY:
                    $icon = ' '.$OUTPUT->pix_icon('i/hierarchylock', get_string('lockedbyhierarchy', 'backup'), 'moodle',
                        array('class' => 'smallicon lockedicon configlock'));
                    break;
                default:
                    $icon = '';
                    break;
            }
            $label = $settingui->get_label($task);
            $labelicon = $settingui->get_icon();
            if (!empty($labelicon)) {
                $label .= '&nbsp;'.$OUTPUT->render($labelicon);
            }
            $this->_form->addElement('static', 'static_'.$settingui->get_name(), $label, $settingui->get_static_value().$icon);
            $this->_form->addElement('html', html_writer::end_tag('div'));
        }
        $this->_form->addElement('hidden', $settingui->get_name(), $settingui->get_value());
        $this->_form->setType($settingui->get_name(), $settingui->get_param_validation());
    }
}
