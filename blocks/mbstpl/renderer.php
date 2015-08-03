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
defined('MOODLE_INTERNAL') || die;

class block_mbstpl_renderer extends plugin_renderer_base {

    /**
     * List questions.
     * @param array $questions
     * @return string
     */
    public function list_questions($questions) {
        $table = new html_table();
        $table->head = array(
            get_string('questionname', 'block_mbstpl'),
            get_string('questiontype', 'block_mbstpl'),
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
        $url = new moodle_url('/blocks/mbstpl/questman/quest.php', array('id' => $question->id));
        $row[] = html_writer::link($url, get_string('edit'));
        $deltitle = $question->inuse ? get_string('removefromdraft', 'block_mbstpl') : get_string('delete');
        $url = new moodle_url('/blocks/mbstpl/questman/confirmdel.php', array('id' => $question->id));
        $row[] = html_writer::link($url, $deltitle);
        $uplink = '';
        if ($canmoveup) {
            $url = new moodle_url('/blocks/mbstpl/questman/index.php', array('moveup' => $question->id));
            $link = html_writer::link($url, get_string('up'));
            $uplink = $link;
        }
        $row[] = $uplink;
        $downlink = '';
        if ($canmovedown) {
            $url = new moodle_url('/blocks/mbstpl/questman/index.php', array('movedown' => $question->id));
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
            get_string('questionname', 'block_mbstpl'),
            get_string('questiontype', 'block_mbstpl'),
            get_string('edit'),
            get_string('useq', 'block_mbstpl'),
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
        $url = new moodle_url('/blocks/mbstpl/questman/quest.php', array('id' => $question->id));
        $row[] = html_writer::link($url, get_string('edit'));
        $usetitle = get_string('useq', 'block_mbstpl');
        $url = new moodle_url('/blocks/mbstpl/questman/index.php', array('useq' => $question->id));
        $row[] = html_writer::link($url, $usetitle);
        return $row;
    }

    /**
     * Box on top of the template page containing course and template information.
     * @param $course
     * @param $template
     */
    public function coursebox($course, $template) {
        global $DB;

        $author = $DB->get_record('user', array('id' => $template->authorid));
        $authorname = $author ? fullname($author). ' '. $author->email : '';
        $reviewer = $DB->get_record('user', array('id' => $template->reviewerid));
        $reviewername = $reviewer ? fullname($reviewer). ' '. $reviewer->email : '';
        $status = \block_mbstpl\course::get_statusshortname($template->status);
        $statusbox = html_writer::div(get_string($status, 'block_mbstpl'), "statusbox $status");

        $cbox = '';
        $table = new html_table();
        $table->attributes['class'] = 'boxtable';
        $table->data = array();
        $table->data[] = array(get_string('coursename', 'block_mbstpl'), $course->fullname);
        $table->data[] = array(get_string('creator', 'block_mbstpl'), $authorname);
        $table->data[] = array(get_string('creationdate', 'block_mbstpl'), userdate($course->timecreated));
        $table->data[] = array(get_string('lastupdate', 'block_mbstpl'), userdate($template->timemodified));
        $table->data[] = array(get_string('assigned', 'block_mbstpl'), $reviewername);
        $table->data[] = array(get_string('status'), $statusbox);

        $cbox .= html_writer::table($table);
        return html_writer::div($cbox, 'mbstcoursebox');
    }
	
	/**
     * Return list of tempalte history.
     * @param array $revhists
     */
	public function templatehistory($revhists) {
		$html = '';
		$html .= html_writer::tag('h3', get_string('history', 'block_mbstpl'));
		$table = new html_table();
		$table->header = array(
			get_string('status'),
			get_string('assigned', 'block_mbstpl'),
			get_string('updated'),
		);
        $table->data = array();
		foreach($revhists as $hist) {
			$status = \block_mbstpl\course::get_statusshortname($hist->status);
			$assignedname = $hist->firstname . ' ' . $hist->lastname;
			$statusbox = html_writer::div(get_string($status, 'block_mbstpl'), "statusbox $status");
			$table->data[] = array(
				$statusbox,
				$assignedname,
				userdate($hist->timecreated),
			);
		}
		$html .= html_writer::table($table);
		return html_writer::div($html, 'mbstrevhist');
	}
}
