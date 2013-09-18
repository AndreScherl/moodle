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
 * Display the search form + results for Prüfungsarchiv Mediathek plugin
 *
 * @package   repository_pmediathek
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require_once(dirname(__FILE__).'/../../config.php');
global $PAGE, $OUTPUT, $CFG;
require_once($CFG->dirroot.'/repository/pmediathek/locallib.php');

$contextid = required_param('contextid', PARAM_INT);
$returntypes = required_param('returntypes', PARAM_INT);
$context = context::instance_by_id($contextid);

$url = new moodle_url('/repository/pmediathek/search.php', array('contextid' => $context->id, 'returntypes' => $returntypes));
$PAGE->set_url($url);

require_login();
$PAGE->set_context($context);
require_capability('repository/pmediathek:view', $context);

$title = get_string('pluginname', 'repository_pmediathek');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('embedded');

$search = new repository_pmediathek_search($context, $returntypes);
$search->process();

echo $OUTPUT->header();
echo $search->output();
echo $OUTPUT->footer();