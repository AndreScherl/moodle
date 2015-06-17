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
 * @package block
 * @subpackage mbstemplating
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_mbstemplating_renderer extends plugin_renderer_base {

    /**
     * List questions.
     * @param array $questions
     * @return string
     */
    public function list_questions($questions) {
        $table = new html_table();
        $table->head = array(
            get_string('questionname', 'block_mbstemplating'),
            get_string('questiontype', 'block_mbstemplating'),
            get_string('edit'),
            get_string('delete'),
            get_string('moveup'),
            get_string('movedown'),
        );
        $table->align = array('left', 'left', 'center', 'center', 'center', 'center');
        $numqs = count($questions);
        $qnum = 0;
        foreach($questions as $question) {
            $canmoveup = $qnum;
            $canmovedown = $qnum != $numqs - 1;
            $qnum++;
            $table->data[] = $this->list_one_question($question, $canmoveup, $canmovedown);
        }
        return html_writer::table($table);
    }

    /**
     * List one question row for html_table.
     * @param object $question
     * @param bool $canmoveup
     * @param bool $canmovedown
     * @return array
     */
    public function list_one_question($question, $canmoveup = true, $canmovedown = true) {
        $row = array();
        $row[] = $question->name;
        $row[] = get_string('pluginname', 'profilefield_'.$question->datatype);
        $url = new moodle_url('/blocks/mbstemplating/questman/quest.php', array('id' => $question->id));
        $row[] = html_writer::link($url, get_string('edit'));
        $deltitle = $question->inuse ? get_string('removefromdraft', 'block_mbstemplating') : get_string('delete');
        $url = new moodle_url('/blocks/mbstemplating/questman/confirmdel.php', array('id' => $question->id));
        $row[] = html_writer::link($url, $deltitle);
        $uplink = '';
        if ($canmoveup) {
            $url = new moodle_url('/blocks/mbstemplating/questman/index.php', array('moveup' => $question->id));
            $link = html_writer::link($url, get_string('up'));
            $uplink = $link;
        }
        $row[] = $uplink;
        $downlink = '';
        if ($canmovedown) {
            $url = new moodle_url('/blocks/mbstemplating/questman/index.php', array('movedown' => $question->id));
            $link = html_writer::link($url, get_string('down'));
            $downlink = $link;
        }
        $row[] = $downlink;
        return $row;
    }

    /**
     * List questions for the question bank.
     * @param array $questions
     * @return string
     */
    public function list_bank_questions($questions) {
        $table = new html_table();
        $table->head = array(
            get_string('questionname', 'block_mbstemplating'),
            get_string('questiontype', 'block_mbstemplating'),
            get_string('edit'),
            get_string('useq', 'block_mbstemplating'),
        );
        $table->align = array('left', 'left', 'center', 'center');
        foreach($questions as $question) {
            $table->data[] = $this->list_one_bank_question($question);
        }
        return html_writer::table($table);
    }

    /**
     * List one bank question row for html_table.
     * @param object $question
     * @return array
     */
    public function list_one_bank_question($question) {
        $row = array();
        $row[] = $question->name;
        $row[] = get_string('pluginname', 'profilefield_'.$question->datatype);
        $url = new moodle_url('/blocks/mbstemplating/questman/quest.php', array('id' => $question->id));
        $row[] = html_writer::link($url, get_string('edit'));
        $usetitle = get_string('useq', 'block_mbstemplating');
        $url = new moodle_url('/blocks/mbstemplating/questman/index.php', array('useq' => $question->id));
        $row[] = html_writer::link($url, $usetitle);
        return $row;
    }

}
