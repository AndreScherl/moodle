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
 * Upgrade steps for the Prüfungsarchiv module.
 *
 * @package   mod_pmediathek
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

function xmldb_pmediathek_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2013120500) {

        // Define field display to be added to pmediathek
        $table = new xmldb_table('pmediathek');
        $field = new xmldb_field('display', XMLDB_TYPE_INTEGER, '4', null, null, null, '6', 'timemodified');

        // Conditionally launch add field display
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        rebuild_course_cache(0, true); // To make sure the popup javascript is added.

        // url savepoint reached
        upgrade_mod_savepoint(true, 2013120500, 'pmediathek');
    }

    return true;
}