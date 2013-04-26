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

require_once($CFG->dirroot . '/lib/weblib.php');
require_once($CFG->dirroot . '/lib/formslib.php');

class block_meinekurse extends block_base {

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
        global $USER, $CFG, $OUTPUT, $DB, $PAGE;

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
        $tabnames = array('mycourses', 'astrainer');
        if (!$prefs = get_user_preferences('block_meinekurse_prefs', false)) {
            $defaults = (object) array(
                        'sortby' => 'name',
                        'coursevisited' => 'allcourses',
                        'numcourses' => 5,
            );
            $prefs = array();
            foreach ($tabnames as $tabname) {
                $prefs[$tabname] = clone($defaults);
            }
        } else {
            $prefs = unserialize($prefs);
        }
        foreach ($tabnames as $tabname) {
            $newsetting = optional_param('meinekurse_'.$tabname.'_sortby', '', PARAM_TEXT);
            if ($newsetting != '') {
                $prefs[$tabname]->sortby = $newsetting;
            }
            $newsetting = optional_param('meinekurse_'.$tabname.'_coursevisited', '', PARAM_TEXT);
            if ($newsetting != '') {
                $prefs[$tabname]->coursevisited = $newsetting;
            }
            $newsetting = optional_param('meinekurse_'.$tabname.'_numcourses', '', PARAM_INT);
            if ($newsetting != '') {
                $prefs[$tabname]->numcourses = $newsetting;
            }
        }
        $initialtab = optional_param('tabname', 'mycourses', PARAM_TEXT);
        set_user_preference('block_meinekurse_prefs', serialize($prefs));

        if ($initialtab == 'astrainer') {
            $content .= '<script type="text/javascript">var starttab = 1</script>';
        } else {
            $content .= '<script type="text/javascript">var starttab = 0</script>';
        }

        //Page from url
        $pagenum = optional_param('page', 1, PARAM_INT);
        if ($initialtab == 'astrainer') {
            $studentpagenum = 1;
            $trainerpagenum = $pagenum;
        } else {
            $studentpagenum = $pagenum;
            $trainerpagenum = 1;
        }

        //Tabs
        $content .= '<div class="mycoursestabs">';

        //Get courses:
        $allmycourses = $this->get_my_courses($prefs['mycourses'], false, null, $studentpagenum);
        $alltrainercourses = $this->get_my_courses($prefs['astrainer'], true, null, $trainerpagenum);

        //If the page is empty for some reason, send us back to page 1
        if (empty($allmycourses->courses)) {
            $studentpagenum = 1;
        }
        if (empty($alltrainercourses->courses)) {
            $trainerpagenum = 1;
        }

        $content .= '<ul>';
        $content .= '<li class="block"><a href="#mycoursestab">' . get_string('mycourses', 'block_meinekurse') . '</a></li>';
        if (!empty($alltrainercourses->courses)) {
            $content .= '<li class="block"><a href="#astrainertab">' . get_string('coursesastrainer', 'block_meinekurse') . '</a></li>';
        }
        $content .= '</ul>';

        //MY COURSES TAB
        $content .= '<div id="mycoursestab">';
        $content .= $this->one_tab($USER, $prefs['mycourses'], $allmycourses->courses, false, 'mycourses', $allmycourses->pages, $studentpagenum);
        $content .= '</div>';

