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
 * Course overview block
 *
 * Currently, just a copy-and-paste from the old My Moodle.
 *
 * @package   blocks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/weblib.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/blocks/meinekurse/lib.php');

class block_meinekurse extends block_base {

    protected static $validsort = array('name', 'timecreated', 'timevisited');

    /**
     * block initializations
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_meinekurse');
    }

    public function instance_can_be_docked() {
        return false;
    }

    public function instance_can_be_hidden() {
        return false;
    }

    public function instance_can_be_collapsed() {
        return false;
    }

    /**
     * block contents
     *
     * @return object
     */
    public function get_content() {
        global $USER, $PAGE;

        $PAGE->requires->js('/blocks/meinekurse/javascript/jquery.js');
        $PAGE->requires->js('/blocks/meinekurse/javascript/jqueryui.js');
        $PAGE->requires->js('/blocks/meinekurse/javascript/meinekurse.js');

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $content = '';

        //Handle submitted / saved data
        $prefs = meinekurse_get_prefs();
        if ($sortby = optional_param('meinekurse_sortby', null, PARAM_TEXT)) {
            /*if ($prefs->sortby == $sortby) {
                $prefs->sortdir = ($prefs->sortdir == 'asc') ? 'desc' : 'asc';
            } else*/ {
                $prefs->sortby = $sortby;
                if ($sortby == 'name') {
                    $prefs->sortdir = 'asc';
                } else {
                    $prefs->sortdir = 'desc';
                }
            }
        }
        if ($numcourses = optional_param('meinekurse_numcourses', null, PARAM_INT)) {
            $prefs->numcourses = $numcourses;
        }
        if (!is_null($school = optional_param('meinekurse_school', null, PARAM_INT))) {
            $prefs->school = $school;
        }
        if (!in_array($prefs->sortby, self::$validsort)) {
            $prefs->sortby = 'name';
        }
        meinekurse_set_prefs($prefs);

        $pagenum = optional_param('meinekurse_page', 0, PARAM_INT) + 1;

        //Get courses:
        $mycourses = $this->get_my_courses($prefs->sortby, $prefs->sortdir, $prefs->numcourses, $prefs->school, $pagenum);

        $starttab = 0;
        $tabnum = 0;
        foreach ($mycourses as $school) {
            if ($prefs->school == $school->id) {
                $starttab = $tabnum;
                break;
            }
            $tabnum++;
        }

        $content .= '<script type="text/javascript">var starttab = '.$starttab.';</script>';

        //Tabs
        $content .= '<div class="mycoursestabs">';

        // Tab headings.
        $content .= '<ul>';
        foreach ($mycourses as $school) {
            $tab = html_writer::link("#school{$school->id}tab", format_string($school->name));
            $tab = html_writer::tag('li', $tab, array('class' => 'block'));
            $content .= $tab;
        }
        $content .= '</ul>';

        // Sorting icons.
        $baseurl = new moodle_url($PAGE->url);
        //$content .= $this->sorting_icons($baseurl, $prefs->sortby);

        // Tab contents.
        foreach ($mycourses as $school) {
            $tab = $this->sorting_form($baseurl, $prefs->sortby, $numcourses);
            $tab .= $this->one_tab($USER, $prefs, $school->courses, $school->id, $school->coursecount, $school->page);
            $content .= html_writer::tag('div', $tab, array('id' => "school{$school->id}tab"));
        }

        $content .= '</div>';

        $this->content->text = $content;

        return $this->content;
    }

