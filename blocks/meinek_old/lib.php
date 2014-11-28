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
 * Library code used by the meinkurse block
 *
 * @package   block_meinkurse
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('meinek_old_SCHOOL_CAT_DEPTH', 3);

define('meinek_old_OTHER_SCHOOLS', -2);
define('meinek_old_NOT_SCHOOL', -1);

class meinek_old {
    public static $validsort = array('name', 'timecreated', 'timevisited');

    /**
     * @param object $user
     * @return bool|object
     */
    public static function get_main_school($user) {
        global $DB;
        static $myschool = null;

        if (is_null($myschool)) {
            if (empty ($user->institution)) {
                $myschool = false;

            } else {
                $schoolid = $user->institution;
                $myschool = $DB->get_record('course_categories', array('idnumber' => $schoolid,
                                                                       'depth' => meinek_old_SCHOOL_CAT_DEPTH),
                                            'id, name, depth, path');
            }
        }

        return $myschool;
    }

    /**
     * @return object
     */
    public static function get_prefs() {
        $defaultprefs = (object) array(
            'sortby' => 'name',
            'numcourses' => 5,
            'school' => null,
            'sortdir' => 'asc',
            'otherschool' => 0,
        );

        $prefs = get_user_preferences('block_meinek_old_prefs', false);
        if ($prefs) {
            $prefs = unserialize($prefs);
        }
        if (!$prefs || !is_object($prefs)) {
            $prefs = new stdClass();
        }
        foreach ($defaultprefs as $name => $value) {
            if (!isset($prefs->$name)) {
                $prefs->$name = $value;
            }
        }

        return $prefs;
    }

    /**
     * @param object $prefs
     * @param $defaultdir
     */
    public static function set_prefs($prefs, $defaultdir = false) {
        if (!in_array($prefs->sortby, self::$validsort)) {
            $prefs->sortby = 'name';
        }
        if ($defaultdir) {
            if ($prefs->sortby == 'name') {
                $prefs->sortdir = 'asc';
            } else {
                $prefs->sortdir = 'desc';
            }
        }
        if ($prefs->sortdir != 'desc') {
            $prefs->sortdir = 'asc';
        }
        set_user_preference('block_meinek_old_prefs', serialize($prefs));
    }

    /**
     * Returns html of one tab of user results
     * @param object $user
     * @param object $prefs - filter preferences
     * @param array $courses - an array of courses to display
     * @param $schoolid
     * @param $totalcourses
     * @param int $thispage - current page number
     * @return string
     */
    public static function one_tab($user, $prefs, $courses, $schoolid, $totalcourses, $thispage = 1) {
        global $OUTPUT, $PAGE;

        $content = '';

        // Pagination.
        $baseurl = new moodle_url($PAGE->url, array('meinek_old_school' => $schoolid));
        $paginghtml = $OUTPUT->paging_bar($totalcourses, $thispage - 1, $prefs->numcourses, $baseurl, 'meinek_old_page');

        // Query results.
        $modpattern = (object)array(
            'red' => false,
            'list' => array(),
            'count' => 0
        );

        $coursetable = new html_table();
        $coursetable->attributes = array('class' => 'generaltable meinek_oldtable');
        $coursetable->colclasses = array('', 'moreinfo', 'moddesc-hidden');
        $coursetable->data = array();

        $infoicon = $OUTPUT->pix_icon('i/info', 'info');
        $newspan = ' <span class="newtext">' . get_string('new', 'block_meinek_old').'</span>';

        foreach ($courses as $course) {
            $modinfo = get_fast_modinfo($course);

            $coursename = '';

            $coursename .= $OUTPUT->pix_icon('i/course', 'course', 'moodle');
            $attrib = array('class' => 'main');
            if (!$course->visible) {
                $attrib['class'] .= ' dimmed';
            }
            $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
            $courselink = format_string($course->fullname);
            $courselink = html_writer::link($courseurl, $courselink);
            $coursename .= html_writer::tag('h2', $courselink, $attrib);

            $modslist = array(
                'assignments' => clone($modpattern),
                'forums' => clone($modpattern),
                'quizes' => clone($modpattern),
                'resources' => clone($modpattern),
            );

            // Get assignments.
            $modslist = self::assignment_details($user, $course, $modinfo, $modslist);
            $modslist = self::assign_details($user, $course, $modinfo, $modslist);

            // Get forums.
            $modslist = self::forum_details($user, $course, $modinfo, $modslist);

            // Get quizes.
            $modslist = self::quiz_details($user, $course, $modinfo, $modslist);

            // Get resources.
            $modslist = self::resource_details($user, $course, $modinfo, $modslist);

            $modslist['quizes']->type = 'quiz';
            $modslist['forums']->type = 'forum';
            $modslist['assignments']->type = 'assignment';
            $modslist['resources']->type = 'resource';

            $modtable = new html_table();
            $modtable->attributes = array('class' => 'generaltable meinek_old_content');
            $modtable->data = array();
            $coursenew = '';
            foreach ($modslist as $modtype => $typedata) {
                // First row - heading for the activity type.
                $row = array();
                $tmp = $OUTPUT->pix_icon('icon', $modtype, 'mod_' . $typedata->type);
                $tmp .= ' ' . get_string($modtype, 'block_meinek_old');
                $row[] = $tmp;
                if ($typedata->count) {
                    $new = $typedata->count.$newspan;
                    if ($typedata->red) {
                        $new = '<span class="red">' . $new . '</span>';
                    }
                    $row[] = $new;
                    $coursenew = $newspan;
                } else {
                    $row[] = '&nbsp;';
                }
                $row[] = '';
                $modtable->data[] = $row;

                // Second row - the details for the activity type.
                $listhtml = '';
                if (count($typedata->list)) {
                    foreach ($typedata->list as $listitem) {
                        $icon = isset($listitem['icon']) ? $listitem['icon'].' ' : '';
                        $listhtml .= '<p>';
                        $listhtml .= '<span class="title">' . $icon . $listitem['title'] . '</span>';
                        if (count($listitem['desc'])) {
                            $listhtml .= '<br />' . implode('<br />', $listitem['desc']);
                        }
                        $listhtml .= '</p>';
                    }
                } else {
                    $listhtml = '<p>'.get_string('nonewitemssincelastlogin', 'block_meinek_old').'</p>';
                }
                $cell = new html_table_cell($listhtml);
                $cell->colspan = 3;
                $row = new html_table_row(array($cell));
                $modtable->data[] = $row;
            }

            $moddetails = html_writer::table($modtable);

            $coursetable->data[] = array($coursename.$coursenew, $infoicon, $moddetails);
        }

        if (empty($courses)) {
            $tblcontent = get_string('nocourses', 'block_meinek_old');
        } else {
            $tblcontent = html_writer::table($coursetable);
            $tblcontent .= html_writer::tag('div', '', array('class' => "coursecontent meinek_old_content{$schoolid}",
                                                            'style' => 'float:left;'));
            $tblcontent .= html_writer::tag('div', '', array('class' => 'clearer'));
        }

        $content .= html_writer::tag('div', $tblcontent, array('class' => 'coursecontainer'));
        $content .= $paginghtml;

        return $content;
    }

