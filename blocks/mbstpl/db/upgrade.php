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
defined('MOODLE_INTERNAL') || die();

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

    if ($oldversion < 2015090300) {

        // Define field datakeyword to be added to block_mbstpl_answer.
        $table = new xmldb_table('block_mbstpl_answer');
        $field = new xmldb_field('datakeyword', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'dataformat');

        // Conditionally launch add field datakeyword.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index datakeyword (not unique) to be added to block_mbstpl_answer.
        $index = new xmldb_index('datakeyword', XMLDB_INDEX_NOTUNIQUE, array('datakeyword'));

        // Conditionally launch add index datakeyword.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015090300, 'mbstpl');
    }

    if ($oldversion < 2015090800) {

        // Define field rating to be added to block_mbstpl_template.
        $table = new xmldb_table('block_mbstpl_template');
        $field = new xmldb_field('rating', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null, 'timemodified');

        // Conditionally launch add field rating.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015090800, 'mbstpl');
    }

    if ($oldversion < 2015091600) {

        // Define field license to be added to block_mbstpl_meta.
        $table = new xmldb_table('block_mbstpl_meta');
        $field = new xmldb_field('license', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'templateid');

        // Conditionally launch add field license.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015091600, 'mbstpl');
    }

    if ($oldversion < 2015091601) {

        // Define table block_mbstpl_tag to be created.
        $table = new xmldb_table('block_mbstpl_tag');

        // Adding fields to table block_mbstpl_tag.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('metaid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tag', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_mbstpl_tag.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('metaid', XMLDB_KEY_FOREIGN, array('metaid'), 'block_mbstpl_meta', array('id'));

        // Adding indexes to table block_mbstpl_tag.
        $table->add_index('tag', XMLDB_INDEX_NOTUNIQUE, array('tag'));

        // Conditionally launch create table for block_mbstpl_tag.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015091601, 'mbstpl');
    }

    if ($oldversion < 2015091602) {

        // Define table block_mbstpl_asset to be created.
        $table = new xmldb_table('block_mbstpl_asset');

        // Adding fields to table block_mbstpl_asset.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('metaid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('url', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('license', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('owner', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table block_mbstpl_asset.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('metaid', XMLDB_KEY_FOREIGN, array('metaid'), 'block_mbstpl_meta', array('id'));

        // Conditionally launch create table for block_mbstpl_asset.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015091602, 'mbstpl');
    }

    if ($oldversion < 2015092100) {

        // Define field comment to be added to block_mbstpl_answer.
        $table = new xmldb_table('block_mbstpl_answer');
        $field = new xmldb_field('comment', XMLDB_TYPE_TEXT, null, null, null, null, null, 'datakeyword');

        // Conditionally launch add field comment.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015092100, 'mbstpl');
    }

    if ($oldversion < 2015092902) {

        $table = new xmldb_table(\block_mbstpl\dataobj\coursefromtpl::get_tablename());

        $newfields = array(
            new xmldb_field('createdby', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED),
            new xmldb_field('createdon', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED)
        );

        foreach ($newfields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
    }

    if ($oldversion < 2015092903) {
        $table = new xmldb_table(\block_mbstpl\dataobj\coursefromtpl::get_tablename());
        $field = new xmldb_field('licence', XMLDB_TYPE_TEXT);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2015100500) {

        $table = new xmldb_table('block_mbstpl_asset');
        $field = new xmldb_field('source', XMLDB_TYPE_TEXT);

        // Conditionally launch add field comment.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015100500, 'mbstpl');
    }

    if ($oldversion < 2015100700) {

        $tablename = 'block_mbstpl_license';

        $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fullname', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('source', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $select = "SELECT shortname, fullname, source FROM {license} WHERE enabled = 1";
        $DB->execute("INSERT INTO {{$tablename}} (shortname, fullname, source) $select");

        upgrade_block_savepoint(true, 2015100700, 'mbstpl');
    }

    if ($oldversion < 2015100701) {

        $table = new xmldb_table('block_mbstpl_asset');
        $field = new xmldb_field('source', XMLDB_TYPE_TEXT);

        // Conditionally launch add field comment.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015100701, 'mbstpl');
    }

    if ($oldversion < 2015102000) {

        // Define field reminded to be added to block_mbstpl_template.
        $table = new xmldb_table('block_mbstpl_template');

        // Conditionally launch add field reminded.
        $field = new xmldb_field('reminded', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'rating');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Conditionally launch add index reminded.
        $index = new xmldb_index('reminded', XMLDB_INDEX_NOTUNIQUE, array('reminded'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015102000, 'mbstpl');
    }

    if ($oldversion < 2015110900) {

        // Define fields createdby, createdon, licence to be added to block_mbstpl_coursefromtpl.
        $table = new xmldb_table('block_mbstpl_coursefromtpl');
        $fields = array(
            new xmldb_field('createdby', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'templateid'),
            new xmldb_field('createdon', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'createdby'),
            new xmldb_field('licence', XMLDB_TYPE_TEXT, null, null, null, null, null, 'createdon'),
        );

        // Conditionally launch add field.
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015110900, 'mbstpl');
    }

    if ($oldversion < 2015111600) {

        // Define field help to be added to block_mbstpl_question.
        $table = new xmldb_table('block_mbstpl_question');
        $field = new xmldb_field('help', XMLDB_TYPE_TEXT, null, null, null, null, null, 'param2');

        // Conditionally launch add field help.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('required', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'help');

        // Conditionally launch add field required.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015111600, 'mbstpl');
    }

    if ($oldversion < 2015113000) {

        // Define field type to be added to block_mbstpl_license.
        $table = new xmldb_table('block_mbstpl_license');
        $field = new xmldb_field('type', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'source');

        // Conditionally launch add field type.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $licenses = $DB->get_records('block_mbstpl_license');
        foreach ($licenses as $license) {
            $license->type = 1;
            $DB->update_record('block_mbstpl_license', $license);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015113000, 'mbstpl');
    }

     if ($oldversion < 2015120400) {

        // Define table block_mbstpl_clicense to be created.
        $table = new xmldb_table('block_mbstpl_clicense');

        // Adding fields to table block_mbstpl_clicense.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table block_mbstpl_clicense.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('shortname', XMLDB_KEY_FOREIGN_UNIQUE, array('shortname'), 'license', array('shortname'));

        // Conditionally launch create table for block_mbstpl_clicense.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015120400, 'mbstpl');
    }


    if ($oldversion < 2015121300) {

        $table = new xmldb_table('block_mbstpl_backup');

        // Define field userdataids to be added to block_mbstpl_backup.
        $field = new xmldb_field('userdataids', XMLDB_TYPE_TEXT, null, null, null, null, null, 'lastversion');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field excludedeploydataids to be added to block_mbstpl_backup.
        $field = new xmldb_field('excludedeploydataids', XMLDB_TYPE_TEXT, null, null, null, null, null, 'userdataids');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2015121300, 'mbstpl');
    }

    if ($oldversion < 2015121400) {

        // Define field excludedeploydataids to be added to block_mbstpl_template.
        $table = new xmldb_table('block_mbstpl_template');
        $field = new xmldb_field('excludedeploydataids', XMLDB_TYPE_TEXT, null, null, null, null, null, 'reminded');

        // Conditionally launch add field excludedeploydataids.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015121400, 'mbstpl');
    }

    if ($oldversion < 2015121800) {

        // Define table block_mbstpl_license to be dropped.
        $table = new xmldb_table('block_mbstpl_license');

        // Conditionally launch drop table for block_mbstpl_license.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015121800, 'mbstpl');
    }

    if ($oldversion < 2015122100) {

        // Define table block_mbstpl_asset to be dropped.
        $table = new xmldb_table('block_mbstpl_asset');
        // Conditionally launch drop table for block_mbstpl_asset.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2015122100, 'mbstpl');
    }

    if ($oldversion < 2016011100) {

        $table = new xmldb_table('block_mbstpl_question');
        // Conditionally install meta data questions.
        if ($dbman->table_exists($table)) {
            \block_mbstpl\questman\manager::install_questions();
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2016011100, 'mbstpl');
    }

    if ($oldversion < 2016011800) {

         // Define table block_mbstpl_subjects to be created.
        $table = new xmldb_table('block_mbstpl_subjects');

        //  Adding fields to table block_mbstpl_subjects.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');

         // Adding key to table block_mbstpl_subjects.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_mbstpl_subjects.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Conditionally install subject data.
        if ($dbman->table_exists($table)) {
            \block_mbstpl\questman\manager::install_subjects();
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2016011800, 'mbstpl');
    }

    if ($oldversion < 2016012600) {

        // Conditionally install subject data.
        \block_mbstpl\questman\manager::install_subjects();

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2016012600, 'mbstpl');
    }

    if ($oldversion < 2016012601) {

        // Conditionally install question data.
        \block_mbstpl\questman\manager::install_questions();

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2016012601, 'mbstpl');
    }

    if ($oldversion < 2016030700) {

        // Update question data.
        \block_mbstpl\questman\manager::install_questions();

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2016030700, 'mbstpl');
    }

    if ($oldversion < 2016030800) {

        // Update question data.
        \block_mbstpl\questman\manager::install_questions();

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2016030800, 'mbstpl');
    }

    if ($oldversion < 2016050400) {

        // Update question data.
        \block_mbstpl\questman\manager::update_lookupsetquestions();

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2016050400, 'mbstpl');
    }

     if ($oldversion < 2016062700) {

        // Define table block_mbstpl_userdeleted to be created.
        $table = new xmldb_table('block_mbstpl_userdeleted');

        // Adding fields to table block_mbstpl_userdeleted.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('firstname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_mbstpl_userdeleted.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table block_mbstpl_userdeleted.
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch create table for block_mbstpl_userdeleted.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Mbstpl savepoint reached.
        upgrade_block_savepoint(true, 2016062700, 'mbstpl');
    }


    return true;
}
