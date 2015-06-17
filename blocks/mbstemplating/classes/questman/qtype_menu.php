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
 * @package block
 * @subpackage mbstemplating
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstemplating\questman;

defined('MOODLE_INTERNAL') || die();

class qtype_menu extends qtype_base {
    public static function extend_form(&$form, $islocked = false) {

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
}