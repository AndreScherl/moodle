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
 * Test-tools to generate data.
 *
 * @package   local_mbs
 * @copyright 2015 Andreas Wagner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../config.php');
require_once($CFG->dirroot . '/lib/tablelib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$action = optional_param('action', '', PARAM_ALPHA);

$PAGE->set_url(new moodle_url('/local/mbs/tool/fixcatimages.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();

function local_mbs_fix_file_entries($filename) {
    global $DB;

    // Check whether there are two entries within coursecat description area with same context and filename '.'
    $sql = "SELECT * FROM {files}
            WHERE component = 'coursecat' AND filearea = 'description' AND filename LIKE '{$filename}%'";
    
    $entries = $DB->get_records_sql($sql);
    
    $groupedentries = array();
    foreach ($entries as $entry) {

        if (!isset($groupedentries[$entry->contextid])) {
            $groupedentries[$entry->contextid] = array();
        }

        $groupedentries[$entry->contextid][$entry->filepath] = $entry;
    }

    foreach ($groupedentries as $gentry) {

        if (count($gentry) > 1) {

            $sql = "DELETE FROM {files}
            WHERE component = 'coursecat' AND filearea = 'description' AND filename LIKE '{$filename}%' AND filepath = '/header/";
            
            $DB->execute($sql);
        }

        if (count($gentry) == 1) {

            $current = reset($gentry);
            
            if ($current->filepath == '/header/') {

                $current->filepath = '/';
                $DB->update_record('files', $current);
                echo "<br/>updated: ".$current->contextid." ".$current->filename;
                
            }
        }
    }
}

switch ($action) {

    case 'show' :

        if (!$entries = $DB->get_records('files', array('filepath' => '/header/', 'component' => 'coursecat', 'filearea' => 'description'))) {
            echo "NO entries";
        } else {

            $table = new html_table();

            foreach ($entries as $e) {

                $table->data[] = array($e->pathnamehash, $e->contextid, $e->component, $e->filearea, $e->itemid, $e->filepath, $e->filename);
            }

            echo html_writer::table($table);
        }

        break;

    case 'fix' :

        local_mbs_fix_file_entries('.');
        local_mbs_fix_file_entries('background');

        $sql = "Update {files} set filepath = '/', pathnamehash = SHA1(CONCAT('/',contextid,'/',component,'/',filearea,'/',itemid,filepath,filename))
                WHERE component = 'coursecat' AND filearea = 'description'";

        $DB->execute($sql);

        echo "... finished fix.";
        break;
}
echo $OUTPUT->footer();
