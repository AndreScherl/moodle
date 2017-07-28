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

use block_mbstpl as mbst;
use block_mbstpl\questman\manager;

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $PAGE, $OUTPUT;

require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('blockmbstplmanagesearch');

$enableid = optional_param('enableid', 0, PARAM_INT);
$disableid = optional_param('disableid', 0, PARAM_INT);
$moveupid = optional_param('moveupid', 0, PARAM_INT);
$movedownid = optional_param('movedownid', 0, PARAM_INT);

if ($enableid) {
    require_sesskey();
    manager::searchq_setenabled($enableid);
    redirect($PAGE->url);
}
if ($disableid) {
    require_sesskey();
    manager::searchq_setenabled($disableid, false);
    redirect($PAGE->url);
}
if ($moveupid) {
    require_sesskey();
    manager::searchq_move($moveupid);
    redirect($PAGE->url);
}
if ($movedownid) {
    require_sesskey();
    manager::searchq_move($movedownid, false);
    redirect($PAGE->url);
}

$questions = manager::searchmanage_getall();
$renderer = mbst\course::get_renderer();
echo $OUTPUT->header();

$pagetitle = get_string('managesearch', 'block_mbstpl');

echo html_writer::tag('h2', $pagetitle);

echo $renderer->manage_search($questions, $PAGE->url);

echo $OUTPUT->footer();
