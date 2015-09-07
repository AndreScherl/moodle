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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package block_mbstpl
 * @copyright 2015 Bence Laky <b.laky@intrallect.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);

use \block_mbstpl\autocomplete;
use \block_mbstpl\dataobj\template;

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');

require_login();
require_sesskey();

$autocomplete = new \block_mbstpl\autocomplete();

$keyword = required_param("keyword", PARAM_TEXT);

$suggestions = $autocomplete->get_suggestions($keyword);

header('Content-Type: application/json; charset=utf-8');

// Reset keys.
echo json_encode($suggestions);