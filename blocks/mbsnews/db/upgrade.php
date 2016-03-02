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
 * Upgrade Script for block_mbsnews
 *
 * @package   block_mbsnews
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_block_mbsnews_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016012200) {

        // Define table block_mbsnews_job to be dropped.
        $table = new xmldb_table('block_mbsnews_job_processed');

        // Conditionally launch drop table for block_mbsnews_job.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table block_mbsnews_message to be created.
        $table = new xmldb_table('block_mbsnews_message');

        // Adding fields to table block_mbsnews_message.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('jobid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usertoid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timefirstviewed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_mbsnews_message.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table block_mbsnews_message.
        $table->add_index('idx_jobid', XMLDB_INDEX_NOTUNIQUE, array('jobid'));
        $table->add_index('idx_usrid', XMLDB_INDEX_NOTUNIQUE, array('usertoid'));
        $table->add_index('idx_jobid_usr', XMLDB_INDEX_NOTUNIQUE, array('jobid', 'usertoid'));

        // Conditionally launch create table for block_mbsnews_message.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Mbsnews savepoint reached.
        upgrade_block_savepoint(true, 2016012200, 'mbsnews');
    }

    if ($oldversion < 2016030100) {

        // Define field timeconfirmed to be added to block_mbsnews_message.
        $table = new xmldb_table('block_mbsnews_message');
        $field = new xmldb_field('timeconfirmed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timefirstviewed');

        // Conditionally launch add field timeconfirmed.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mbsnews savepoint reached.
        upgrade_block_savepoint(true, 2016030100, 'mbsnews');
    }
    return true;
}
