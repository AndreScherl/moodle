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

require_once(dirname(dirname(__DIR__)) . '/config.php');
require_once($CFG->libdir.'/tablelib.php');

global $PAGE, $OUTPUT, $USER;

use \block_mbstpl AS mbst;

$courseid = required_param('course', PARAM_INT);
$perpage = optional_param('perpage', 5, PARAM_INT);

$coursecontext = context_course::instance($courseid);
$course = get_course($courseid);
$redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));

$template = mbst\dataobj\template::get_from_course($courseid);
if (!$template->fetched) {
    redirect($redirecturl);
}

if (!mbst\perms::can_viewfeedback($template, $coursecontext)) {
    redirect($redirecturl);
}

$thisurl = new moodle_url('/blocks/mbstpl/viewrating.php');
$thisurl->param('course', $courseid);

$PAGE->set_url($thisurl);
$PAGE->set_pagelayout('course');
$PAGE->set_context($coursecontext);

$renderer = mbst\course::get_renderer();
echo $OUTPUT->header();

echo $renderer->coursebox($course, $template, false);

if (is_null($template->rating)) {
    echo html_writer::tag('h3', get_string('norating', 'block_mbstpl'));
} else {
    echo $renderer->rating($template->rating);

    $table = new flexible_table('block_mbstpl_ratings');
    $table->define_columns(array('timecreated', 'rating', 'comment', 'user'));
    $table->define_headers(array(
        get_string('date'),
        get_string('rating', 'block_mbstpl'),
        get_string('feedback', 'block_mbstpl'),
        get_string('user'),
    ));
    $table->define_baseurl($thisurl);
    $table->no_sorting('feedback');
    $table->setup();

    $ufnames = get_all_user_name_fields(true, 'u');

    $select = "SELECT rat.id, rat.rating, rat.comment, rat.timecreated, rat.userid, $ufnames";
    $from = "
    FROM {block_mbstpl_starrating} rat
    JOIN {user} u ON u.id = rat.userid
    ";
    $where = "WHERE rat.templateid = :tid";
    $params = array('tid' => $template->id);
    $sort = $table->get_sql_sort() ? 'ORDER BY '.$table->get_sql_sort() : '';

    $matchcount = $DB->count_records_sql("SELECT COUNT(1) $from $where", $params);
    $table->initialbars(true);
    $table->pagesize($perpage, $matchcount);
    $results = $DB->get_recordset_sql("$select $from $where $sort", $params, $table->get_page_start(), $table->get_page_size());

    foreach($results as $result) {
        $row = array();
        $row[] = userdate($result->timecreated);
        $row[] = $result->rating;
        $row[] = $result->comment;
        $row[] = html_writer::link(new moodle_url('/user/view.php', array('id' => $result->userid)), fullname($result));
        $table->add_data($row);
    }
    $table->finish_html();
}

echo $OUTPUT->footer();