    /**
     * @param int $page
     * @param string $sortby optional
     * @param string $sortdir
     * @param int $numcourses
     * @param int $schoolid
     * @param int $otherschoolid
     * @return string
     */
    public static function output_course_list($page = 1, $sortby = null, $sortdir = null, $numcourses = null,
                                              $schoolid = null, $otherschoolid = null) {
        global $USER;

        $prefs = self::get_prefs();
        if (!is_null($sortby) || !is_null($numcourses) || !is_null($schoolid) || !is_null($otherschoolid) || !is_null($sortdir)) {
            $defaultdir = false;
            if (!is_null($sortby)) {
                $prefs->sortby = $sortby;
                $defaultdir = true;
            }
            if (!is_null($sortdir)) {
                $prefs->sortdir = $sortdir;
                $defaultdir = false;
            }
            if (!is_null($numcourses)) {
                $prefs->numcourses = $numcourses;
            }
            if (!is_null($schoolid)) {
                $prefs->school = $schoolid;
            }
            if (!is_null($otherschoolid)) {
                $prefs->otherschool = $otherschoolid;
            }
            self::set_prefs($prefs, $defaultdir);
        }

        $mycourses = self::get_my_courses($prefs->sortby, $prefs->sortdir, $prefs->numcourses, $prefs->school, $page,
                                          $prefs->otherschool);

        if (!isset($mycourses[$prefs->school])) {
            return array(null, '');
        }

        $school = $mycourses[$prefs->school];
        return array(
            $school->name,
            self::one_tab($USER, $prefs, $school->courses, $school->id, $school->coursecount, $school->page)
        );
    }

