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

use \block_mbstpl AS mbst;

class block_mbstpl_renderer extends plugin_renderer_base {

    protected function qtype_name($type) {
        $gs = get_string_manager();
        if ($gs->string_exists('pluginname', 'profilefield_'.$type)) {
            return get_string('pluginname', 'profilefield_'.$type);
        } else {
            return get_string('field_'.$type, 'block_mbstpl');
        }
    }

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
        foreach ($questions as $question) {
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
        $row[] = $this->qtype_name($question->datatype);
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
        foreach ($questions as $question) {
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
        $row[] = $this->qtype_name($question->datatype);
        $url = new moodle_url('/blocks/mbstpl/questman/quest.php', array('id' => $question->id));
        $row[] = html_writer::link($url, get_string('edit'));
        $usetitle = get_string('useq', 'block_mbstpl');
        $url = new moodle_url('/blocks/mbstpl/questman/index.php', array('useq' => $question->id));
        $row[] = html_writer::link($url, $usetitle);
        return $row;
    }

    /**
     * Box on top of the template page containing course and template information.
     * @param object $course
     * @param mbst\dataobj\template $template
     * @param bool $showstatus
     */
    public function coursebox($course, mbst\dataobj\template $template, $showstatus = true) {
        global $DB;

        $authorname = mbst\course::get_creators($template->id);
        $reviewer = $DB->get_record('user', array('id' => $template->reviewerid));
        $reviewername = $reviewer ? fullname($reviewer). ' '. $reviewer->email : '';
        $courseurl = new moodle_url('/course/view.php', array('id' => $template->courseid));
        $courselink = html_writer::link($courseurl, format_string($course->fullname));

        $cbox = '';
        $table = new html_table();
        $table->attributes['class'] = 'boxtable';
        $table->data = array();
        $table->data[] = array(get_string('coursename', 'block_mbstpl'), $courselink);
        $table->data[] = array(get_string('creator', 'block_mbstpl'), $authorname);
        $table->data[] = array(get_string('creationdate', 'block_mbstpl'), userdate($course->timecreated));
        $table->data[] = array(get_string('lastupdate', 'block_mbstpl'), userdate($template->timemodified));
        if ($showstatus) {
            $assignedname = $template->status == $template::STATUS_UNDER_REVISION ? $authorname : $reviewername;
            $status = \block_mbstpl\course::get_statusshortname($template->status);
            $statusbox = html_writer::div(get_string($status, 'block_mbstpl'), "statusbox $status");
            $table->data[] = array(get_string('assigned', 'block_mbstpl'), $assignedname);
            $table->data[] = array(get_string('status'), $statusbox);
        }

        $cbox .= html_writer::table($table);
        return html_writer::div($cbox, 'mbstcoursebox');
    }

    /**
     * A box showing the current status of the template
     *
     * @param int $status
     * @return string
     * @throws coding_exception
     */
    public function status_box($status) {
        $statusname = \block_mbstpl\course::get_statusshortname($status);
        return html_writer::div(get_string($statusname, 'block_mbstpl'), "statusbox $statusname");
    }

    /**
     * Return template usage
     * @param $template
     */
    public function templateusage($courseswithcreators) {

        $html = '';

        $html .= html_writer::tag('h3', get_string('coursesfromtemplate', 'block_mbstpl'));

        $table = new html_table();
        $table->head = array(
            get_string('fullname'),
            get_string('shortname'),
            get_string('createdby', 'block_mbstpl'),
            get_string('createdon', 'block_mbstpl')
        );

        $table->data = array_map(function($coursewithcreator) {
            $courseurl = new \moodle_url('/course/view.php', array('id' => $coursewithcreator->course_id));
            return array(
                html_writer::link($courseurl, $coursewithcreator->course_fullname),
                $coursewithcreator->course_shortname,
                $coursewithcreator->course_creator_name,
                userdate($coursewithcreator->course_createdon)
            );
        }, $courseswithcreators);

        $html .= html_writer::table($table);

        return $html;
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
            '',
        );
        $table->data = array();
        $commentpic = \html_writer::img(
            new moodle_url('/blocks/mbstpl/pix/comments.png'),
            get_string('viewfeedback', 'block_mbstpl'));
        foreach ($revhists as $hist) {
            $assignedname = $hist->firstname.' '.$hist->lastname;
            $viewfdbk = '';
            if ($hist->hasfeedback) {
                $feedbackurl = new \moodle_url('/blocks/mbstpl/feedbackdetail.php', array('id' => $hist->id));
                $viewfdbk = \html_writer::link($feedbackurl, $commentpic);
            }
            $table->data[] = array(
                $this->status_box($hist->status),
                $assignedname,
                userdate($hist->timecreated),
                $viewfdbk,
            );
        }
        $html .= html_writer::table($table);
        return html_writer::div($html, 'mbstrevhist');
    }

