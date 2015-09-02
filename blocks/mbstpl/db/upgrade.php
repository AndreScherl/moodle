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
 * @copyright 2015 Janek Lasocki-Biczysko, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Handles upgrading instances of this block.
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_mbstpl_upgrade($oldversion, $block) {

    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2015082800) {

        // Define table block_mbstpl_starrating to be created.
        $table = new xmldb_table(\block_mbstpl\dataobj\starrating::get_tablename());

        // Adding fields to table block_mbstpl_starrating.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('templateid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
        $table->add_field('rating', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
        $table->add_field('comment', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);

        // Adding keys to table block_mbstpl_starrating.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('unique_user_template', XMLDB_KEY_UNIQUE, array('templateid', 'userid'));

        // Launch create table for block_mbstpl_starrating.
        $dbman->create_table($table);
    }

    if ($oldversion < 2015090200) {

        // Define table block_mbstpl_coursefromtpl to be created.
        $table = new xmldb_table('block_mbstpl_coursefromtpl');

        // Adding fields to table block_mbstpl_coursefromtpl.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('templateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_mbstpl_coursefromtpl.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table block_mbstpl_coursefromtpl.
        $table->add_index('courseid', XMLDB_INDEX_UNIQUE, array('courseid'));
        $table->add_index('templateid', XMLDB_INDEX_NOTUNIQUE, array('templateid'));

        // Conditionally launch create table for block_mbstpl_coursefromtpl.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015090200, 'mbstpl');
    }


    return true;
}
