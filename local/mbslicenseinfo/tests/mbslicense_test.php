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
 * Unit tests for mbslicenseinfo
 *
 * @package   local_mbslicenseinfo
 * @copyright 2016 Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class local_mbslicenseinfo_testcase extends advanced_testcase {

    public function test_plugin_installed() {
        $config = get_config('local_mbslicenseinfo');
        $this->assertTrue(isset($config->cleanupcount));
    }

    private function create_stored_file($filename, $addfmeta = false) {
        global $USER, $CFG;

        $fs = get_file_storage();
        $filename = $CFG->tempdir . '/' . $filename;

        $file = fopen($filename, 'a+');
        fwrite($file, $filename);
        fclose($file);

        $fr = array(
            'contextid' => 1,
            'component' => 'site',
            'filearea' => 'test',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => $USER->id,
            'timecreated' => time(),
            'timemodified' => time());

        $storedfile = $fs->create_file_from_pathname($fr, $filename);

        if ($addfmeta) {
            $filemeta = new stdClass();
            $filemeta->title = $filename;
            $filemeta->source = 'http://www.example.com';
            \local_mbslicenseinfo\local\mbslicenseinfo::set_fmeta($filemeta, $storedfile->get_id());
        }

        return $storedfile;
    }

    public function test_cleanup() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $storedfiles = array();
        for ($i = 0; $i < 100; $i++) {
            $storedfiles[$i] = $this->create_stored_file('test'. $i, true);
        }

        // Delete files.
        for ($i = 0; $i < 50; $i++) {
            $storedfiles[$i]->delete();
        }

        $countwithmeta = $DB->count_records('local_mbslicenseinfo_fmeta');
        $this->assertEquals(100, $countwithmeta);

        // Do cleanup.
        set_config('cleanupcount', 10, 'local_mbslicenseinfo');
        \local_mbslicenseinfo\local\mbslicenseinfo::cleanup_fmeta();

        $countwithmeta = $DB->count_records('local_mbslicenseinfo_fmeta');
        $this->assertEquals(90, $countwithmeta);

        set_config('cleanupcount', 200, 'local_mbslicenseinfo');
        \local_mbslicenseinfo\local\mbslicenseinfo::cleanup_fmeta();

        $countwithmeta = $DB->count_records('local_mbslicenseinfo_fmeta');
        $this->assertEquals(50, $countwithmeta);

    }

}