    /**
     * Print all my tempates to the block.
     * @param object $templates
     * @return string
     */
    public function mytemplates($templates) {
        $html = '';
        $commonhead = array(
            get_string('coursename', 'block_mbstpl'),
            get_string('status'),
            get_string('assigneddate', 'block_mbstpl'),
            get_string('assignee', 'block_mbstpl'),
            '',
        );
        $imgview = html_writer::img(new moodle_url('/blocks/mbstpl/pix/eye.png'), get_string('view'));
        $viewurl = new \moodle_url('/blocks/mbstpl/viewfeedback.php');
        foreach ($templates as $type => $typetemplates) {
            if (empty($typetemplates)) {
                continue;
            }
            $html .= html_writer::start_tag('p');
            $html .= html_writer::div(get_string('my'.$type, 'block_mbstpl'));
            $html .= html_writer::start_tag('ul');
            
            foreach ($typetemplates as $template) {
                $courseitemstatus = \block_mbstpl\course::get_statusshortname($template->status);
                $viewurl->param('course', $template->courseid);
                $courseitemclasses = 'statuslink';
                if ($template->viewfeedback) {
                    $courseitemclasses .= ' viewfeedback';
                }
                $courseitemlink = html_writer::link($viewurl, $template->coursename, array('class' => $courseitemclasses . ' ' . $courseitemstatus));
                $courseitem = html_writer::tag('li', $courseitemlink);
                $html .= $courseitem;
            }
            $html .= html_writer::end_tag('ul');
            $html .= html_writer::end_tag('p');
            
        }
        return $html;
    }

    public function templatesearch($searchform, $courses, $layout) {
        
        // Add the search form.
        $html = \html_writer::div($searchform->render(), 'mbstpl-search-form');

        $headingpanel = \html_writer::tag('h3', get_string('searchresult', 'block_mbstpl'));

        // Add layout and pagination controllers.
        $listcontrollers = get_string('layout', 'block_mbstpl') . ': ';
        $link = new moodle_url('#');

        // TODO: Add pagination controls.

        $listcontrollers .= \html_writer::link($link, \html_writer::img(
            $this->output->pix_url('e/table', 'core'), get_string('layoutgrid', 'block_mbstpl'), array('l' => 'grid')));
        $listcontrollers .= \html_writer::link($link, \html_writer::img(
            $this->output->pix_url('e/bullet_list', 'core'), get_string('layoutlist', 'block_mbstpl'), array('l' => 'list')));
        $headingpanel .= \html_writer::div($listcontrollers, 'mbstpl-list-controller');

        $html .= \html_writer::div($headingpanel, 'mbstpl-heading-panel');

        // Render result listing.
        $searchlisting = $this->mbstpl_resultlist($courses, $layout);
        $html .= \html_writer::div($searchlisting, 'mbstpl-search-listing clearfix');
        return $html;
    }

    /**
     * render result listing of block mbstpl
     *
     * @param array $courses list of courses
     * @param string $layout
     * @return string html to be displayed
     */
    public function mbstpl_resultlist($courses, $layout) {
        $searchlisting = '';
        $complainturl = new moodle_url(get_config('block_mbstpl', 'complainturl'));
        
        if (count($courses) > 0) {
            foreach ($courses as $course) {
                $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
                $listitem = \html_writer::link($courseurl, $course->fullname);                
                // TBD, see spec page 14.                
                $externalurl = clone($complainturl);
                $externalurl->param('courseid', $course->id);
                $righticons = '';
                $complaintlink = \html_writer::link($externalurl,
                    \html_writer::img(new moodle_url('/blocks/mbstpl/pix/complaint.png'), $course->fullname));
                $righticons .= $complaintlink;
                $courselink = \html_writer::link($courseurl,
                        \html_writer::img($this->output->pix_url('t/collapsed_empty', 'core'), $course->fullname));
                $righticons .= $courselink;
                $listitem .= html_writer::div($righticons, 'righticons');
                $listitem .= html_writer::div($course->catname, 'crsdetails');
                $listitem .= html_writer::div(get_string('author', 'block_mbstpl').': ' .$course->authorname, 'crsauthor');
                if (!is_null($course->rating)) {
                    $listitem .= $this->rating($course->rating, false);
                }
                $searchlisting .= \html_writer::div($listitem, "mbstpl-list-item mbstpl-list-item-{$layout}");
            }
        } else {
            $searchlisting .= \html_writer::tag('em', get_string('noresults', 'block_mbstpl'));
        }
        return $searchlisting;
    }
    
