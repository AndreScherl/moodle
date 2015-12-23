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
 * @package   block_mbslicenseinfo
 * @copyright 2015, ISB Bayern
 * @author    Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_block_mbslicenseinfo_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();
    
      if ($oldversion < 2015120400) {

        // Define table block_mbslicenseinfo_fmeta to be created.
        $table = new xmldb_table('block_mbslicenseinfo_fmeta');
        // Adding fields to table block_mbslicenseinfo_fmeta.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('source', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('files_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        // Adding keys to table block_mbslicenseinfo_fmeta.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('files_id', XMLDB_KEY_FOREIGN_UNIQUE, array('files_id'), 'files', array('id'));
        // Conditionally launch create table for block_mbslicenseinfo_fmeta.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Define table block_mbslicenseinfo_ul to be created.
        $table = new xmldb_table('block_mbslicenseinfo_ul');
        // Adding fields to table block_mbslicenseinfo_ul.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '13', null, null, null, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('source', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        // Adding keys to table block_mbslicenseinfo_ul.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN_UNIQUE, array('userid'), 'user', array('id'));
        // Conditionally launch create table for block_mbslicenseinfo_ul.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Mbslicenseinfo savepoint reached.
        upgrade_block_savepoint(true, 2015120400, 'mbslicenseinfo');
    }
    
    if ($oldversion < 2015121800) {
        
        // Change foreign key from foreign-unique to foreign (first drop then add)
        // Define key userid (foreign) to be dropped form block_mbslicenseinfo_ul.
        $table = new xmldb_table('block_mbslicenseinfo_ul');
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN_UNIQUE, array('userid'), 'user', array('id'));
        // Launch drop key userid.
        $dbman->drop_key($table, $key);
        
        // Define key userid (foreign) to be added to block_mbslicenseinfo_ul.
        $table = new xmldb_table('block_mbslicenseinfo_ul');
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        // Launch add key userid.
        $dbman->add_key($table, $key);

        // Mbslicenseinfo savepoint reached.
        upgrade_block_savepoint(true, 2015121800, 'mbslicenseinfo');
    }
    
     if ($oldversion < 2015122300) {

        // Changing type of field source on table block_mbslicenseinfo_fmeta to text.
        $table = new xmldb_table('block_mbslicenseinfo_fmeta');
        $field = new xmldb_field('source', XMLDB_TYPE_TEXT, null, null, null, null, null, 'title');

        // Launch change of type for field source.
        $dbman->change_field_type($table, $field);

        // Mbslicenseinfo savepoint reached.
        upgrade_block_savepoint(true, 2015122300, 'mbslicenseinfo');
    }
    
    return true;
}