    /**
     * Gather the details for the old assignment module
     *
     * @param object $user
     * @param object $course
     * @param course_modinfo $modinfo
     * @param object[] $modslist
     * @return object[] the updated $modslist
     */
    protected static function assignment_details($user, $course, $modinfo, $modslist) {
        global $DB, $CFG;

        if (!$DB->get_field('modules', 'visible', array('name' => 'assignment'))) {
            return $modslist; // Assignment module is disabled - nothing to add here.
        }

        $mods = get_coursemodules_in_course('assignment', $course->id, 'm.timedue');
        $assignmentids = array();
        foreach ($mods as $mod) {
            $assignmentids[] = $mod->instance;
        }
        if ($assignmentids && $course->istrainer) {
            // Gather submission data for all assignments.
            list($asql, $params) = $DB->get_in_or_equal($assignmentids, SQL_PARAMS_NAMED);
            $sql = "SELECT s.assignment, COUNT(s.id) AS c
                          FROM {assignment_submissions} s
                          JOIN {user_enrolments} ue ON ue.userid = s.userid
                          JOIN {enrol} e ON ue.enrolid = e.id AND e.courseid = :courseid
                         WHERE s.assignment $asql
                         GROUP BY s.assignment";
            $params['courseid'] = $course->id;
            $totalsubmissions = $DB->get_records_sql($sql, $params);
            $sql = "SELECT s.assignment, COUNT(s.id) AS c
                          FROM {assignment_submissions} s
                          JOIN {user_enrolments} ue ON ue.userid = s.userid
                          JOIN {enrol} e ON ue.enrolid = e.id AND e.courseid = :courseid
                         WHERE s.assignment $asql AND s.grade = -1
                         GROUP BY s.assignment";
            $ungradedsubmissions = $DB->get_records_sql($sql, $params);
        }
        foreach ($mods as $mod) {
            $isnew = false;
            $cms = $modinfo->get_cm($mod->id);
            if (!$cms->uservisible) {
                continue;
            }
            $title = '<a href="' . $CFG->wwwroot . '/mod/assignment/view.php?id=' . $mod->id . '">' . s($mod->name) . '</a>';
            $desc = array();
            $count = 0;
            if ($course->istrainer) {
                // Count submissions.
                $submissions = isset($totalsubmissions[$mod->instance]) ? $totalsubmissions[$mod->instance]->c : 0;
                $desc[] = get_string('numsubmissions', 'block_meinek_old') . ': ' . $submissions;

                // Count ungraded submissions.
                $ungradeds = isset($ungradedsubmissions[$mod->instance]) ? $ungradedsubmissions[$mod->instance]->c : 0;
                $gradeds = $submissions - $ungradeds;
                $desc[] = '<a href="' . $CFG->wwwroot . '/mod/assignment/submissions.php?id=' . $mod->id . '">' .
                    get_string('numgradedsubmissions', 'block_meinek_old') . ': ' . $gradeds . '</a>';
                if ($ungradeds) {
                    $isnew = true;
                }
                if ($mod->timedue > time()) {
                    $isnew = true;
                }
                $count = $ungradeds;
            } else {
                $submissions = $DB->get_records('assignment_submissions',
                                                array('assignment' => $mod->instance, 'userid' => $user->id));
                $gradetimepercent = self::grade_timepercent($course, 'mod', 'assignment', $mod->instance, $user->id);
                $submitted = count($submissions);
                if (!$submitted) {
                    if ($mod->timedue > 0) {
                        $isnew = true;
                        if ($mod->timedue < time()) {
                            $modslist['assignments']->red = true;
                        }
                    }
                }

                // If I haven't seen the (latest) grade, mark as new.
                if ($gradetimepercent && $gradetimepercent->date) {
                    // Get the date I viewed the module.
                    $latestviewrecs = $DB->get_records('log', array('userid' => $user->id, 'cmid' => $mod->id, 'action' => 'view'),
                                                       'time DESC', 'time', 0, 1);
                    if (count($latestviewrecs)) {
                        $lastviewrec = array_shift($latestviewrecs);
                        $lastview = $lastviewrec->time;
                        if ($lastview < $gradetimepercent->date) {
                            $isnew = true;
                        }
                    }
                }

                if ($mod->timedue > 0) {
                    $desc[] = get_string('deadline', 'block_meinek_old') . ': ' . userdate($mod->timedue);
                }

                if ($submitted) {
                    $submission = array_shift($submissions);
                    $desc[] = get_string('submitted', 'block_meinek_old') . ': ' . userdate($submission->timecreated);
                }

                if ($gradetimepercent) {
                    $desc[] = get_string('grade', 'block_meinek_old') . ': ' . $gradetimepercent->grade;
                }

                if ($isnew) {
                    $count = 1;
                }
            }

            if ($isnew) {
                $modslist['assignments']->list[] = array(
                    'title' => $title,
                    'desc' => $desc,
                );
                $modslist['assignments']->count += $count;
            }
        }

        return $modslist;
    }