        //Courses as Trainer tab
        if ($alltrainercourses->pages) {
            $content .= '<div id="astrainertab">';
            $content .= $this->one_tab($USER, $prefs['astrainer'], $alltrainercourses->courses, true, 'astrainer', $alltrainercourses->pages, $trainerpagenum);
            $content .= '</div>';
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

    private function one_tab($user, $prefs, $courses, $istrainer = false, $tabname, $totalpages = 1, $thispage = 1) {
        global $CFG, $OUTPUT, $DB;

        $content = '';
        //Settings form
        $content .= '<form method="post">';
        $content .= '<input type="hidden" name="tabname" value="'.$tabname.'" />';
        $table = new html_table();
        $table->head = array(
            get_string('sortby', 'block_meinekurse'),
            get_string('lastvisited', 'block_meinekurse'),
            get_string('numcourses', 'block_meinekurse'));
        $table->align = array('center', 'center', 'center');
        $table->data = array();
        $row = array();
        $row[] = $this->html_select($tabname, 'sortby', array('name', 'timecreated', 'timevisited'), true, $prefs);
        $row[] = $this->html_select($tabname, 'coursevisited', array('lastweek', 'lastmonth', 'last6months', 'allcourses'), true, $prefs);
        $row[] = $this->html_select($tabname, 'numcourses', array(5, 10, 20, 50, 100), false, $prefs);
        $table->data[] = $row;
        $content .= html_writer::table($table);
        $content .= '</form>';

        if (empty($courses)) {
            $content .= get_string('nocourses', 'block_meinekurse');
        }

        //Pagination
        $paginghtml = '';
        if ($totalpages > 1) {
            $trainerparam = $istrainer ? '&amp;tabname=astrainer' : '';
            $paginghtml .= '<div class="paging">' . get_string('page') . ': ';
            if ($thispage > 1) {
                $paginghtml .= '<a class="previous" href="?page=' . ($thispage - 1) . $trainerparam . '">(' . get_string('previous') . ')</a>';
            }
            for ($pg = 1; $pg <= $totalpages; $pg++) {
                $paginghtml .= ' ';
                if ($thispage == $pg) {
                    $paginghtml .= $pg;
                } else {
                    $paginghtml .= '<a href="?page=' . $pg . $trainerparam . '">' . $pg . '</a>';
                }
                $paginghtml .= ' ';
            }
            if ($thispage < $totalpages) {
                $paginghtml .= '<a class="previous" href="?page=' . ($thispage + 1) . $trainerparam . '">(' . get_string('next') . ')</a>';
            }
            $paginghtml .= '</div>';
        }
        $content .= $paginghtml;

        //Query results
        $urlicon = $OUTPUT->pix_icon('icon', get_string('pluginname', 'mod_url'), 'mod_url');
        $pageicon = $OUTPUT->pix_icon('icon', get_string('pluginname', 'mod_page'), 'mod_page');
        $foldericon = $OUTPUT->pix_icon('icon', get_string('pluginname', 'mod_folder'), 'mod_folder');
        $strfile = get_string('pluginname', 'mod_resource');
        foreach ($courses as $course) {
            $modinfo = get_fast_modinfo($course);

            $content .= "<div class='meinekurse_course' style='clear: both' id='meinekurse_{$course->id}'>";
            $content .= $OUTPUT->pix_icon('c/course', 'course', 'moodle');
            $classes = '';
            if (!$course->visible) {
                $classes .= ' class="dimmed" ';
            }
            $content .= $OUTPUT->heading('<a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'" '.$classes.'>'.$course->fullname.'</a>');
            $content .= html_writer::tag('div', '', array('class' => "clearer"));
            $modpattern = new stdClass();
            $modpattern->red = false;
            $modpattern->list = array();
            $modpattern->count = 0;
            $modslist = array(
                'assignments' => clone($modpattern),
                'forums' => clone($modpattern),
                'quizes' => clone($modpattern),
                'resources' => clone($modpattern),
            );

            //Get assignments
            $mods = get_coursemodules_in_course('assignment', $course->id, 'm.timedue');
            $assignmentids = array();
            foreach ($mods as $mod) {
                $assignmentids[] = $mod->instance;
            }
            if ($assignmentids && $istrainer) {
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
                if ($istrainer) {
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

            //Get forums
            $mods = get_coursemodules_in_course('forum', $course->id);
            foreach ($mods as $mod) {
                $isnew = false;
                $cms = $modinfo->get_cm($mod->id);
                if (!$cms->uservisible) {
                    continue;
                }
                $title = '<a href="' . $CFG->wwwroot . '/mod/forum/view.php?id=' . $mod->id . '">' . s($mod->name) . '</a>';
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
                $params = array('forumid' => $mod->instance, 'lastlogin' => $user->lastlogin, 'userid' => $user->id, 'userid2' => $user->id);
                $posts = $DB->get_records_sql($sql, $params);
                $posted = count($posts);
                if ($posted) {
                    $isnew = true;

                    //Get latest post:
                    $desc[] = $posted . ' ' . get_string('posts', 'block_meinekurse') . ' ' . get_string('sincelastlogin', 'block_meinekurse');
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
                    $desc[] = $posted . ' ' . get_string('posts', 'block_meinekurse') . ' '
                            . get_string('sincelastlogin', 'block_meinekurse');
                }

                if ($isnew) {
                    $modslist['forums']->list[] = array(
                        'title' => $title,
                        'desc' => $desc,
                    );
                    $modslist['forums']->count += $posted;
                }
            }

            //Get quizes
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
                $url = $CFG->wwwroot . '/mod/quiz/view.php?id=' . $mod->id;
                $desc = array();
                $count = 0;
                if ($istrainer) {
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
                    $desc[] = get_string('numtests', 'block_meinekurse') . ': ' . $attempts;
                    if ($attempts) {
                        $isnew = ($mod->timeclose > time());
                        $isnew = $isnew || ($mod->timeclose == 0 && in_array($mod->instance, $essayquizzes));
                    }
                    $count = $attempts;
                } else {
                    $gradetimepercent = $this->grade_timepercent($course, 'mod', 'quiz', $mod->instance, $user->id);
                    if ($gradetimepercent) {
                        $url = $CFG->wwwroot . '/grade/report/user/index.php?id=' . $course->id;
                    }
                    $attempted = $DB->count_records('quiz_attempts', array('quiz' => $mod->instance, 'userid' => $user->id));
                    if (!$attempted) {
                        $isnew = true;
                        $count = 1;
                        if ($mod->timeclose > 0) {
                            if ($mod->timeclose < time()) {
                                $modslist['quizes']->red = true;
                            }
                        }
                    }

                    //If the quiz has been marked since last visit, and the grade is visible to the user, also mark as new
                    if($gradetimepercent && !$isnew) {
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
                        $desc[] = get_string('deadline', 'block_meinekurse') . ': ' . userdate($mod->timeclose);
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
                        $desc[] = get_string('submitted', 'block_meinekurse') . ': ' . userdate($timefinish);
                    }


                    if ($gradetimepercent) {
                        $desc[] = get_string('grade', 'block_meinekurse') . ': ' . $gradetimepercent->grade;
                    }
                }
                $title = '<a href="' . $url . '">' . s($mod->name) . '</a>';

                if ($isnew) {
                    $modslist['quizes']->list[] = array(
                        'title' => $title,
                        'desc' => $desc,
                    );
                    $modslist['quizes']->count += $count;
                }
            }

            //Get resources

            //'resource' type resource
            $mods = get_coursemodules_in_course('resource', $course->id, 'm.timemodified');
            foreach ($mods as $mod) {
                $cms = $modinfo->get_cm($mod->id);
                if (!$cms->uservisible) {
                    continue;
                }

                $title = '<a href="' . $CFG->wwwroot . '/mod/resource/view.php?id=' . $mod->id . '">' . s($mod->name) . '</a>';
                if ($mod->timemodified > $user->lastlogin) {
                    $modslist['resources']->list[] = array(
                        'title' => $title,
                        'desc' => array(),
                        'icon' => $OUTPUT->pix_icon($cms->icon, $strfile, $cms->iconcomponent)
                    );
                    $modslist['resources']->count++;
                }
            }

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

                $title = '<a href="' . $CFG->wwwroot . '/mod/folder/view.php?id=' . $mod->id . '">' . s($mod->name) . '</a>';
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

            //'page' type resource
            $mods = get_coursemodules_in_course('page', $course->id, 'm.timemodified');
            foreach ($mods as $mod) {
                $cms = $modinfo->get_cm($mod->id);
                if (!$cms->uservisible) {
                    continue;
                }

                $title = '<a href="' . $CFG->wwwroot . '/mod/page/view.php?id=' . $mod->id . '">' . s($mod->name) . '</a>';
                if ($mod->timemodified > $user->lastlogin) {
                    $modslist['resources']->list[] = array(
                        'title' => $title,
                        'desc' => array(),
                        'icon' => $pageicon
                    );
                    $modslist['resources']->count++;
                }
            }

            //'url' type resource
            $mods = get_coursemodules_in_course('url', $course->id, 'm.timemodified');
            foreach ($mods as $mod) {
                $cms = $modinfo->get_cm($mod->id);
                if (!$cms->uservisible) {
                    continue;
                }

                $title = '<a href="' . $CFG->wwwroot . '/mod/url/view.php?id=' . $mod->id . '">' . s($mod->name) . '</a>';
                if ($mod->timemodified > $user->lastlogin) {
                    $modslist['resources']->list[] = array(
                        'title' => $title,
                        'desc' => array(),
                        'icon' => $urlicon
                    );
                    $modslist['resources']->count++;
                }
            }

            $modslist['quizes']->type = 'quiz';
            $modslist['forums']->type = 'forum';
            $modslist['assignments']->type = 'assignment';
            $modslist['resources']->type = 'resource';

            $table = new html_table();
            $table->colnames = array('modtype', 'totalnew', 'icon', 'moddesc');
            $table->data = array();
            foreach ($modslist as $modtype => $typedata) {
                $row = array();
                $tmp = $OUTPUT->pix_icon('icon', $modtype, 'mod_' . $typedata->type);
                $tmp .= ' ' . get_string($modtype, 'block_meinekurse');
                $row[] = $tmp;
                if ($typedata->count) {
                    $new = $typedata->count . ' <span class="newtext">' . get_string('new', 'block_meinekurse').'</span>';
                    if ($typedata->red) {
                        $new = '<span class="red">' . $new . '</span>';
                    }
                    $row[] = $new;
                } else {
                    $row[] = '&nbsp;';
                }
                $row[] = $OUTPUT->pix_icon('i/info', 'info');
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
                $row[] = $listhtml;

                $table->data[] = $row;
            }


            $container = $this->table($table);
            $container .= html_writer::tag('div', '', array('class' => "coursecontent meinekurse_{$course->id}", 'style' => 'float:left;'));
            $container .= html_writer::tag('div', '', array('class' => "clearer"));
            $content .= html_writer::tag('div', $container, array('class' => "coursecontainer container_{$course->id}"));
            $content .= '<div class="clearer"></div>';
            $content .= '</div>';
            $content .= '<hr>';
        }

        $content .= $paginghtml;

        return $content;
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
        global $USER, $CFG;
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

    /*
     * Return a html <select> tag
     * @param string $tabname - tab name to be included with the input name
     * @param string $selectname - name of the select tag
     * @param array $fields
     * @param bool $usegetstring - get string from language file or just display as is
     * @param object $data - preset data
     */

    private function html_select($tabname, $selectname, $options, $usegetstring = true, $data = null) {
        if (is_null($data)) {
            $data = new stdClass();
        }
        $select = '<select name="meinekurse_' .$tabname .'_' . $selectname . '" onchange="this.form.submit();">';
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
     * Modified copy of enrol_get_my_courses() in lib/enrollib.php
     * To allow additional filtering
     * And pagination
     *
     * - $fields is an array of field names to ADD
     *   so name the fields you really need, which will
     *   be added and uniq'd
     *
     * @param object $prefs - search preferences
     * @param bool $istrainer
     * @param string|array $fields
     * @param int page
     * @return object with results array and page count
     */
    private function get_my_courses($prefs, $istrainer = false, $fields = NULL, $page = 1) {
        global $DB, $USER;

        //Sorting:
        if ($prefs->sortby == 'timecreated') {
            $sort = 'c.timecreated ASC';
        } else if ($prefs->sortby == 'timevisited') {
            $sort = "(SELECT MAX(time) FROM {log} lg WHERE lg.course = c.id AND lg.action = 'view' AND lg.userid = :userid2)";
        } else {
            $sort = 'c.fullname ASC';
        }

        // Guest account does not have any courses
        if (isguestuser() or !isloggedin()) {
            return(array());
        }

        $basefields = array('id', 'category', 'sortorder',
            'shortname', 'fullname', 'idnumber',
            'startdate', 'visible', 'showgrades',
            'groupmode', 'groupmodeforce', 'modinfo', 'sectioncache');

        if (empty($fields)) {
            $fields = $basefields;
        } else if (is_string($fields)) {
            // turn the fields from a string to an array
            $fields = explode(',', $fields);
            $fields = array_map('trim', $fields);
            $fields = array_unique(array_merge($basefields, $fields));
        } else if (is_array($fields)) {
            $fields = array_unique(array_merge($basefields, $fields));
        } else {
            throw new coding_exception('Invalid $fileds parameter in enrol_get_my_courses()');
        }
        if (in_array('*', $fields)) {
            $fields = array('*');
        }

        $orderby = "ORDER BY $sort";

        $wheres = array("c.id <> :siteid");
        $params = array('siteid' => SITEID);

        if (isset($USER->loginascontext) and $USER->loginascontext->contextlevel == CONTEXT_COURSE) {
            // list _only_ this course - anything else is asking for trouble...
            $wheres[] = "courseid = :loginas";
            $params['loginas'] = $USER->loginascontext->instanceid;
        }

        //Course visit filtering
        if ($prefs->coursevisited != 'allcourses') {
            switch ($prefs->coursevisited) {
                case 'lastweek':
                    $visitedfrom = time() - (7 * 24 * 60 * 60);
                    break;
                case 'lastmonth':
                    $visitedfrom = time() - (30 * 24 * 60 * 60);
                    break;
                case 'last6months':
                    $visitedfrom = time() - (182 * 24 * 60 * 60);
                    break;
            }
            $wheres[] = "EXISTS (
                SELECT * FROM {log} lgwhr
                WHERE lgwhr.course = c.id AND lgwhr.action = 'view' AND lgwhr.userid = :userid3 AND lgwhr.time > $visitedfrom
                )";
        }

        $coursefields = 'c.' . join(',c.', $fields);
        list($ccselect, $ccjoin) = context_instance_preload_sql('c.id', CONTEXT_COURSE, 'ctx');
        $wheres = implode(" AND ", $wheres);

        //note: we can not use DISTINCT + text fields due to Oracle and MS limitations, that is why we have the subselect there
        $sql = "SELECT $coursefields $ccselect
                  FROM {course} c
                  JOIN (SELECT DISTINCT e.courseid
                          FROM {enrol} e
                          JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = :userid)
                         WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)
                       ) en ON (en.courseid = c.id)
               $ccjoin
                 WHERE $wheres
              $orderby";
        $params['userid'] = $USER->id;
        $params['userid2'] = $USER->id;
        $params['userid3'] = $USER->id;
        $params['active'] = ENROL_USER_ACTIVE;
        $params['enabled'] = ENROL_INSTANCE_ENABLED;
        $params['now1'] = time();
        $params['now2'] = $params['now1'];

        $courses = $DB->get_records_sql($sql, $params);

        // preload contexts and check visibility
        foreach ($courses as $id => $course) {
            context_instance_preload($course);
            if (!$course->visible) {
                if (!$context = context_course::instance($id)) {
                    unset($courses[$id]);
                    continue;
                }
                if (!has_capability('moodle/course:viewhiddencourses', $context)) {
                    unset($courses[$id]);
                    continue;
                }
            }
            $courses[$id] = $course;
        }

        //Add to array depending on capability
        $results = array();
        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            if (has_capability('block/meinekurse:viewtrainertab', $context)) {
                if ($istrainer) {
                    $results[] = $course;
                }
            } else {
                if (!$istrainer) {
                    $results[] = $course;
                }
            }
        }
        $toreturn = new stdClass();
        $toreturn->pages = ceil(count($results) / $prefs->numcourses);
        $toreturn->courses = array_slice($results, ($page - 1) * $prefs->numcourses, $prefs->numcourses);
        //If for some reason page is empty, send to Page 1.
        if (!count($toreturn->courses)) {
            $toreturn->courses = array_slice($results, 0, $prefs->numcourses);
        }
        return $toreturn;
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

?>
