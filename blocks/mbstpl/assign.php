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
define('MBSTPL_SKIP_USED_REFERENCES', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $PAGE, $USER, $DB;

use \block_mbstpl AS mbst;
use block_mbstpl\dataobj\template;

$courseid = required_param('course', PARAM_INT);
$type = required_param('type', PARAM_ALPHA);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$template = new template(array('courseid' => $courseid), true, MUST_EXIST);

$thisurl = new moodle_url('/blocks/mbstpl/assign.php', array('course' => $course->id, 'type' => $type));
$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('incourse');

require_login($courseid, false);
$coursecontext = context_course::instance($courseid);

$PAGE->set_context($coursecontext);
$pagetitle = get_string('assign'.$type, 'block_mbstpl');
$PAGE->set_title($pagetitle);

if ($type == 'author') {
    if (!mbst\perms::can_assignauthor($template, $coursecontext)) {
        throw new moodle_exception('errorcannotassignauthor', 'block_mbstpl');
    }
} else if ($type == 'reviewer') {
    if (!mbst\perms::can_assignreview($template, $coursecontext) && !mbst\perms::can_returnreview($template, $coursecontext)) {
        throw new moodle_exception('errorcannotassignreviewer', 'block_mbstpl');
    }
} else {
    throw new moodle_exception('invalidtype', 'block_mbstpl');
}

// Prepare form customdata + data.
$editoropts = array('subdirs' => 0, 'maxfiles' => 0, 'context' => $coursecontext);
$fileopts = array('subdirs' => 0, 'maxfiles' => 1);
$customdata = array('type' => $type, 'editoropts' => $editoropts, 'fileopts' => $fileopts);

$formdata = (object)array('course' => $course->id, 'type' => $type,
                          'feedback' => $template->feedback, 'feedbackformat' => $template->feedbackformat);
$formdata = file_prepare_standard_editor($formdata, 'feedback', $editoropts);
$formdata = file_prepare_standard_filemanager($formdata, 'uploadfile', $fileopts, $coursecontext, 'block_mbstpl',
                                              template::FILEAREA, $template->id);

// Load possible users.
$showselector = ($type == 'author') || (mbst\perms::can_assignreview($template, $coursecontext));
if ($showselector) {
    $allowedreviewerids = null;
    if ($type == 'reviewer') {
        // When selecting a reviewer, restrict to the available users.
        $catcontext = context_coursecat::instance($course->category);
        $users = get_users_by_capability($catcontext, 'block/mbstpl:coursetemplatereview', 'u.id');
        if (empty($users)) {
            throw new moodle_exception('errornoassignableusers', 'block_mbstpl');
        }
        $allowedreviewerids = array_keys($users);
    }
    $selector = new mbst\selector\user_potential(null, array('allowedreviewerids' => $allowedreviewerids));
    if ($type == 'author') {
        $selector->exclude(array($USER->id)); // Cannot assign yourself as the author.
        $selector->set_default($template->authorid);
    } else {
        $selector->set_default($template->reviewerid);
    }

    $customdata['selector'] = $selector->display(true);
} else {
    if ($type == 'author') {
        $selecteduser = $DB->get_record('user', array('id' => $template->authorid));
    } else {
        $selecteduser = $DB->get_record('user', array('id' => $template->reviewerid));
    }
    $customdata['selector'] = $selecteduser ? fullname($selecteduser) : '';
}

// Create the form.
$form = new mbst\form\assign(null, $customdata);
$form->set_data($formdata);

// Process the form.
$error = null;
$redirurl = new moodle_url('/course/view.php', array('id' => $courseid));
if ($form->is_cancelled()) {
    redirect($redirurl);
} else if (($data = $form->get_data())) {

    // Extract the selected user - if a user was expected, only save the form if one was selected.
    $userid = null;
    if ($showselector) {
        if ($user = $selector->get_selected_user()) {
            $userid = $user->id;
        } else {
            $error = get_string('mustselectuser', 'block_mbstpl');
        }
    }

    if (!$error) {
        // Save the comments / files.
        $data = file_postupdate_standard_editor($data, 'feedback', $editoropts, $coursecontext);
        $data = file_postupdate_standard_filemanager($data, 'uploadfile', $fileopts, $coursecontext, 'block_mbstpl',
                                                     template::FILEAREA, $template->id);
        if ($type == 'author') {
            mbst\course::assign_author($template, $userid, $data->feedback, $data->feedbackformat);
        } else {
            mbst\course::assign_reviewer($template, $userid, $data->feedback, $data->feedbackformat);
        }

        redirect($redirurl);
    }
}

// Output the page.
/* @var $output block_mbstpl_renderer */
$output = $PAGE->get_renderer('block_mbstpl');

echo $output->header();
echo html_writer::tag('h2', $pagetitle);
echo $output->coursebox($course, $template);

if ($error) {
    echo $output->notification($error);
}

$form->display();
echo $output->footer();
