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
 * Form to edit the title (i. e. the logo and the name of a school)
 *
 * @package   block_mbsschooltitle
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/lib/formslib.php');

class edittitle_form extends moodleform {

    function definition() {
        global $CFG;

        $mform = $this->_form;
        $categoryid = $this->_customdata['categoryid'];
        $titledata = $this->_customdata['titledata'];

        $mform->addElement('header', 'headersettings', get_string('settings', 'block_mbsschooltitle'));

        $mform->addElement('text', 'headline', get_string('headline', 'block_mbsschooltitle'), array('size' => '70'));
        if (isset($titledata->headline)) {
            $mform->setDefault('headline', $titledata->headline);
        }
        $mform->setType('headline', PARAM_TEXT);

        $file = false;
        if (!empty($titledata->image)) {
            $file = \block_mbsschooltitle\local\imagehelper::get_imagefile($categoryid, $titledata->image);
        }
        
        $currentpicture = $this->render_currentimage($categoryid, $file);
        $mform->addElement('static', 'currentpicture', get_string('currentpicture'), $currentpicture);

        if ($file) {
            $mform->addElement('checkbox', 'deletepicture', get_string('delete'));
            $mform->setDefault('deletepicture', 0);
        }

        $mform->addElement('filepicker', 'imagefile', get_string('newpicture'), '', array('maxbytes' => get_max_upload_file_size($CFG->maxbytes)));

        $mform->addElement('hidden', 'categoryid', $categoryid);
        $mform->setType('categoryid', PARAM_INT);
        
        // Buttons.
        $this->add_action_buttons(true);
    }

    protected function render_currentimage($categoryid, $file) {

        if (!$file) {
            return get_string('none');
        }
        
        $imgurl = \block_mbsschooltitle\local\imagehelper::get_imageurl($categoryid, $file->get_filename());
        
        $alt = get_string('imagepreview', 'block_mbsschooltitle');
        $attributes = array('src' => $imgurl.'?'.time(), 'alt' => $alt, 'title' => $alt);
        
        return html_writer::empty_tag('img', $attributes);
        
    }
}