    /**
     * Gather the details for the old assignment module
     *
     * @param object $user
     * @param object $course
     * @param course_modinfo $modinfo
     * @param object[] $modslist
     * @return object[] the updated $modslist
     */
    protected static function assign_details($user, $course, $modinfo, $modslist) {
        global $DB;

        if (!$DB->get_field('modules', 'visible', array('name' => 'assign'))) {
            return $modslist; // Assignment module is disabled - nothing to add here.
        }

        $mods = get_coursemodules_in_course('assign', $course->id, 'm.duedate');
        $assignids = array();
        foreach ($mods as $mod) {
            $assignids[] = $mod->instance;
        }
        if ($assignids && $course->istrainer) {
            // Gather submission data for all assignments.

            list($asql, $params) = $DB->get_in_or_equal($assignids, SQL_PARAMS_NAMED);
            $sql = "SELECT s.assignment, COUNT(s.id) AS c
                          FROM {assign_submission} s
                          JOIN {user_enrolments} ue ON ue.userid = s.userid
                          JOIN {enrol} e ON ue.enrolid = e.id AND e.courseid = :courseid
                         WHERE s.assignment $asql
                         GROUP BY s.assignment";
            $params['courseid'] = $course->id;
            $totalsubmissions = $DB->get_records_sql($sql, $params);
            $sql = "SELECT s.assignment, COUNT(s.id) AS c
                          FROM {assign_submission} s
                          LEFT JOIN {assign_grades} g ON g.assignment = s.assignment AND g.userid = s.userid
                                                     AND g.attemptnumber = s.attemptnumber
                          JOIN {user_enrolments} ue ON ue.userid = s.userid
                          JOIN {enrol} e ON ue.enrolid = e.id AND e.courseid = :courseid
                         WHERE s.assignment $asql AND g.grade IS NULL
                         GROUP BY s.assignment";
            $ungradedsubmissions = $DB->get_records_sql($sql, $params);
        }
        foreach ($mods as $mod) {
            $isnew = false;
            $cms = $modinfo->get_cm($mod->id);
            if (!$cms->uservisible) {
                continue;
            }
            $title = html_writer::link($cms->url, format_string($mod->name));
            $desc = array();
            $count = 0;
            if ($course->istrainer) {
                // Count submissions.
                $submissions = isset($totalsubmissions[$mod->instance]) ? $totalsubmissions[$mod->instance]->c : 0;
                $desc[] = get_string('numsubmissions', 'block_meinek_old') . ': ' . $submissions;

                // Count ungraded submissions.
                $ungradeds = isset($ungradedsubmissions[$mod->instance]) ? $ungradedsubmissions[$mod->instance]->c : 0;
                $gradeds = $submissions - $ungradeds;
                $submissionurl = new moodle_url('/mod/assign/view.php', array('id' => $mod->id, 'action' => 'grading'));
                $desc[] = html_writer::link($submissionurl,
                                            get_string('numgradedsubmissions', 'block_meinek_old') . ': ' . $gradeds);
                if ($ungradeds) {
                    $isnew = true;
                }
                if ($mod->duedate > time()) {
                    $isnew = true;
                }
                $count = $ungradeds;
            } else {
                $submissions = $DB->get_records('assign_submission', array('assignment' => $mod->instance, 'userid' => $user->id));
                $gradetimepercent = self::grade_timepercent($course, 'mod', 'assign', $mod->instance, $user->id);
                $submitted = count($submissions);
                if (!$submitted) {
                    if ($mod->duedate > 0) {
                        $isnew = true;
                        if ($mod->duedate < time()) {
                            $modslist['assignments']->red = true;
                        }
                    }
                }

                // If I haven't seen the (latest) grade, mark as new.
                if ($gradetimepercent && $gradetimepercent->date) {
                    // Get the date I viewed the module.
                    $latestviewrecs = $DB->get_records('log', array('userid' => $user->id, 'cmid' => $mod->id, 'action' => 'view'),
                                                       'time DESC', 'time', 0, 1);
                    if (count($latestviewrecs)) {
                        $lastviewrec = array_shift($latestviewrecs);
                        $lastview = $lastviewrec->time;
                        if ($lastview < $gradetimepercent->date) {
                            $isnew = true;
                        }
                    }
                }

                if ($mod->duedate > 0) {
                    $desc[] = get_string('deadline', 'block_meinek_old') . ': ' . userdate($mod->duedate);
                }

                if ($submitted) {
                    $submission = array_shift($submissions);
                    $desc[] = get_string('submitted', 'block_meinek_old') . ': ' . userdate($submission->timecreated);
                }

                if ($gradetimepercent) {
                    $desc[] = get_string('grade', 'block_meinek_old') . ': ' . $gradetimepercent->grade;
                }

                if ($isnew) {
                    $count = 1;
                }
            }

            if ($isnew) {
                $modslist['assignments']->list[] = array(
                    'title' => $title,
                    'desc' => $desc,
                );
                $modslist['assignments']->count += $count;
            }
        }

        return $modslist;
    }

    /**
     * @param object $user
     * @param object $course
     * @param course_modinfo $modinfo
     * @param object[] $modslist
     * @return object[] updated $modslist
     */
    protected static function forum_details($user, $course, $modinfo, $modslist) {
        global $DB;

        if (!$DB->get_field('modules', 'visible', array('name' => 'forum'))) {
            return $modslist; // Forum module is disabled - nothing to add here.
        }

        $mods = get_coursemodules_in_course('forum', $course->id);
        foreach ($mods as $mod) {
            $isnew = false;
            $cms = $modinfo->get_cm($mod->id);
            if (!$cms->uservisible) {
                continue;
            }
            $title = html_writer::link($cms->url, format_string($mod->name));
            $desc = array();

            // Depending on group mode, add group condition.
            $andgroup = '';
            $groupmode = groups_get_activity_groupmode($mod);
            $context = context_module::instance($mod->id);
            if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
                $andgroup = "
                    AND EXISTS (
                        SELECT * FROM {groups_members} gm WHERE gm.groupid = fd.groupid AND gm.userid = :userid2
                    )
                    ";
            }

            $sql = "
                    SELECT fp.id, fp.discussion, fp.subject FROM {forum} f
                    JOIN {forum_discussions} fd ON (fd.forum = f.id)
                    JOIN {forum_posts} fp ON (fp.discussion = fd.id AND fp.created > :lastlogin AND fp.userid != :userid)
                    WHERE f.id = :forumid
                    $andgroup
                    ORDER BY fp.modified DESC
                    ";
            $params = array(
                'forumid' => $mod->instance, 'lastlogin' => $user->lastlogin, 'userid' => $user->id,
                'userid2' => $user->id
            );
            $posts = $DB->get_records_sql($sql, $params);
            $posted = count($posts);
            if ($posted) {
                $isnew = true;

                // Get latest post.
                $desc[] = $posted.' '.get_string('posts', 'block_meinek_old').' '.get_string('sincelastlogin', 'block_meinek_old');
                $link = new moodle_url('/mod/forum/discuss.php');
                foreach ($posts as $post) {
                    $link->param('d', $post->discussion);
                    $link->set_anchor('p'.$post->id);
                    $msg = format_string($post->subject);
                    if (strlen($msg) > 40) {
                        $msg = substr($msg, 0, 40).'&hellip;';
                    }
                    $desc[] = html_writer::link($link, $msg);
                }

            } else {
                $desc[] = $posted.' '.get_string('posts', 'block_meinek_old').' '
                    .get_string('sincelastlogin', 'block_meinek_old');
            }

            if ($isnew) {
                $modslist['forums']->list[] = array(
                    'title' => $title,
                    'desc' => $desc,
                );
                $modslist['forums']->count += $posted;
            }
        }