    /*
     * Returns html of one tab of user results
     * @param object $user
     * @param object $prefs - filter preferences
     * @param array $courses - an array of courses to display
     * @param bool $istrainer - whether it's a trainer tab or not
     * @param string $tabname
     * @param int $totalpages - total pages for this tab
     * @param int $thispage - current page number
     */
    private function one_tab($user, $prefs, $courses, $schoolid, $totalcourses, $thispage = 1) {
        global $CFG, $OUTPUT, $PAGE;

        $content = '';

        if (empty($courses)) {
            $content .= get_string('nocourses', 'block_meinekurse');
        }

        //Pagination
        $baseurl = new moodle_url($PAGE->url, array('meinekurse_school' => $schoolid));
        $paginghtml = $OUTPUT->paging_bar($totalcourses, $thispage - 1, $prefs->numcourses, $baseurl, 'meinekurse_page');

        //Query results
        $modpattern = (object)array(
            'red' => false,
            'list' => array(),
            'count' => 0
        );

        $coursetable = new html_table();
        $coursetable->attributes = array('class' => 'generaltable meinekursetable');
        $coursetable->colclasses = array('', 'moreinfo', 'moddesc-hidden');
        $coursetable->data = array();

        $infoicon = $OUTPUT->pix_icon('i/info', 'info');
        $newspan = ' <span class="newtext">' . get_string('new', 'block_meinekurse').'</span>';

        foreach ($courses as $course) {
            $modinfo = get_fast_modinfo($course);

            $coursename = '';

            $coursename .= $OUTPUT->pix_icon('c/course', 'course', 'moodle');
            $classes = '';
            if (!$course->visible) {
                $classes .= ' class="dimmed" ';
            }
            $coursename .= $OUTPUT->heading('<a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'" '.$classes.'>'.$course->fullname.'</a>');

            $modslist = array(
                'assignments' => clone($modpattern),
                'forums' => clone($modpattern),
                'quizes' => clone($modpattern),
                'resources' => clone($modpattern),
            );

            //Get assignments
            $modslist = $this->assignment_details($user, $course, $modinfo, $modslist);
            $modslist = $this->assign_details($user, $course, $modinfo, $modslist);

            //Get forums
            $modslist = $this->forum_details($user, $course, $modinfo, $modslist);

            //Get quizes
            $modslist = $this->quiz_details($user, $course, $modinfo, $modslist);

            //Get resources
            $modslist = $this->resource_details($user, $course, $modinfo, $modslist);

            $modslist['quizes']->type = 'quiz';
            $modslist['forums']->type = 'forum';
            $modslist['assignments']->type = 'assignment';
            $modslist['resources']->type = 'resource';

            $modtable = new html_table();
            $modtable->colnames = array('modtype', 'totalnew', 'icon');
            $modtable->attributes = array('class' => 'generaltable meinekurse_content');
            $modtable->data = array();
            $coursenew = '';
            foreach ($modslist as $modtype => $typedata) {
                // First row - heading for the activity type.
                $row = array();
                $tmp = $OUTPUT->pix_icon('icon', $modtype, 'mod_' . $typedata->type);
                $tmp .= ' ' . get_string($modtype, 'block_meinekurse');
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
                    $listhtml = '<p>'.get_string('nonewitemssincelastlogin', 'block_meinekurse').'</p>';
                }
                $cell = new html_table_cell($listhtml);
                $cell->colspan = 3;
                $row = new html_table_row(array($cell));
                $modtable->data[] = $row;
            }

            $moddetails = html_writer::table($modtable);

            $coursetable->data[] = array($coursename.$coursenew, $infoicon, $moddetails);
        }

        $tblcontent = html_writer::table($coursetable);
        $tblcontent .= html_writer::tag('div', '', array('class' => "coursecontent meinekurse_content{$schoolid}",
                                                'style' => 'float:left;'));
        $tblcontent .= html_writer::tag('div', '', array('class' => 'clearer'));

        $content .= html_writer::tag('div', $tblcontent, array('class' => 'coursecontainer'));

        $content .= $paginghtml;

        return $content;
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
    protected function assignment_details($user, $course, $modinfo, $modslist) {
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
            // Gather submission data for all assignments
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
                //Count submissions:
                $submissions = isset($totalsubmissions[$mod->instance]) ? $totalsubmissions[$mod->instance]->c : 0;
                $desc[] = get_string('numsubmissions', 'block_meinekurse') . ': ' . $submissions;

                //Count ungraded submissions:
                $ungradeds = isset($ungradedsubmissions[$mod->instance]) ? $ungradedsubmissions[$mod->instance]->c : 0;
                $gradeds = $submissions - $ungradeds;
                $desc[] = '<a href="' . $CFG->wwwroot . '/mod/assignment/submissions.php?id=' . $mod->id . '">' .
                    get_string('numgradedsubmissions', 'block_meinekurse') . ': ' . $gradeds . '</a>';
                if($ungradeds) {
                    $isnew = true;
                }
                if($mod->timedue > time()) {
                    $isnew = true;
                }
                $count = $ungradeds;
            } else {
                $submissions = $DB->get_records('assignment_submissions', array('assignment' => $mod->instance, 'userid' => $user->id));
                $gradetimepercent = $this->grade_timepercent($course, 'mod', 'assignment', $mod->instance, $user->id);
                $submitted = count($submissions);
                if (!$submitted) {
                    if ($mod->timedue > 0) {
                        $isnew = true;
                        if ($mod->timedue < time()) {
                            $modslist['assignments']->red = true;
                        }
                    }
                }

                //If I haven't seen the (latest) grade, mark as new:
                if ($gradetimepercent && $gradetimepercent->date) {
                    //Get the date I viewed the module
                    $latestviewrecs = $DB->get_records('log', array('userid' => $user->id, 'cmid' => $mod->id, 'action' => 'view'), 'time DESC', 'time', 0, 1);
                    if (count($latestviewrecs)) {
                        $lastviewrec = array_shift($latestviewrecs);
                        $lastview = $lastviewrec->time;
                        if ($lastview < $gradetimepercent->date) {
                            $isnew = true;
                        }
                    }
                }

                if ($mod->timedue > 0) {
                    $desc[] = get_string('deadline', 'block_meinekurse') . ': ' . userdate($mod->timedue);
                }

                if ($submitted) {
                    $submission = array_shift($submissions);
                    $desc[] = get_string('submitted', 'block_meinekurse') . ': ' . userdate($submission->timecreated);
                }

                if ($gradetimepercent) {
                    $desc[] = get_string('grade', 'block_meinekurse') . ': ' . $gradetimepercent->grade;
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
    protected function assign_details($user, $course, $modinfo, $modslist) {
        global $DB, $CFG;

        if (!$DB->get_field('modules', 'visible', array('name' => 'assign'))) {
            return $modslist; // Assignment module is disabled - nothing to add here.
        }

        $mods = get_coursemodules_in_course('assign', $course->id, 'm.duedate');
        $assignids = array();
        foreach ($mods as $mod) {
            $assignids[] = $mod->instance;
        }
        if ($assignids && $course->istrainer) {
            // Gather submission data for all assignments

            if ($CFG->version > 2012120400) {
                debugging("Warning: assign_details does not take into account Moodle 2.5 resubmissions - the code should be updated");
            }

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
            $title = html_writer::link($cms->get_url(), format_string($mod->name));
            $desc = array();
            $count = 0;
            if ($course->istrainer) {
                //Count submissions:
                $submissions = isset($totalsubmissions[$mod->instance]) ? $totalsubmissions[$mod->instance]->c : 0;
                $desc[] = get_string('numsubmissions', 'block_meinekurse') . ': ' . $submissions;

                //Count ungraded submissions:
                $ungradeds = isset($ungradedsubmissions[$mod->instance]) ? $ungradedsubmissions[$mod->instance]->c : 0;
                $gradeds = $submissions - $ungradeds;
                $submissionurl = new moodle_url('/mod/assign/view.php', array('id' => $mod->id, 'action' => 'grading'));
                $desc[] = html_writer::link($submissionurl,
                                            get_string('numgradedsubmissions', 'block_meinekurse') . ': ' . $gradeds);
                if($ungradeds) {
                    $isnew = true;
                }
                if($mod->duedate > time()) {
                    $isnew = true;
                }
                $count = $ungradeds;
            } else {
                $submissions = $DB->get_records('assign_submission', array('assignment' => $mod->instance, 'userid' => $user->id));
                $gradetimepercent = $this->grade_timepercent($course, 'mod', 'assign', $mod->instance, $user->id);
                $submitted = count($submissions);
                if (!$submitted) {
                    if ($mod->duedate > 0) {
                        $isnew = true;
                        if ($mod->duedate < time()) {
                            $modslist['assignments']->red = true;
                        }
                    }
                }

                //If I haven't seen the (latest) grade, mark as new:
                if ($gradetimepercent && $gradetimepercent->date) {
                    //Get the date I viewed the module
                    $latestviewrecs = $DB->get_records('log', array('userid' => $user->id, 'cmid' => $mod->id, 'action' => 'view'), 'time DESC', 'time', 0, 1);
                    if (count($latestviewrecs)) {
                        $lastviewrec = array_shift($latestviewrecs);
                        $lastview = $lastviewrec->time;
                        if ($lastview < $gradetimepercent->date) {
                            $isnew = true;
                        }
                    }
                }

                if ($mod->duedate > 0) {
                    $desc[] = get_string('deadline', 'block_meinekurse') . ': ' . userdate($mod->duedate);
                }

                if ($submitted) {
                    $submission = array_shift($submissions);
                    $desc[] = get_string('submitted', 'block_meinekurse') . ': ' . userdate($submission->timecreated);
                }

                if ($gradetimepercent) {
                    $desc[] = get_string('grade', 'block_meinekurse') . ': ' . $gradetimepercent->grade;
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
     * Output the HTML for the icons to sort the courses.
     *
     * @param moodle_url $baseurl the URL to base the links on
     * @param string $selectedtype the sort currently selected
     * @return string html snipet for the icons
     */
    protected function sorting_icons($baseurl, $selectedtype) {

        $out = '';

        foreach (self::$validsort as $sorttype) {
            $str = get_string("sort{$sorttype}", 'block_meinekurse');
            $text = html_writer::tag('span', $str);
            $attr = array('id' => "meinekurse_sort{$sorttype}", 'title' => $str);
            if ($sorttype == $selectedtype) {
                $attr['class'] = 'selected';
            }
            $url = new moodle_url($baseurl, array('meinekurse_sortby' => $sorttype));
            $out .= html_writer::link($url, $text, $attr);
        }

        return html_writer::tag('div', $out, array('class' => 'meinekurse_sorticons'));
    }

    /**
     * Output the HTML for the form to sort the courses.
     *
     * @param moodle_url $baseurl the URL to base the links on
     * @param string $selectedtype the sort currently selected
     * @param $numcourses
     * @return string html snipet for the icons
     */
    protected function sorting_form($baseurl, $selectedtype, $numcourses) {

        $prefs = new stdClass();
        $prefs->meinekurse_sortby = $selectedtype;
        $prefs->numcourses = $numcourses;

        $out = '';
        $out .= html_writer::input_hidden_params($baseurl);
        $table = new html_table();
        $table->head = array(
            get_string('sortby', 'block_meinekurse'),
            get_string('numcourses', 'block_meinekurse'));
        $table->align = array('center', 'center', 'center');
        $table->data = array();
        $row = array();
        $row[] = $this->html_select('meinekurse_sortby', array('name', 'timecreated', 'timevisited'), true, $prefs);
        $row[] = $this->html_select('numcourses', array(5, 10, 20, 50, 100), false, $prefs);
        $table->data[] = $row;
        $out .= html_writer::table($table);

        return html_writer::tag('form', $out, array('method' => 'get', 'action' => $baseurl->out_omit_querystring()));
    }

    /*
     * Returns an object with grade time and grade in percentage form for a module a given user
     * @param obj $course, a course object containing id and showgrades
     * @param string $itemtype 'mod', 'block'
     * @param string $itemmodule 'forum, 'quiz', etc.
     * @param int $iteminstance id of the item module
     * @param int $userid
     * @param bool showhidden
     */
    private function grade_timepercent($course, $itemtype, $itemmodule, $iteminstance, $userid, $showhidden=false) {
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
     * @param object $user
     * @param object $course
     * @param course_modinfo $modinfo
     * @param object[] $modslist
     * @return object[] updated $modslist
     */
    protected function forum_details($user, $course, $modinfo, $modslist) {
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
            $title = html_writer::link($cms->get_url(), format_string($mod->name));
            $desc = array();

            //Depending on group mode, add group condition:
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

                //Get latest post:
                $desc[] = $posted.' '.get_string('posts', 'block_meinekurse').' '.get_string('sincelastlogin', 'block_meinekurse');
                $link = new moodle_url('/mod/forum/discuss.php');
                foreach ($posts as $post) {
                    $link->param('d', $post->discussion);
                    $link->set_anchor('p'.$post->id);
                    $msg = format_string($post->subject);
                    if (strlen($msg)>40) {
                        $msg = substr($msg, 0, 40).'&hellip;';
                    }
                    $desc[] = html_writer::link($link, $msg);
                }

            } else {
                $desc[] = $posted.' '.get_string('posts', 'block_meinekurse').' '
                    .get_string('sincelastlogin', 'block_meinekurse');
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
    protected function quiz_details($user, $course, $modinfo, $modslist) {
        global $CFG, $DB;

        if (!$DB->get_field('modules', 'visible', array('name' => 'quiz'))) {
            return $modslist; // Quiz module is disabled - nothing to add here.
        }

        $mods = get_coursemodules_in_course('quiz', $course->id, 'm.timeclose');
        $sql = "SELECT q.id
                      FROM {quiz} q
                      JOIN {quiz_question_instances} qi ON qi.quiz = q.id
                      JOIN {question} qs ON qi.question = qs.id
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
                $desc[] = get_string('numtests', 'block_meinekurse').': '.$attempts;
                if ($attempts) {
                    $isnew = ($mod->timeclose>time());
                    $isnew = $isnew || ($mod->timeclose == 0 && in_array($mod->instance, $essayquizzes));
                }
                $count = $attempts;
            } else {
                $gradetimepercent = $this->grade_timepercent($course, 'mod', 'quiz', $mod->instance, $user->id);
                if ($gradetimepercent) {
                    $url = $CFG->wwwroot.'/grade/report/user/index.php?id='.$course->id;
                }
                $attempted = $DB->count_records('quiz_attempts', array(
                                                                      'quiz' => $mod->instance, 'userid' => $user->id
                                                                 ));
                if (!$attempted) {
                    $isnew = true;
                    $count = 1;
                    if ($mod->timeclose>0) {
                        if ($mod->timeclose<time()) {
                            $modslist['quizes']->red = true;
                        }
                    }
                }

                //If the quiz has been marked since last visit, and the grade is visible to the user, also mark as new
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
                    if ($markedtime && $markedtime>$user->lastlogin) {
                        $isnew = true;
                        $count = 1;
                    }
                }

                if ($mod->timeclose>0) {
                    $desc[] = get_string('deadline', 'block_meinekurse').': '.userdate($mod->timeclose);
                }

                //Get last attempt date
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
                    $desc[] = get_string('submitted', 'block_meinekurse').': '.userdate($timefinish);
                }

                if ($gradetimepercent) {
                    $desc[] = get_string('grade', 'block_meinekurse').': '.$gradetimepercent->grade;
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
    protected function resource_details($user, $course, $modinfo, $modslist) {
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

                $title = html_writer::link($cms->get_url(), format_string($mod->name));
                if ($mod->timemodified>$user->lastlogin) {
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
            //'folder' type resource
            // Get a list of all the 'folder' resources in this course, with their 'lastmodified' time and the
            // largest 'timemodified' field for their included files - note this will skip folders with not files in them
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

                $title = html_writer::link($cms->get_url(), format_string($mod->name));
                $modified = ($mod->timemodified>$user->lastlogin);
                $modified = $modified || ($mod->filemodified>$user->lastlogin);
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

        //'page' type resource
        if ($enabled['page']) {
            $mods = get_coursemodules_in_course('page', $course->id, 'm.timemodified');
            foreach ($mods as $mod) {
                $cms = $modinfo->get_cm($mod->id);
                if (!$cms->uservisible) {
                    continue;
                }

                $title = html_writer::link($cms->get_url(), format_string($mod->name));
                if ($mod->timemodified>$user->lastlogin) {
                    $modslist['resources']->list[] = array(
                        'title' => $title,
                        'desc' => array(),
                        'icon' => $pageicon
                    );
                    $modslist['resources']->count++;
                }
            }
        }

        //'url' type resource
        if ($enabled['url']) {
            $mods = get_coursemodules_in_course('url', $course->id, 'm.timemodified');
            foreach ($mods as $mod) {
                $cms = $modinfo->get_cm($mod->id);
                if (!$cms->uservisible) {
                    continue;
                }

                $title = html_writer::link($cms->get_url(), format_string($mod->name));
                if ($mod->timemodified>$user->lastlogin) {
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

    /*
     * Return a html <select> tag
     * @param string $tabname - tab name to be included with the input name
     * @param string $selectname - name of the select tag
     * @param array $fields
     * @param bool $usegetstring - get string from language file or just display as is
     * @param object $data - preset data
     */

    private function html_select($selectname, $options, $usegetstring = true, $data = null) {
        if (is_null($data)) {
            $data = new stdClass();
        }
        $select = '<select name="'. $selectname . '" onchange="this.form.submit();">';
        foreach ($options as $option) {
            $selected = '';
            if (isset($data->{$selectname}) && $data->{$selectname} == $option) {
                $selected = ' selected="selected"';
            }
            $display = $usegetstring ? get_string($option, 'block_meinekurse') : $option;
            $select .= '<option value="' . $option . '"' . $selected . '>' . $display . '</option>';
        }
        $select .= '</select>';
        return $select;
    }

    /**
     * allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return false;
    }

    /**
     * locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my-index' => true);
    }

    /**
     * Get a list of all the courses the user is in, grouped by school
     *
     * @param string $sortby the field to sort by (timecreated, timevisited, name)
     * @param string $sortdir direction for sorting (asc, desc)
     * @param int $perpage the number of courses per page
     * @param int $schoolid the currently selected school
     * @param int $page the current page (in the selected school), may be updated
     * @return array
     */
    protected function get_my_courses($sortby, $sortdir, $perpage, $schoolid, $page) {
        global $USER, $DB;
        // Get all courses, grouped by school (3rd-level category)

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
                        'groupmode', 'groupmodeforce', 'modinfo', 'sectioncache');

        // Exclude the front page from the courses list.
        $wheres = array('c.id <> :siteid');
        $params['siteid'] = SITEID;

        if (isset($USER->loginascontext) and $USER->loginascontext->contextlevel == CONTEXT_COURSE) {
            // list _only_ this course - anything else is asking for trouble...
            $wheres[] = "courseid = :loginas";
            $params['loginas'] = $USER->loginascontext->instanceid;
        }

        $coursefields = 'c.' . join(',c.', $fields);
        list($ccselect, $ccjoin) = context_instance_preload_sql('c.id', CONTEXT_COURSE, 'ctx');
        $wheres = implode(" AND ", $wheres);

        //note: we can not use DISTINCT + text fields due to Oracle and MS limitations, that is why we have the subselect there
        $sql = "SELECT $coursefields $ccselect, ca.name AS catname, ca.depth AS catdepth, ca.path AS catpath
                  FROM {course} c
                  JOIN (SELECT DISTINCT e.courseid
                          FROM {enrol} e
                          JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = :userid)
                         WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)
                       ) en ON (en.courseid = c.id)
                  JOIN {course_categories} ca ON ca.id = c.category
               $ccjoin
                 WHERE $wheres
              $sort";
        $params['userid'] = $USER->id;
        $params['userid3'] = $params['userid'];
        $params['active'] = ENROL_USER_ACTIVE;
        $params['enabled'] = ENROL_INSTANCE_ENABLED;
        $params['now1'] = time();
        $params['now2'] = $params['now1'];

        $courses = $DB->get_records_sql($sql, $params);

        $myschool = meinekurse_get_main_school($USER);
        $schools = array();
        if ($myschool) {
            // Make sure the user's own school is first in the list.
            $schools[$myschool->id] = (object) array(
                'id' => $myschool->id,
                'name' => $myschool->name,
                'courses' => array(),
                'coursecount' => 0,
                'page' => 1,
            );
        }

        // preload contexts and check visibility
        foreach ($courses as $id => $course) {
            context_instance_preload($course);
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
            $course->istrainer = $context && has_capability('block/meinekurse:viewtrainertab', $context);
            if ($course->catdepth < MEINEKURSE_SCHOOL_CAT_DEPTH) {
                // Course does not appear to be within a school - gather all such courses together into a 'misc' category.
                $course->category = -1;
            } else if ($course->catdepth > MEINEKURSE_SCHOOL_CAT_DEPTH) {
                // Course is within a subcategory of a school - find the school category.
                $path = explode('/', $course->catpath);
                if (count($path) < (MEINEKURSE_SCHOOL_CAT_DEPTH + 1)) {
                    $course->category = -1;
                    debugging("Found bad category information - id: {$course->category}; depth: {$course->catdepth}; path: {$course->catpath}; name: {$course->catname}");
                } else {
                    $course->category = $path[MEINEKURSE_SCHOOL_CAT_DEPTH];
                    $course->catname = null; // As this was the name of the direct category, not of the school.
                }
            }

            if (empty($schools[$course->category])) {
                // First course we've found in this school - set up the school details.
                $school = new stdClass();
                $school->id = $course->category;
                if ($course->category > 0) {
                    $school->name = $course->catname;
                } else {
                    $school->name = get_string('notinschool', 'block_meinekurse');
                }
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
                    continue; // Other than the currently selected school, only keep the first 'perpage'
                }
            }
            $schools[$course->category]->courses[] = $course; // Add the course to the list for the school.
        }

        // Now deal with the paging of the currently selected school
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
                $catids[] = $school->id;
            }
        }
        if (!empty($catids)) {
            $catnames = $DB->get_records_list('course_categories', 'id', $catids, '', 'id, name');
            foreach ($catnames as $cat) {
                $schools[$cat->id]->name = $cat->name;
            }
        }

        // Move the 'Not in a school' list to the end of the schools
        if (array_key_exists(-1, $schools)) {
            $noschool = $schools[-1];
            unset($schools[-1]);
            $schools[-1] = $noschool;
        }

        return $schools;
    }

    /*
     * Returns a HTML table that has cell IDs that can be hidden
     * @param object $table - similar to the stdClass table sent to html_writer::table,
     *                        only has 'data'
     *                        also with 'colnames' - an array of column names to be used as part of the ID
     */

    private function table($table) {
        $html = '';
        $html .= '<table style="float: left;" class="generaltable mycourses"><tbody>';
        $rowid = 0;
        foreach ($table->data as $row) {
            $rowid++;
            $html .= '<tr class="r' . $rowid . '">';
            foreach ($table->colnames as $colname) {
                $cell = array_shift($row);
                $html .= '<td id="cell_' . $rowid . '_' . $colname . '">' . $cell . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

}
