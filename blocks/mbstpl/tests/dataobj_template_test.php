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

use block_mbstpl\dataobj\template,
    block_mbstpl\dataobj\coursefromtpl;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/utils.php');

/**
 * Unit tests for block_mbstpl\dataobj\template
 * @group block_mbstpl
 */
class block_mbstpl_dataobj_template_test extends advanced_testcase {

    public function test_get_from_course() {

        $this->resetAfterTest(true);

        $template = block_mbstpl_test_utils::create_template();

        $coursefromtpl = block_mbstpl_test_utils::create_coursefromtpl($template->id, 2);

        $fetchedtemplate = template::get_from_course($template->courseid);
        $this->assertEquals($template->id, $fetchedtemplate->id, "Should return same template when searching by template's course id");

        $fetchedtemplate = template::get_from_course($coursefromtpl->courseid);
        $this->assertEquals($template->id, $fetchedtemplate->id, "Should return same template when searching by coursefromtpl's course id");

        $fetchedtemplate = template::get_from_course(100);
        $this->assertNull($fetchedtemplate, "Should not return a template when given non-existent course id");
    }
}
