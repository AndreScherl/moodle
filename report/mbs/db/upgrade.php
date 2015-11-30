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
 * @package    report_mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagner@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_report_mbs_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2015111101) {

        // Define table report_mbs_tex to be created.
        $table = new xmldb_table('report_mbs_tex');

        // Adding fields to table report_mbs_tex.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('tablename', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table report_mbs_tex.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for report_mbs_tex.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Mbs savepoint reached.
        upgrade_plugin_savepoint(true, 2015111101, 'report', 'mbs');
    }
    
     if ($oldversion < 2015111103) {

        // Changing type of field count on table report_mbs_tex to text.
        $table = new xmldb_table('report_mbs_tex');
        $field = new xmldb_field('count', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'tablename');

        // Launch change of type for field count.
        $dbman->change_field_type($table, $field);

        // Mbs savepoint reached.
        upgrade_plugin_savepoint(true, 2015111103, 'report', 'mbs');
    }


    return true;
}
