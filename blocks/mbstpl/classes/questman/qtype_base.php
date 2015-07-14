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

namespace block_mbstpl\questman;

defined('MOODLE_INTERNAL') || die();

class qtype_base {

    /**
     * @param $datatype
     * @return \block_mbstpl\questman\qtype_base
     * @throws \moodle_exception
     */
    public static function qtype_factory($datatype) {
        $alloweds = manager::allowed_datatypes();
        if (!in_array($datatype, $alloweds)) {
            throw new \moodle_exception('errorincorrectdatatype', 'block_mbstpl');
        }

        $typeclass = '\block_mbstpl\questman\qtype_' . $datatype;
        return new $typeclass();
    }

    /**
     * Add question type-specific fields to the form.
     * @param $form
     * @param bool $islocked
     */
    public static function extend_form(\MoodleQuickForm $form, $islocked = false) {

    }

    /**
     * Extend the validation.
     * @param $data
     * @param $files
     */
    public function extend_validation($data, $files) {
        return array();
    }

    /**
     * If the type has text editor fields, let them be known.
     * @return array
     */
    public static function get_editors() {
        return array();
    }

    /**
     * Add an element of the relevant type to the template form.
     * @param MoodleQuickForm $form
     * @param object $question
     */
    public static function add_template_element(\MoodleQuickForm &$form, $question) {

    }

    /**
     * Save the answer.
     * @param $backupid
     * @param $questionid
     * @param $data
     * @param $dataformat
     */
    public static function save_answer($backupid, $questionid, $answer, $dataformat = FORMAT_MOODLE) {
        global $DB;

        if (is_null($answer)) {
            $answer = '';
        }

        $obj = (object)array(
            'backupid' => $backupid,
            'questionid' => $questionid,
            'data' => $answer,
            'dataformat' => $dataformat,
        );
        return $DB->insert_record('block_mbstpl_answer', $obj);
    }
}