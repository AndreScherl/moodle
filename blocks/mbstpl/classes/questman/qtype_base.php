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
     * @param \MoodleQuickForm $form
     * @param object $question
     */
    public static function add_template_element(\MoodleQuickForm $form, $question) {

    }

    /**
     * Save the answer.
     * @param $metaid
     * @param $questionid
     * @param $data
     * @param $dataformat
     * @return bool;
     */
    public static function save_answer($metaid, $questionid, $answer, $comment = null, $dataformat = FORMAT_MOODLE) {
        if (is_null($answer)) {
            $answer = '';
        }

        $answerdata = array(
            'metaid' => $metaid,
            'questionid' => $questionid,
            'data' => $answer,
            'dataformat' => $dataformat,
        );
        if ($comment !== null) {
            $answerdata['comment'] = trim($comment);
        }

        $answerobj = new \block_mbstpl\dataobj\answer($answerdata);
        $answerobj->insertorupdate();
        return true;
    }

    /*
     * Gets answer according to type (by default the data, for some fields an array)
     * @param object $answer
     */
    public static function process_answer($answer) {
        return $answer->data;
    }

    /**
     * Add the appropriate element to search this field.
     * @param \MoodleQuickForm $form
     * @param $question
     * @param string $elname the element name.
     */
    public static function add_to_searchform(\MoodleQuickForm $form, $question, $elname) {

    }

    /**
     * Returns an array of arrays with possibly 'wheres', 'joins' and 'params'.
     * @param object $question
     * @param mixed $answer
     */
    public static function get_query_filters($question, $answer) {
        return array();
    }

    /*
     * Used to easily build a JOIN clause that filters on the answer question id and adds any extra filter.
     * @param string $extra extra filter.
     * @param string $alias param wildcard corresponding to the question id as well as join alias.
     * @return string
     */
    protected static function get_join($extra = '', $alias = '') {
        return "JOIN {block_mbstpl_answer} $alias ON $alias.metaid = mta.id AND $alias.questionid = :$alias $extra";
    }

    /**
     * Call the definition_after_data_internal functions for each of the question types.
     *
     * @param \MoodleQuickForm $form
     */
    public static function definition_after_data(\MoodleQuickForm $form) {
        $dtypes = manager::allowed_datatypes();
        foreach ($dtypes as $dtype) {
            $typeclass = self::qtype_factory($dtype);
            $typeclass::definition_after_data_internal($form);
        }
    }

    protected static function definition_after_data_internal(\MoodleQuickForm $form) {
        // Does nothing in the base class.
    }
}