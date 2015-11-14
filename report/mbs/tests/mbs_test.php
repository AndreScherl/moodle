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
 * certpaypal enrolment plugin tests.
 *
 * @package    enrol_certpaypal
 * @category   phpunit
 * @copyright  2015 Andreas Wagner (Synergy Learning)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class report_mbs_testcase extends advanced_testcase {

    public function test_report() {
        global $DB, $CFG;
        
        $this->resetAfterTest();
        $course1 = $this->getDataGenerator()->create_course();
        
        // Create a page with old TeX Notation.
        $page1 = new \stdClass();
        $page1->course = $course1->id;
        $page1->content = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr,
            sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat,
            sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.
            Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet';
        
        $page1 = $this->getDataGenerator()->create_module('page', $page1);
        
        \report_mbs\local\reporttex::report_tables('page');
        $result = $DB->get_record('report_mbs_tex', array('tablename' => 'page'));
        $this->assertEmpty($result->count);
        
        $page2 = new \stdClass();
        $page2->course = $course1->id;
        $page2->content = 'Lorem ipsum ... <p> $$\frac{1}{2}$$$$Exp without blank$$</p><div>$$another TeX Content$$</div>';
        
        $page2 = $this->getDataGenerator()->create_module('page', $page2);

        \report_mbs\local\reporttex::report_tables('page');
        $result = $DB->get_record('report_mbs_tex', array('tablename' => 'page'));
        $this->assertNotEmpty($result->count);
        
        // Now replace the TeX Notation.
        $result->active = 1;
        $DB->update_record('report_mbs_tex', $result);
        
        \report_mbs\local\reporttex::replace_tex();
        
        // Should be now more $$ now.
        \report_mbs\local\reporttex::report_tables('page');
        $result = $DB->get_record('report_mbs_tex', array('tablename' => 'page'));
        $this->assertEmpty($result->count);
        
        $page2 = $DB->get_record('page', array('id' => $page2->id));
        
        // Instead there should be a replacement by \( and \)
        $matches = array();
        preg_match_all('/\\(/', $page2->content, $matches);
        $this->assertEquals(3, count($matches[0]));
     
    }
}