    public function rating($avg, $label = true) {
        $roundavg = round($avg * 2);
        $inner = '';

        for ($i = 1; $i <= 5; $i++) {
            if ($roundavg >= $i * 2) {
                $inner .= html_writer::div('', 'star fullstar');
            } else if ($roundavg >= $i * 2 - 1) {
                $inner .= html_writer::div('', 'star halfstar');
            } else {
                $inner .= html_writer::div('', 'star emptystar');
            }
        }
        $output = '';
        if ($label) {
            $output .= html_writer::div(get_string('ratingavg', 'block_mbstpl'));
        }

        $output .= html_writer::div($inner, 'templaterating');
        return $output;
    }

    public function feedback(mbst\dataobj\template $template, mbst\dataobj\revhist $revhist) {
        $out = '';

        // Heading.
        $out .= $this->output->heading(get_string('feedbackfor', 'block_mbstpl', $revhist->get_assigned_name()));

        $info = '';

        // Status.
        $info .= html_writer::tag('dt', get_string('status'));
        $info .= html_writer::tag('dd', $this->status_box($revhist->status));

        // Timestamp.
        $info .= html_writer::tag('dt', get_string('timeassigned', 'block_mbstpl'));
        $info .= html_writer::tag('dd', userdate($revhist->timecreated));

        // Feedback text.
        $info .= html_writer::tag('dt', get_string('feedback', 'block_mbstpl'));
        $info .= html_writer::tag('dd', format_text($revhist->feedback, $revhist->feedbackformat));

        // Feedback files.
        if ($files = $revhist->get_files(context_course::instance($template->courseid))) {
            $info .= html_writer::tag('dt', get_string('feedbackfiles', 'block_mbstpl'));
            $info .= html_writer::tag('dd', $this->file_list($files));
        }

        $out .= html_writer::tag('dl', $info, array('class' => 'list'));

        return $out;
    }

