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
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \block_mbstpl as mbst;

class block_mbstpl extends block_base {

    public function init() {

        $this->title = get_string('pluginname', 'block_mbstpl');
    }

    public function get_content() {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        $searchurl = new moodle_url('/blocks/mbstpl/templatesearch.php');
        $searchlink = html_writer::link($searchurl, get_string('templatesearch', 'block_mbstpl'));
        $this->content->text .= html_writer::tag('p', $searchlink);

        $templates = mbst\user::get_templates();
        if (empty($templates)) {
            $this->content->text .= get_string('notemplates', 'block_mbstpl');
        } else {
            $renderer = $PAGE->get_renderer('block_mbstpl');
            $this->content->text .= $renderer->mytemplates($templates);
        }

        return $this->content;
    }

    function has_config() {
        return true;
    }

    public function cron() {
        mbst\reporting::statscron();
        mbst\reporting::remindercron();
    }
}