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
 * Clear the cached lists of available search parameters
 *
 * @package   repository_mediathek
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
global $PAGE, $CFG, $OUTPUT;
require_once($CFG->dirroot.'/repository/mediathek/mediathekapi.php');

$url = new moodle_url('/repository/mediathek/clearcache.php');
$PAGE->set_url($url);
require_login();
if (!is_siteadmin()) {
    die('Admin only');
}
$PAGE->set_context($context = context_system::instance());

$api = new repository_mediathek_api();
$api->clear_list_cache();

echo $OUTPUT->header();
echo $OUTPUT->box(get_string('cachecleared', 'repository_mediathek'));
echo $OUTPUT->footer();