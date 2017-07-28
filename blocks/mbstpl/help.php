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

require_once(dirname(__FILE__) . '/../../config.php');

$PAGE->set_url('/help.php');
$PAGE->set_pagelayout('popup');
$PAGE->set_context(context_system::instance());

$id = required_param('qid', PARAM_INT);

$question = $DB->get_record('block_mbstpl_question', array('id' => $id), '*', MUST_EXIST);

$data = new stdClass();
$data->heading = get_string('questionhelppopupheading', 'block_mbstpl', $question->title);
$data->text = $question->help;

if (!empty($data->heading)) {
    $PAGE->set_title($data->heading);
} else {
    $PAGE->set_title(get_string('help'));
}
echo $OUTPUT->header();
if (!empty($data->heading)) {
    echo $OUTPUT->heading($data->heading, 1, 'helpheading');
}
echo $data->text;
if (isset($data->completedoclink)) {
    echo $data->completedoclink;
}
echo $OUTPUT->footer();
