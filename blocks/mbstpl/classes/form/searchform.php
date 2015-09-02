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

/**
 * Class activatedraft
 *
 * @package block_mbstpl
 *          Course Template search and filter form
 *
 */
class searchform extends \moodleform {

    public function definition() {
        $form = $this->_form;

        $qidlist = \block_mbstpl\questman\manager::get_active_qform();
        $questions = \block_mbstpl\questman\manager::get_questsions_in_order($qidlist->questions);

        $form->addElement('hidden', 'layout', 'grid');
        $form->setType('layout', PARAM_TEXT);

        foreach ($questions as $question) {
            $typeclass = \block_mbstpl\questman\qtype_base::qtype_factory($question->datatype);
            $elname = 'q' . $question->id;
            $typeclass->add_to_searchform($form, $question, $elname);
        }

        $form->addElement('text', 'keyword', 'Search Field');
        $form->setType('keyword', PARAM_TEXT);

        $form->addElement('submit', 'submitbutton', get_string('search'));
    }
}