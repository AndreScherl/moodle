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
 * Test the mediathek API
 *
 * @package   repository_mediathek
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
global $CFG;
require_once($CFG->dirroot.'/repository/mediathek/mediathekapi.php');

require_login();

if (!is_siteadmin()) {
    die('Admin only');
}

$api = new repository_mediathek_api();

$modes = array_keys($api->get_search_mode_list());
$mode = reset($modes);
$sortparams = array_keys($api->get_sort_criteria_list());
$sortparam = reset($sortparams);
$sortorders = array_keys($api->get_sort_order_list());
$sortorder = reset($sortorders);

var_dump($api->search_content($mode, 10, 1, $sortparam, $sortorder, 'test'));

var_dump($api->get_search_mode_list());
var_dump($api->get_topic_list());
var_dump($api->get_level_list());
var_dump($api->get_type_list());
var_dump($api->get_sort_criteria_list());
var_dump($api->get_sort_order_list());
var_dump($api->get_restriction_list());
var_dump($api->get_record_element_list());
var_dump($api->get_error_list());
var_dump($api->get_tag_list());

