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
 * Load help text, that is stored in database.
 * 
 * @package block_mbstpl
 * @copyright 2015 Andreas Wagner, ISB
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

$PAGE->set_url('/help_ajax.php');
$PAGE->set_context(context_system::instance());

$id = required_param('qid', PARAM_INT);

$question = $DB->get_record('block_mbstpl_question', array('id' => $id), '*', MUST_EXIST);

$data = new stdClass();
$data->heading = get_string('questionhelppopupheading', 'block_mbstpl', $question->title);
$data->text = $question->help;

echo json_encode($data);
