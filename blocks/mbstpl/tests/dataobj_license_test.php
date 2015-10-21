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

use block_mbstpl\dataobj\license,
    block_mbstpl\dataobj\meta,
    block_mbstpl\dataobj\asset;


defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/utils.php');

/**
 * Unit tests for block_mbstpl\dataobj\template
 * @group block_mbstpl
 */
class block_mbstpl_dataobj_license_test extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest(true);
    }

    public function test_fetch_all_used_shortnames() {

        $shortnames = license::fetch_all_used_shortnames();
        $this->assertCount(0, $shortnames, "No shortnames should be used to begin with");

        $meta = block_mbstpl_test_utils::create_meta('licenseshortname');
        block_mbstpl_test_utils::create_asset($meta->id, 'licenseshortname');

        $shortnames = license::fetch_all_used_shortnames();
        $this->assertEquals(array('licenseshortname'), $shortnames, "Should have found a single used license 'licenseshortname'");

        block_mbstpl_test_utils::create_asset($meta->id, 'licenseshortname2');
        block_mbstpl_test_utils::create_asset($meta->id, 'licenseshortname3');

        $shortnames = license::fetch_all_used_shortnames();
        $this->assertCount(3, $shortnames, "Should find 3 shortnames");
        $this->assertContains('licenseshortname', $shortnames);
        $this->assertContains('licenseshortname2', $shortnames);
        $this->assertContains('licenseshortname3', $shortnames);
    }

    public function test_fetch_all_mapped_by_shortname() {

        block_mbstpl_test_utils::create_license('license1');
        block_mbstpl_test_utils::create_license('license3');

        $assets = array(
            block_mbstpl_test_utils::create_asset(1, 'license1'),
            block_mbstpl_test_utils::create_asset(1, 'license2'),
            block_mbstpl_test_utils::create_asset(1, 'license3')
        );

        $licenses = license::fetch_all_mapped_by_shortname($assets);

        $this->assertCount(2, $licenses, "Should only find two licenses");
        $this->assertArrayHasKey('license1', $licenses, "Should have a 'license1' license");
        $this->assertArrayNotHasKey('license2', $licenses, "Should not have a 'license2' license");
        $this->assertArrayHasKey('license3', $licenses, "Should have a 'license3' license");
    }

}
