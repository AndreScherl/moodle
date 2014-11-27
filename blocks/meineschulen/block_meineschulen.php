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
 * Main code for the Meine Schulen block
 *
 * @package   block_meineschulen
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_meineschulen extends block_list {
    public function init() {
        $this->title = get_string('pluginname', 'block_meineschulen');
    }

    public function instance_can_be_docked() {
    	return false;
    }

    public function get_content() {
        global $CFG, $OUTPUT;
        require_once($CFG->dirroot.'/blocks/meineschulen/lib.php');

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();

      //  $this->content->footer = meineschulen::output_block_search_form();
        $this->content->footer = '';

        $arrowicon = $OUTPUT->pix_icon('i/navigationitem', '');
        $schools = meineschulen::get_my_schools();

//        if (!empty($schools)) {
//            $this->content->items[] = get_string('myschools', 'block_meineschulen');
//            $this->content->icons[] = '';
//        }
//        foreach ($schools as $school) {
//            $this->content->items[] = html_writer::link($school->viewurl, format_string($school->name));
//            $this->content->icons[] = $arrowicon;
//        }
//        $this->content->items[] = '';
//        $this->content->icons[] = '';
//
        $requests = meineschulen::get_course_requests();
//        foreach ($requests as $request) {
//            $info = (object)array('name' => format_string($request->name), 'count' => $request->count);
//            $str = get_string('viewcourserequests', 'block_meineschulen', $info);
//            $this->content->items[] = html_writer::link($request->viewurl, $str);
//            $this->content->icons[] = $arrowicon;
//        }


        $i = 0;
        foreach ($schools as $school)
        {
            $content = html_writer::start_div('col-xs-12 col-sm-6 col-md-4 schoolbox', array('data-courseid' => $school->id, 'data-type' => '1'));

            $content .= html_writer::start_div('schoolbox-meta');
            $content .= html_writer::start_div('row');
            $content .= html_writer::start_div('col-md-12 box-type text-right');
            $content .= html_writer::tag('i', '', array('class' => 'icon-me-schule'));
            $content .= html_writer::end_div();
            $content .= html_writer::end_div();
            $content .= html_writer::end_div();

            $content .= html_writer::start_div('schoolbox-inner' . (($i == 0) ? ' first' : ''));
            $url = new moodle_url('/course/index.php', array('categoryid' => $school->id));
            $content .= html_writer::start_tag('a', array('class' => 'schoolbox-link', 'href' => $school->viewurl));
            $content .= html_writer::start_div('panel-heading info');

            $content .= html_writer::tag('span', $school->name, array('class' => 'schoolname'));
            $content .= html_writer::tag('span', html_writer::tag('i', '', array('class' => 'icon-me-pfeil-weiter')), array('class' => 'vbox'));

            $content .= html_writer::end_div();
            $content .= html_writer::end_tag('a');
            $content .= html_writer::end_div();

            $content .= html_writer::end_div();

            $i++;
            $this->content->items[] = $content;
        }

//        foreach ($requests as $request)
//        {
//
//            $content = html_writer::start_div('col-xs-12 col-sm-6 col-md-4 schoolbox', array('data-courseid' => $request->id, 'data-type' => '1'));
//
//            $content .= html_writer::start_div('schoolbox-meta');
//            $content .= html_writer::start_div('row');
//            $content .= html_writer::start_div('col-md-12 box-type text-right');
//            $content .= html_writer::tag('i', '', array('class' => 'icon-me-schule'));
//            $content .= html_writer::end_div();
//            $content .= html_writer::end_div();
//            $content .= html_writer::end_div();
//
//            $content .= html_writer::start_div('schoolbox-inner' . (($i == 0) ? ' first' : ''));
//            $url = new moodle_url('/course/index.php', array('categoryid' => $request->id));
//            $content .= html_writer::start_tag('a', array('class' => 'schoolbox-link', 'href' => $url));
//            $content .= html_writer::start_div('panel-heading info');
//
//            $content .= html_writer::tag('span', $request->name, array('class' => 'schoolname'));
//            $content .= html_writer::tag('span', html_writer::tag('i', '', array('class' => 'icon-me-pfeil-weiter')), array('class' => 'vbox'));
//
//            $content .= html_writer::end_div();
//            $content .= html_writer::end_tag('a');
//            $content .= html_writer::end_div();
//
//            $content .= html_writer::end_div();
//
//            $i++;
//            $this->content->items[] = $content;
//        }

        return $this->content;
    }

    function has_config() {return true;}

    function hide_header() {return true;}
}