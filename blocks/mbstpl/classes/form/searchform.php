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
 *
 * @package block_mbstpl
 * @copyright 2015 Bence Laky <b.laky@intrallect.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace block_mbstpl\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/mbs/classes/form/MoodleQuickForm_lookupset.php');

/**
 * Class activatedraft
 *
 * @package block_mbstpl
 *          Course Template search and filter form
 *
 */
class searchform extends \moodleform {

    public function definition() {
        global $PAGE;

        $form = $this->_form;

        $questions = $this->_customdata['questions'];

        $form->addElement('hidden', 'layout', 'grid');
        $form->setType('layout', PARAM_TEXT);

        foreach ($questions as $question) {
            $typeclass = \block_mbstpl\questman\qtype_base::qtype_factory($question->datatype);
            $elname = 'q_' . $question->id;
            $typeclass->add_to_searchform($form, $question, $elname);
            if ($question->datatype == 'checkboxgroup') {
                $this->add_checkbox_controller($question->id, null, null, 0);
            }
        }

        $ajaxurl = new \moodle_url('/blocks/mbstpl/lookupset_ajax.php', array('action' => 'searchtags'));
        $form->addElement('lookupset', 'tag', get_string('tag', 'block_mbstpl'), $ajaxurl, array());

        $ajaxurl = new \moodle_url('/blocks/mbstpl/lookupset_ajax.php', array('action' => 'searchauthor'));
        $form->addElement('lookupset', 'author', get_string('author', 'block_mbstpl'), $ajaxurl, array());

        $ajaxurl = new \moodle_url('/blocks/mbstpl/lookupset_ajax.php', array('action' => 'searchcoursename'));
        $form->addElement('lookupset', 'coursename', get_string('coursename', 'block_mbstpl'), $ajaxurl, array());

        // Sorting.
        $asc = ': ' . get_string('asc');
        $desc = ': ' . get_string('desc');
        $strrating = get_string('rating', 'block_mbstpl');
        $options = array(
            'asc_rating' => $strrating . $asc,
            'desc_rating' => $strrating . $desc,
        );
        foreach ($questions as $question) {
            $options['asc_' . $question->id] = $question->name . $asc;
            $options['desc_' . $question->id] = $question->name . $desc;
        }
        $form->addElement('select', 'sortby', get_string('sort'), $options);
        $form->setDefault('sortby', 'desc_rating');

        $form->addElement('submit', 'submitbutton', get_string('search'));
    }
}
