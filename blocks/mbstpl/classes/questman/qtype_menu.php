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

class qtype_menu extends qtype_base {
    public static function extend_form(\MoodleQuickForm $form, $islocked = false) {

        $form->addElement('textarea', 'param1', get_string('profilemenuoptions', 'admin'), array('rows' => 6, 'cols' => 40));
        $form->setType('param1', PARAM_TEXT);

        // Default data.
        $form->addElement('text', 'defaultdata', get_string('profiledefaultdata', 'admin'), 'size="50"');
        $form->setType('defaultdata', PARAM_TEXT);

        if ($islocked) {
            $form->freeze('param1');
        }
    }

    public function extend_validation($data, $files) {
        $err = array();

        $data->param1 = str_replace("\r", '', $data->param1);

        // Check that we have at least 2 options.
        if (($options = explode("\n", $data->param1)) === false) {
            $err['param1'] = get_string('profilemenunooptions', 'admin');
        } else if (count($options) < 2) {
            $err['param1'] = get_string('profilemenutoofewoptions', 'admin');
        } else if (!empty($data->defaultdata) and !in_array($data->defaultdata, $options)) {
            // Check the default data exists in the options.
            $err['defaultdata'] = get_string('profilemenudefaultnotinoptions', 'admin');
        }
        return $err;
    }

    public static function add_template_element(\MoodleQuickForm $form, $question, $isfrozen = false) {
        if (isset($question->param1)) {
            $rawoptions = explode("\n", $question->param1);
        } else {
            $rawoptions = array();
        }
        $rawoptions = array_merge(array('' => get_string('choose').'...'), $rawoptions);
        $options = array();
        foreach ($rawoptions as $key => $option) {
            $options[$key] = format_string($option); // Multilang formatting.
        }

        $form->addElement('select', $question->fieldname, $question->title, $options);
    }

    public static function add_to_searchform(\MoodleQuickForm $form, $question, $elname) {
        $values = explode("\n", $question->param1);
        $boxes = array();
        for ($i = 0; $i < count($values); $i++) {
            $boxes[] =& $form->createElement('checkbox', $i, null, $values[$i]);
        }
        $form->addGroup($boxes, $elname, $question->title, \html_writer::empty_tag('br'));
    }

    public static function get_query_filters($question, $answer) {
        global $DB;

        $toreturn = array('joins' => array(), 'params' => array());
        if (empty($answer)) {
            return $toreturn;
        }
        $checkids = array_keys($answer);

        $apfx = 'a' . $question->id . '_';
        $qparam = 'q' . $question->id;
        list($dkwin, $dkwparams) = $DB->get_in_or_equal($checkids, SQL_PARAMS_NAMED, $apfx);
        $toreturn['params'] = $dkwparams;
        $toreturn['params'][$qparam] = $question->id;
        $toreturn['joins'][] = self::get_join("AND $qparam.datakeyword $dkwin", $qparam);

        return $toreturn;
    }
}