    /**
     * Output an ordered list of links to files
     *
     * @param stored_file[] $files
     * @return string
     */
    public function file_list($files) {
        $out = '';
        foreach ($files as $file) {
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
                                                   $file->get_itemid(), $file->get_filepath(), $file->get_filename(), true);
            $filename = s($file->get_filename());
            $out .= html_writer::tag('li', html_writer::link($url, $filename));
        }
        return html_writer::tag('ul', $out, array('class' => 'mbsfilelist'));
    }

    /**
     * List of questions that appear in the search
     * @param array $questions questions in display order with id, title and enabled
     * @param moodle_url $thisurl
     * @return string
     */
    public function manage_search($questions, $thisurl) {

        $mainurl = clone($thisurl);
        $mainurl->param('sesskey', sesskey());
        $attrs = array('class' => 'iconsmall');
        $imgshow = html_writer::img($this->output->pix_url('t/show'), 'disable', $attrs);
        $imghide = html_writer::img($this->output->pix_url('t/hide'), 'enable', $attrs);
        $imgup = html_writer::img($this->output->pix_url('t/up'), 'up', $attrs);
        $imgdown = html_writer::img($this->output->pix_url('t/down'), 'down', $attrs);
        $imgspacer = html_writer::img($this->output->pix_url('spacer'), '', $attrs);

        $table = new html_table();
        $table->head = array(
            get_string('questionname', 'block_mbstpl'),
            get_string('enable'),
            get_string('move'),
        );
        $table->align = array('left', 'center', 'center');
        $questions = array_values($questions);
        foreach ($questions as $qpos => $question) {
            $cells = array();
            $class = '';
            $cells[] = $question->name;
            $url = clone($mainurl);
            if ($question->enabled) {
                $img = $imghide;
                $url->param('disableid', $question->id);
            } else {
                $class = 'dimmed_text';
                $img = $imgshow;
                $url->param('enableid', $question->id);
            }
            $cells[] = html_writer::link($url, $img);

            $canup = false;
            $candown = false;
            if ($question->enabled) {
                if (isset($questions[$qpos - 1])) {
                    $canup = true;
                }
                if (isset($questions[$qpos + 1]) && $questions[$qpos + 1]->enabled) {
                    $candown = true;
                }
            }
            $arrowhtml = '';
            if ($canup) {
                $url = clone($mainurl);
                $url->param('moveupid', $question->id);
                $arrowhtml .= html_writer::link($url, $imgup);
            } else {
                $arrowhtml .= $imgspacer;
            }
            if ($candown) {
                $url = clone($mainurl);
                $url->param('movedownid', $question->id);
                $arrowhtml .= html_writer::link($url, $imgdown);
            } else {
                $arrowhtml .= $imgspacer;
            }
            $cells[] = $arrowhtml;

            $row = new html_table_row($cells);
            $row->attributes['class'] = $class;
            $table->data[] = $row;
        }
        return html_writer::table($table);
    }

    /**
     * Create a table of licenses
     *
     * @param \block_mbstpl\dataobj\license[] $licenses
     * @param string[] $usedshortnames
     */
    public function license_table($licenses, $usedshortnames = array()) {

        $table = new html_table();
        $table->head = array(
            get_string('license_shortname', 'block_mbstpl'),
            get_string('license_fullname', 'block_mbstpl'),
            get_string('license_source', 'block_mbstpl'),
            get_string('license_used', 'block_mbstpl')
        );

        foreach ($licenses as $license) {

            if (in_array($license->shortname, $usedshortnames)) {
                $usage = get_string('license_used', 'block_mbstpl');
            } else {
                $url = new \moodle_url('/blocks/mbstpl/editlicenses.php', array('deletelicenseid' => $license->id));
                $usage = html_writer::link($url, get_string('delete'));
            }

            $row = new html_table_row(array(
                $license->shortname,
                $license->fullname,
                $license->source,
                $usage
            ));

            $table->data[] = $row;
        }

        return html_writer::table($table);
    }

    /**
     * Render a list of assets used by a template
     *
     * @param \block_mbstpl\dataobj\asset[] $assets
     * @param \block_mbstpl\dataobj\license[] $licenses
     */
    public function references_block_content($assets, $licenses) {

        $items = array();
        foreach ($assets as $asset) {

            $item = html_writer::link($asset->url, $asset->url);
            $item .= ' ';
            $item .= $asset->owner . ', ' . $asset->source;

            /* @var $license \block_mbstpl\dataobj\license */
            $license = $licenses[$asset->license];
            if ($license) {
                $item .= ' (' . html_writer::link($license->source, $license->fullname) . ')';
            }


            $items[] = $item;
        }

        return html_writer::alist($items, null, 'ol');
    }

    /**
     * Displays a backup files viewer. Modified from \core_backup_renderer\render_backup_files_viewer()
     *
     * @param array $options
     * @return string
     */
    public function render_backup_files_viewer(array $options = null) {
        $viewer = new backup_files_viewer($options);
        $files = $viewer->files;

        $table = new html_table();
        $table->attributes['class'] = 'backup-files-table generaltable';
        $table->head = array(get_string('filename', 'backup'), get_string('time'), get_string('size'), get_string('download'), get_string('restore'));
        $table->width = '100%';
        $table->data = array();

        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }
            $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), true);
            $params = array();
            $params['action'] = 'choosebackupfile';
            $params['filename'] = $file->get_filename();
            $params['filepath'] = $file->get_filepath();
            $params['component'] = $file->get_component();
            $params['filearea'] = $file->get_filearea();
            $params['filecontextid'] = $file->get_contextid();
            $params['contextid'] = $viewer->currentcontext->id;
            $params['itemid'] = $file->get_itemid();
            $restoreurl = new moodle_url('/backup/restorefile.php', $params);
            $table->data[] = array(
                $file->get_filename(),
                userdate($file->get_timemodified()),
                display_size($file->get_filesize()),
                html_writer::link($fileurl, get_string('download')),
                html_writer::link($restoreurl, get_string('restore')),
            );
        }

        $html = html_writer::table($table);
        return $html;
    }
}
