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
 * Block mbschangetheme
 *
 * @package    block_mbschangetheme
 * @copyright  Andreas Wagner <andreas.wagner@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');

require_sesskey();
require_login();

$theme = required_param('theme', PARAM_TEXT);
$redirect = required_param('redirect', PARAM_URL);

// Check, whether theme exists.
$themes = get_list_of_themes();

if (!empty($theme) and !in_array($theme, array_keys($themes))) {

    redirect($redirect, get_string('unknowntheme', 'block_mbschangetheme'));
}

$USER->theme = $theme;
$DB->set_field('user', 'theme', $theme, array('id' => $USER->id));
redirect($redirect);