        return $modslist;
    }

    /**
     * @param object $user
     * @param object $course
     * @param course_modinfo $modinfo
     * @param object[] $modslist
     * @return object[] updated $modslist
     */
    protected static function quiz_details($user, $course, $modinfo, $modslist) {
        global $CFG, $DB;

        if (!$DB->get_field('modules', 'visible', array('name' => 'quiz'))) {
            return $modslist; // Quiz module is disabled - nothing to add here.
        }

        $mods = get_coursemodules_in_course('quiz', $course->id, 'm.timeclose');
        $sql = "SELECT q.id
                      FROM {quiz} q
                      JOIN {quiz_slots} qi ON qi.quizid = q.id
                      JOIN {question} qs ON qi.questionid = qs.id
                     WHERE q.course = ? AND qs.qtype = 'essay'";
        $essayquizzes = $DB->get_fieldset_sql($sql, array($course->id));
        foreach ($mods as $mod) {
            $isnew = false;
            $cms = $modinfo->get_cm($mod->id);
            if (!$cms->uservisible) {
                continue;
            }
            $url = $CFG->wwwroot.'/mod/quiz/view.php?id='.$mod->id;
            $desc = array();
            $count = 0;
            if ($course->istrainer) {
                $sql = "
                        SELECT COUNT(DISTINCT qa.id)
                        FROM {quiz_attempts} qa
                        JOIN {user_enrolments} ue ON ue.userid = qa.userid
                        JOIN {enrol} e ON ue.enrolid = e.id AND e.courseid = :courseid
                        WHERE qa.quiz = :quiz
                        AND timefinish > 0
                        ";
                $params = array('courseid' => $course->id, 'quiz' => $mod->instance);
                $attempts = $DB->count_records_sql($sql, $params);
                $desc[] = get_string('numtests', 'block_meinek_old').': '.$attempts;
                if ($attempts) {
                    $isnew = ($mod->timeclose > time());
                    $isnew = $isnew || ($mod->timeclose == 0 && in_array($mod->instance, $essayquizzes));
                }
                $count = $attempts;
            } else {
                $gradetimepercent = self::grade_timepercent($course, 'mod', 'quiz', $mod->instance, $user->id);
                if ($gradetimepercent) {
                    $url = $CFG->wwwroot.'/grade/report/user/index.php?id='.$course->id;
                }
                $attempted = $DB->count_records('quiz_attempts', array(
                                                                      'quiz' => $mod->instance, 'userid' => $user->id
                                                                 ));
                if (!$attempted) {
                    $isnew = true;
                    $count = 1;
                    if ($mod->timeclose > 0) {
                        if ($mod->timeclose < time()) {
                            $modslist['quizes']->red = true;
                        }
                    }
                }

                // If the quiz has been marked since last visit, and the grade is visible to the user, also mark as new.
                if ($gradetimepercent && !$isnew) {
                    $sql = "
                            SELECT qa.timemodified
                            FROM {quiz_attempts} qa
                            WHERE qa.quiz = :quiz
                            AND qa.userid = :userid
                            AND qa.sumgrades IS NOT NULL
                            ORDER BY qa.timemodified DESC
                            LIMIT 1
                            ";
                    $params = array('quiz' => $mod->instance, 'userid' => $user->id);
                    $markedtime = $DB->get_field_sql($sql, $params);
                    if ($markedtime && $markedtime > $user->lastlogin) {
                        $isnew = true;
                        $count = 1;
                    }
                }

                if ($mod->timeclose > 0) {
                    $desc[] = get_string('deadline', 'block_meinek_old').': '.userdate($mod->timeclose);
                }

                // Get last attempt date.
                $sql = "
                        SELECT qa.timefinish
                        FROM {quiz_attempts} qa
                        WHERE qa.quiz = :quiz
                        AND qa.userid = :userid
                        ORDER BY qa.timemodified DESC
                        LIMIT 1
                        ";
                $params = array('quiz' => $mod->instance, 'userid' => $user->id);
                if ($timefinish = $DB->get_field_sql($sql, $params)) {
                    $desc[] = get_string('submitted', 'block_meinek_old').': '.userdate($timefinish);
                }

                if ($gradetimepercent) {
                    $desc[] = get_string('grade', 'block_meinek_old').': '.$gradetimepercent->grade;
                }
            }
            $title = '<a href="'.$url.'">'.s($mod->name).'</a>';

            if ($isnew) {
                $modslist['quizes']->list[] = array(
                    'title' => $title,
                    'desc' => $desc,
                );
                $modslist['quizes']->count += $count;
            }
        }
        return $modslist;
    }

    /**
     * @param object $user
     * @param object $course
     * @param course_modinfo $modinfo
     * @param object[] $modslist
     * @return object[] updated $modslist
     */
    protected static function resource_details($user, $course, $modinfo, $modslist) {
        global $OUTPUT, $DB;

        static $strfile = null, $urlicon = null, $pageicon = null, $foldericon = null;
        if (is_null($strfile)) {
            $urlicon = $OUTPUT->pix_icon('icon', get_string('pluginname', 'mod_url'), 'mod_url');
            $pageicon = $OUTPUT->pix_icon('icon', get_string('pluginname', 'mod_page'), 'mod_page');
            $foldericon = $OUTPUT->pix_icon('icon', get_string('pluginname', 'mod_folder'), 'mod_folder');
            $strfile = get_string('pluginname', 'mod_resource');
        }

        $enabled = $DB->get_records_menu('modules', array(), '', 'name, visible');

        if ($enabled['resource']) {
            $mods = get_coursemodules_in_course('resource', $course->id, 'm.timemodified');
            foreach ($mods as $mod) {
                $cms = $modinfo->get_cm($mod->id);
                if (!$cms->uservisible) {
                    continue;
                }

                $title = html_writer::link($cms->url, format_string($mod->name));
                if ($mod->timemodified > $user->lastlogin) {
                    $modslist['resources']->list[] = array(
                        'title' => $title,
                        'desc' => array(),
                        'icon' => $OUTPUT->pix_icon($cms->icon, $strfile, $cms->iconcomponent)
                    );
                    $modslist['resources']->count++;
                }
            }
        }

        if ($enabled['folder']) {
            // Folder type resource
            // Get a list of all the 'folder' resources in this course, with their 'lastmodified' time and the
            // largest 'timemodified' field for their included files - note this will skip folders with not files in them.
            $sql = "SELECT cm.id, i.name, i.timemodified, MAX(f.timemodified) AS filemodified
                      FROM {folder} i
                      JOIN {course_modules} cm ON cm.instance = i.id
                      JOIN {modules} m ON m.id = cm.module AND m.name = 'folder'
                      JOIN {context} cx ON cx.instanceid = cm.id AND cx.contextlevel = :contextmodule
                      JOIN {files} f ON f.contextid = cx.id AND f.component = 'mod_folder' AND f.filearea = 'content'
                                     AND f.filename <> '.' AND f.itemid = 0
                     WHERE i.course = :course
                     GROUP BY i.id, i.name, i.timemodified";
            $params = array('course' => $course->id, 'contextmodule' => CONTEXT_MODULE);
            $mods = $DB->get_records_sql($sql, $params);
            foreach ($mods as $mod) {
                $cms = $modinfo->get_cm($mod->id);
                if (!$cms->uservisible) {
                    continue;
                }

                $title = html_writer::link($cms->url, format_string($mod->name));
                $modified = ($mod->timemodified > $user->lastlogin);
                $modified = $modified || ($mod->filemodified > $user->lastlogin);
                if ($modified) {
                    $modslist['resources']->list[] = array(
                        'title' => $title,
                        'desc' => array(),
                        'icon' => $foldericon
                    );
                    $modslist['resources']->count++;
                }
            }
        }

        // Page type resource.
        if ($enabled['page']) {
            $mods = get_coursemodules_in_course('page', $course->id, 'm.timemodified');
            foreach ($mods as $mod) {
                $cms = $modinfo->get_cm($mod->id);
                if (!$cms->uservisible) {
                    continue;
                }

                $title = html_writer::link($cms->url, format_string($mod->name));
                if ($mod->timemodified > $user->lastlogin) {
                    $modslist['resources']->list[] = array(
                        'title' => $title,
                        'desc' => array(),
                        'icon' => $pageicon
                    );
                    $modslist['resources']->count++;
                }
            }
        }

        // URL type resource.
        if ($enabled['url']) {
            $mods = get_coursemodules_in_course('url', $course->id, 'm.timemodified');
            foreach ($mods as $mod) {
                $cms = $modinfo->get_cm($mod->id);
                if (!$cms->uservisible) {
                    continue;
                }

                $title = html_writer::link($cms->url, format_string($mod->name));
                if ($mod->timemodified > $user->lastlogin) {
                    $modslist['resources']->list[] = array(
                        'title' => $title,
                        'desc' => array(),
                        'icon' => $urlicon
                    );
                    $modslist['resources']->count++;
                }
            }
        }

        return $modslist;
    }

    /**
     * Returns an object with grade time and grade in percentage form for a module a given user
     * @param object $course, a course object containing id and showgrades
     * @param string $itemtype 'mod', 'block'
     * @param string $itemmodule 'forum, 'quiz', etc.
     * @param int $iteminstance id of the item module
     * @param int $userid
     * @param bool $showhidden
     * @return bool|object
     */
    private static function grade_timepercent($course, $itemtype, $itemmodule, $iteminstance, $userid, $showhidden=false) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/gradelib.php');

        $grade = grade_get_grades($course->id, $itemtype, $itemmodule, $iteminstance, $userid);
        $item = array_shift($grade->items);
        $usergrade = array_shift($item->grades);
        if (!$showhidden) {
            if ($usergrade->hidden) {
                return false;
            }
            if (!$course->showgrades) {
                return false;
            }
        }
        if (is_numeric($usergrade->grade)) {
            $returngrade = ($usergrade->grade / $item->grademax * 100) . '%';
            $returndate = $usergrade->dategraded;
            return (object) array('grade' => $returngrade, 'date' => $returndate);
        }
        return false;
    }


    /**
     * Get a list of all the courses the user is in, grouped by school
     *
     * @param string $sortby the field to sort by (timecreated, timevisited, name)
     * @param string $sortdir direction for sorting (asc, desc)
     * @param int $perpage the number of courses per page
     * @param int $schoolid the currently selected school
     * @param int $page the current page (in the selected school), may be updated
     * @param int $otherschoolid
     * @return array
     */
    public static function get_my_courses($sortby, $sortdir, $perpage, $schoolid, $page, $otherschoolid) {
        global $USER, $DB;
        // Get all courses, grouped by school (3rd-level category).

        // Guest account does not have any courses.
        if (isguestuser() or !isloggedin()) {
            return array();
        }

        $params = array();

        // Sorting.
        if ($sortdir != 'desc') {
            $sortdir = ' ASC';
        } else {
            $sortdir = ' DESC';
        }

        if ($sortby == 'timecreated') {
            $sort = 'c.timecreated'.$sortdir;
        } else if ($sortby == 'timevisited') {
            $sort = "(SELECT MAX(time) FROM {log} lg WHERE lg.course = c.id AND lg.action = 'view' AND lg.userid = :userid2)".$sortdir;
            $params['userid2'] = $USER->id;
        } else {
            $sort = 'c.fullname'.$sortdir;
        }
        $sort = 'ORDER BY '.$sort;

        $fields = array('id', 'category', 'sortorder',
                        'shortname', 'fullname', 'idnumber',
                        'startdate', 'visible', 'showgrades',
                        'groupmode', 'groupmodeforce');

        // Exclude the front page from the courses list.
        $wheres = array('c.id <> :siteid');
        $params['siteid'] = SITEID;

        if (isset($USER->loginascontext) and $USER->loginascontext->contextlevel == CONTEXT_COURSE) {
            // List _only_ this course - anything else is asking for trouble...
            $wheres[] = "courseid = :loginas";
            $params['loginas'] = $USER->loginascontext->instanceid;
        }

        $coursefields = 'c.' . join(',c.', $fields);
        $wheres = implode(" AND ", $wheres);

        // Note: we can not use DISTINCT + text fields due to Oracle and MS limitations, that is why we have the subselect there.
        $sql = "SELECT $coursefields, ca.name AS catname, ca.depth AS catdepth, ca.path AS catpath
                  FROM {course} c
                  JOIN (SELECT DISTINCT e.courseid
                          FROM {enrol} e
                          JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = :userid)
                         WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1
                           AND (ue.timeend = 0 OR ue.timeend > :now2)
                       ) en ON (en.courseid = c.id)
                  JOIN {course_categories} ca ON ca.id = c.category
                 WHERE $wheres
              $sort";
        $params['userid'] = $USER->id;
        $params['userid3'] = $params['userid'];
        $params['active'] = ENROL_USER_ACTIVE;
        $params['enabled'] = ENROL_INSTANCE_ENABLED;
        $params['now1'] = time();
        $params['now2'] = $params['now1'];

        $courses = $DB->get_records_sql($sql, $params);

        $myschool = self::get_main_school($USER);
        $schools = array();
        if ($myschool) {
            // Make sure the user's own school is first in the list.
            $schools[$myschool->id] = (object) array(
                'id' => $myschool->id,
                'name' => $myschool->name,
                'courses' => array(),
                'coursecount' => 0,
                'page' => 1,
                'schools' => array(),
            );
        }
        $schools[meinek_old_OTHER_SCHOOLS] = (object) array(
            'id' => meinek_old_OTHER_SCHOOLS,
            'name' => null,
            'courses' => array(),
            'coursecount' => 0,
            'page' => 1,
            'schools' => array(0 => get_string('allotherschools', 'block_meinek_old')),
        );
        if (!$otherschoolid) {
            $schools[meinek_old_OTHER_SCHOOLS]->name = get_string('otherschools', 'block_meinek_old');
        }

        // Preload contexts and check visibility.
        foreach ($courses as $id => $course) {
            $context = context_course::instance($id);
            if (!$course->visible) {
                if (!$context) {
                    unset($courses[$id]);
                    continue;
                }
                if (!has_capability('moodle/course:viewhiddencourses', $context)) {
                    unset($courses[$id]);
                    continue;
                }
            }
            $course->istrainer = $context && has_capability('block/meinek_old:viewtrainertab', $context);
            if ($course->catdepth < meinek_old_SCHOOL_CAT_DEPTH) {
                // Course does not appear to be within a school - gather all such courses together into a 'misc' category.
                $course->category = meinek_old_NOT_SCHOOL;
            } else {
                if ($course->catdepth > meinek_old_SCHOOL_CAT_DEPTH) {
                    // Course is within a subcategory of a school - find the school category.
                    $path = explode('/', $course->catpath);
                    if (count($path) < (meinek_old_SCHOOL_CAT_DEPTH + 1)) {
                        $course->category = meinek_old_NOT_SCHOOL;
                        debugging("Found bad category information - id: {$course->category}; depth: {$course->catdepth};'.
                        ' path: {$course->catpath}; name: {$course->catname}");
                    } else {
                        $course->category = $path[meinek_old_SCHOOL_CAT_DEPTH];
                        $course->catname = null; // As this was the name of the direct category, not of the school.
                    }
                }
            }

            if ($course->category != meinek_old_NOT_SCHOOL && (!$myschool || $course->category != $myschool->id)) {
                $schools[meinek_old_OTHER_SCHOOLS]->schools[$course->category] = $course->catname; // For drop-down list.
                if (!$otherschoolid) {
                    $course->catname = null; // We already know the name, so don't need it here.
                } else if ($otherschoolid != $course->category) {
                    continue; // Skip this course, as it isn't in the selected school.
                }
                $course->category = meinek_old_OTHER_SCHOOLS;
            }

            if (empty($schools[$course->category])) {
                // First course we've found in this school - set up the school details.
                $school = new stdClass();
                $school->id = $course->category;
                if ($course->category == meinek_old_NOT_SCHOOL) {
                    $school->name = get_string('notinschool', 'block_meinek_old');
                } else {
                    $school->name = $course->catname;
                }
                $school->schools = array();
                $school->courses = array();
                $school->coursecount = 0;
                $school->page = 1;
                $schools[$course->category] = $school;
            }
            if (is_null($schools[$course->category]->name) && !is_null($course->catname)) {
                $schools[$course->category]->name = $course->catname; // Fill in the school name if we now know it.
            }
            $schools[$course->category]->coursecount++;
            if ($schoolid != $course->category) {
                if ($schools[$course->category]->coursecount > $perpage) {
                    continue; // Other than the currently selected school, only keep the first 'perpage'.
                }
            }
            $schools[$course->category]->courses[] = $course; // Add the course to the list for the school.
        }

        // Now deal with the paging of the currently selected school.
        if (!empty($schools[$schoolid])) {
            $firstcourse = $perpage * ($page - 1);
            if ($firstcourse > $schools[$schoolid]->coursecount) {
                $page = 1;
                $firstcourse = 0;
            }
            // Only include the courses for the current page.
            $schools[$schoolid]->courses = array_slice($schools[$schoolid]->courses, $firstcourse, $perpage, true);
            $schools[$schoolid]->page = $page;
        }

        // Gather any missing school names (for courses that were in subcategories).
        $catids = array();
        foreach ($schools as $school) {
            if (is_null($school->name)) {
                $catids[$school->id] = $school->id;
            }
        }
        unset($catids[meinek_old_OTHER_SCHOOLS]);
        if (is_null($schools[meinek_old_OTHER_SCHOOLS]->name)) {
            $catids[$otherschoolid] = $otherschoolid;
        }
        foreach ($schools[meinek_old_OTHER_SCHOOLS]->schools as $id => $name) {
            if (is_null($name)) {
                $catids[$id] = $id;
            }
        }
        if (!empty($catids)) {
            $catnames = $DB->get_records_list('course_categories', 'id', $catids, '', 'id, name');
            foreach ($schools as $school) {
                if (isset($catnames[$school->id])) {
                    $school->name = $catnames[$school->id]->name;
                }
            }
            if (isset($catnames[$otherschoolid])) {
                $schools[meinek_old_OTHER_SCHOOLS]->name = $catnames[$otherschoolid]->name;
            }
            foreach ($schools[meinek_old_OTHER_SCHOOLS]->schools as $id => $name) {
                if (isset($catnames[$id])) {
                    $schools[meinek_old_OTHER_SCHOOLS]->schools[$id] = $catnames[$id]->name;
                }
            }
        }

        // Tidy up the 'other schools' tab if there are 0 or 1 other schools to display.
        $numotherschools = count($schools[meinek_old_OTHER_SCHOOLS]->schools) - 1;
        if ($numotherschools == 0) {
            unset($schools[meinek_old_OTHER_SCHOOLS]);
        } else if ($numotherschools == 1) {
            $onlyotherschool = array_pop($schools[meinek_old_OTHER_SCHOOLS]->schools);
            $schools[meinek_old_OTHER_SCHOOLS]->name = $onlyotherschool;
        }

        // Clear the 'otherschool' preference if it is invalid (eg if the user has been unenroled from all courses in that school).
        if ($otherschoolid && empty($schools[meinek_old_OTHER_SCHOOLS]->courses)) {
            $prefs = self::get_prefs();
            $prefs->otherschool = 0;
            self::set_prefs($prefs);
            return self::get_my_courses($sortby, $sortdir, $perpage, $schoolid, $page, 0);
        }

        // Move the 'Not in a school' list to the end of the schools.
        if (array_key_exists(meinek_old_NOT_SCHOOL, $schools)) {
            $noschool = $schools[meinek_old_NOT_SCHOOL];
            unset($schools[meinek_old_NOT_SCHOOL]);
            $schools[meinek_old_NOT_SCHOOL] = $noschool;
        }

        return $schools;
    }
}

