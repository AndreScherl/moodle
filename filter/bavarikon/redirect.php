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
 * Use moodle as a proxy, this is a attempt to use moodle as a proxy to
 * avoid ame policy restriction for javascript, but it seems that the
 * bavarikon site is too complex to use this technique.
 *
 * @package    filter
 * @subpackage bavarikon
 * @copyright  2017 Andreas Wagner, ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
require_once(dirname(__FILE__) . '/../../config.php');

require_once($CFG->dirroot . '/lib/filelib.php');

$url = required_param('url', PARAM_URL);

$curl = new curl();
$response =  $curl->get($url);

$response = str_replace('/css', 'https://bavarikon.de/css', $response);
$response = str_replace('/js', 'https://bavarikon.de/js', $response);
echo $response;