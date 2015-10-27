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
 * Overrides the backup render, to exclude existing courses form restore
 * form, where the user is NOT having the capability moode/restore:restorecourse
 * in the context of the course.
 *
 * @package    theme_mebis
 * @copyright  2015 ISB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/backup/util/ui/renderer.php');

class theme_mebis_core_backup_renderer extends core_backup_renderer {
    
    /**
     * Overrides the backup render, to exclude existing courses form restore
     * form, where the user additonal having the capability moode/course:manageactivities
     * in the context of the course.
     * 
     * Important note: This method is mainly taken form the original renderer with one hook 
     * (see comment).
     *
     * @param moodle_url $nextstageurl
     * @param bool $wholecourse true if we are restoring whole course (as with backup::TYPE_1COURSE), false otherwise
     * @param restore_category_search $categories
     * @param restore_course_search $courses
     * @param int $currentcourse
     * @return string
     */
    public function course_selector(moodle_url $nextstageurl,
                                    $wholecourse = true,
                                    restore_category_search $categories = null,
                                    restore_course_search $courses = null,
                                    $currentcourse = null) {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/course/lib.php');

        // These variables are used to check if the form using this function was submitted.
        $target = optional_param('target', false, PARAM_INT);
        $targetid = optional_param('targetid', null, PARAM_INT);

        // Check if they submitted the form but did not provide all the data we need.
        $missingdata = false;
        if ($target and is_null($targetid)) {
            $missingdata = true;
        }

        $nextstageurl->param('sesskey', sesskey());

        $form = html_writer::start_tag('form', array('method' => 'post', 'action' => $nextstageurl->out_omit_querystring(),
                    'class' => 'mform'));
        foreach ($nextstageurl->params() as $key => $value) {
            $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value));
        }

        $hasrestoreoption = false;

        $html = html_writer::start_tag('div', array('class' => 'backup-course-selector backup-restore'));
        if ($wholecourse && !empty($categories) && ($categories->get_count() > 0 || $categories->get_search())) {
            // New course
            $hasrestoreoption = true;
            $html .= $form;
            $html .= html_writer::start_tag('div', array('class' => 'bcs-new-course backup-section'));
            $html .= $this->output->heading(get_string('restoretonewcourse', 'backup'), 2, array('class' => 'header'));
            $html .= $this->backup_detail_input(get_string('restoretonewcourse', 'backup'), 'radio', 'target', backup::TARGET_NEW_COURSE, array('checked' => 'checked'));
            $selectacategoryhtml = $this->backup_detail_pair(get_string('selectacategory', 'backup'), $this->render($categories));
            // Display the category selection as required if the form was submitted but this data was not supplied.
            if ($missingdata && $target == backup::TARGET_NEW_COURSE) {
                $html .= html_writer::span(get_string('required'), 'error');
                $html .= html_writer::start_tag('fieldset', array('class' => 'error'));
                $html .= $selectacategoryhtml;
                $html .= html_writer::end_tag('fieldset');
            } else {
                $html .= $selectacategoryhtml;
            }
            $html .= $this->backup_detail_pair('', html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('continue'))));
            $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('form');
        }

        if ($wholecourse && !empty($currentcourse)) {
            // Current course
            $hasrestoreoption = true;
            $html .= $form;
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'targetid', 'value' => $currentcourse));
            $html .= html_writer::start_tag('div', array('class' => 'bcs-current-course backup-section'));
            $html .= $this->output->heading(get_string('restoretocurrentcourse', 'backup'), 2, array('class' => 'header'));
            $html .= $this->backup_detail_input(get_string('restoretocurrentcourseadding', 'backup'), 'radio', 'target', backup::TARGET_CURRENT_ADDING, array('checked' => 'checked'));
            $html .= $this->backup_detail_input(get_string('restoretocurrentcoursedeleting', 'backup'), 'radio', 'target', backup::TARGET_CURRENT_DELETING);
            $html .= $this->backup_detail_pair('', html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('continue'))));
            $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('form');
        }

        // If we are restoring an activity, then include the current course.
        if (!$wholecourse) {
            $courses->invalidate_results(); // Clean list of courses.
            $courses->set_include_currentcourse();
        }

        // +++ awag: require additional capability.
        $courses->require_capability('moodle/course:manageactivities');
        // --- awag: end of Hook.
        
        if (!empty($courses) && ($courses->get_count() > 0 || $courses->get_search())) {
            // Existing course
            $hasrestoreoption = true;
            $html .= $form;
            $html .= html_writer::start_tag('div', array('class' => 'bcs-existing-course backup-section'));
            $html .= $this->output->heading(get_string('restoretoexistingcourse', 'backup'), 2, array('class' => 'header'));
            if ($wholecourse) {
                $html .= $this->backup_detail_input(get_string('restoretoexistingcourseadding', 'backup'), 'radio', 'target', backup::TARGET_EXISTING_ADDING, array('checked' => 'checked'));
                $html .= $this->backup_detail_input(get_string('restoretoexistingcoursedeleting', 'backup'), 'radio', 'target', backup::TARGET_EXISTING_DELETING);
            } else {
                $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'target', 'value' => backup::TARGET_EXISTING_ADDING));
            }
            
            $selectacoursehtml = $this->backup_detail_pair(get_string('selectacourse', 'backup'), $this->render($courses));
            
            // Display the course selection as required if the form was submitted but this data was not supplied.
            if ($missingdata && $target == backup::TARGET_EXISTING_ADDING) {
                $html .= html_writer::span(get_string('required'), 'error');
                $html .= html_writer::start_tag('fieldset', array('class' => 'error'));
                $html .= $selectacoursehtml;
                $html .= html_writer::end_tag('fieldset');
            } else {
                $html .= $selectacoursehtml;
            }
            $html .= $this->backup_detail_pair('', html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('continue'))));
            $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('form');
        }

        if (!$hasrestoreoption) {
            echo $this->output->notification(get_string('norestoreoptions', 'backup'));
        }

        $html .= html_writer::end_tag('div');
        return $html;
    }

}
