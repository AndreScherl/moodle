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
 * @package   local_mbslicenseinfo
 * @copyright 2015, ISB Bayern
 * @author    Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_mbslicenseinfo_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016022903) {

        // Define key files_id (foreign) to be added to local_mbslicenseinfo_fmeta.
        // Define key files_id (foreign) to be dropped form local_mbslicenseinfo_fmeta.
        $table = new xmldb_table('local_mbslicenseinfo_fmeta');
        $key = new xmldb_key('files_id', XMLDB_KEY_FOREIGN_UNIQUE, array('files_id'), 'files', array('id'));

        // Launch drop key files_id.
        $dbman->drop_key($table, $key);

        $key = new xmldb_key('files_id', XMLDB_KEY_FOREIGN, array('files_id'), 'files', array('id'));

        // Launch add key files_id.
        $dbman->add_key($table, $key);

        // Mbslicenseinfo savepoint reached.
        upgrade_plugin_savepoint(true, 2016022903, 'error', 'mbslicenseinfo');
    }

    if ($oldversion < 2016030101) {

        $table = new xmldb_table('files');
        $index = new xmldb_index('chash-ctx-mime', XMLDB_INDEX_NOTUNIQUE, array('contenthash', 'contextid', 'mimetype'));

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2016030101, 'error', 'mbslicenseinfo');
    }

    if ($oldversion < 2016042600) {

        // Changing type of field source on table local_mbslicenseinfo_fmeta to text.
        $table = new xmldb_table('local_mbslicenseinfo_fmeta');
        $field = new xmldb_field('source', XMLDB_TYPE_TEXT, null, null, null, null, null, 'title');

        // Launch change of type for field source.
        $dbman->change_field_type($table, $field);

        // Mbslicenseinfo savepoint reached.
        upgrade_plugin_savepoint(true, 2016042600, 'error', 'mbslicenseinfo');
    }


    return true;
}
