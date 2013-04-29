<?php
/*
 #########################################################################
 #                       DLB-Bayern
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 #
 # Copyright 2012 Andreas Wagner. All Rights Reserved.
 # This file may not be redistributed in whole or significant part.
 # Content of this file is Protected By International Copyright Laws.
 #
 # ~~~~~~~~~~~~~~~~~~ THIS CODE IS NOT FREE SOFTWARE ~~~~~~~~~~~~~~~~~~~~
 #
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 # @author Andreas Wagner, DLB	andreas.wagner@alp.dillingen.de
 #########################################################################
*/
require_once("../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT); // Category id
$page = optional_param('page', 0, PARAM_INT); // which page to show
$perpage = optional_param('perpage', $CFG->coursesperpage, PARAM_INT); // how many per page
$categoryedit = optional_param('categoryedit', -1, PARAM_BOOL);
$hide = optional_param('hide', 0, PARAM_INT);
$show = optional_param('show', 0, PARAM_INT);
$moveup = optional_param('moveup', 0, PARAM_INT);
$movedown = optional_param('movedown', 0, PARAM_INT);
$moveto = optional_param('moveto', 0, PARAM_INT);
$resort = optional_param('resort', 0, PARAM_BOOL);


$site = get_site();

if (empty($id)) {
    print_error("unknowcategory");
}

$PAGE->set_category_by_id($id);
$urlparams = array('id' => $id);
if ($page) {
    $urlparams['page'] = $page;
}
if ($perpage) {
    $urlparams['perpage'] = $perpage;
}
$PAGE->set_url(new moodle_url('/course/category.php', array('id' => $id)));
navigation_node::override_active_url($PAGE->url);
$context = $PAGE->context;
$category = $PAGE->category;

$canedit = can_edit_in_category($category->id);
if ($canedit) {
    if ($categoryedit !== -1) {
        $USER->editing = $categoryedit;
    }
    require_login();
    $editingon = $PAGE->user_is_editing();
} else {
    if ($CFG->forcelogin) {
        require_login();
    }
    $editingon = false;
}

if (!$category->visible) {
    require_capability('moodle/category:viewhiddencategories', $context);
}

// Process any category actions.
if (has_capability('moodle/category:manage', $context)) {
    /// Resort the category if requested
    if ($resort and confirm_sesskey()) {
        if ($courses = get_courses($category->id, "fullname ASC", 'c.id,c.fullname,c.sortorder')) {
            $i = 1;
            foreach ($courses as $course) {
                $DB->set_field('course', 'sortorder', $category->sortorder+$i, array('id'=>$course->id));
                $i++;
            }
            fix_course_sortorder(); // should not be needed
        }
    }
}

// Process any course actions.
if ($editingon) {
    /// Move a specified course to a new category
    if (!empty($moveto) and $data = data_submitted() and confirm_sesskey()) {   // Some courses are being moved
        // user must have category update in both cats to perform this
        require_capability('moodle/category:manage', $context);
        require_capability('moodle/category:manage', get_context_instance(CONTEXT_COURSECAT, $moveto));

        if (!$destcategory = $DB->get_record('course_categories', array('id' => $data->moveto))) {
            print_error('cannotfindcategory', '', '', $data->moveto);
        }

        $courses = array();
        foreach ($data as $key => $value) {
            if (preg_match('/^c\d+$/', $key)) {
                $courseid = substr($key, 1);
                array_push($courses, $courseid);

                // check this course's category
                if ($movingcourse = $DB->get_record('course', array('id'=>$courseid))) {
                    if ($movingcourse->category != $id ) {
                        print_error('coursedoesnotbelongtocategory');
                    }
                } else {
                    print_error('cannotfindcourse');
                }
            }
        }
        move_courses($courses, $data->moveto);
    }

    /// Hide or show a course
    if ((!empty($hide) or !empty($show)) and confirm_sesskey()) {
        if (!empty($hide)) {
            $course = $DB->get_record('course', array('id' => $hide));
            $visible = 0;
        } else {
            $course = $DB->get_record('course', array('id' => $show));
            $visible = 1;
        }

        if ($course) {
            $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
            require_capability('moodle/course:visibility', $coursecontext);
            $DB->set_field('course', 'visible', $visible, array('id' => $course->id));
            $DB->set_field('course', 'visibleold', $visible, array('id' => $course->id)); // we set the old flag when user manually changes visibility of course
        }
    }


    /// Move a course up or down
    if ((!empty($moveup) or !empty($movedown)) and confirm_sesskey()) {
        require_capability('moodle/category:manage', $context);

        // Ensure the course order has continuous ordering
        fix_course_sortorder();
        $swapcourse = NULL;

        if (!empty($moveup)) {
            if ($movecourse = $DB->get_record('course', array('id' => $moveup))) {
                $swapcourse = $DB->get_record('course', array('sortorder' => $movecourse->sortorder - 1));
            }
        } else {
            if ($movecourse = $DB->get_record('course', array('id' => $movedown))) {
                $swapcourse = $DB->get_record('course', array('sortorder' => $movecourse->sortorder + 1));
            }
        }
        if ($swapcourse and $movecourse) {
            // check course's category
            if ($movecourse->category != $id) {
                print_error('coursedoesnotbelongtocategory');
            }
            $DB->set_field('course', 'sortorder', $swapcourse->sortorder, array('id' => $movecourse->id));
            $DB->set_field('course', 'sortorder', $movecourse->sortorder, array('id' => $swapcourse->id));
        }
    }

} // End of editing stuff

// Print headings
$numcategories = $DB->count_records('course_categories');

$stradministration = get_string('administration');
$strcategories = get_string('categories');
$strcategory = get_string('category');
$strcourses = get_string('courses');

if ($editingon && can_edit_in_category()) {
    // Integrate into the admin tree only if the user can edit categories at the top level,
    // otherwise the admin block does not appear to this user, and you get an error.
    require_once($CFG->libdir . '/adminlib.php');
    admin_externalpage_setup('coursemgmt', '', $urlparams, $CFG->wwwroot . '/course/category.php');
    $PAGE->set_context($context);   // Ensure that we are actually showing blocks etc for the cat context

    $settingsnode = $PAGE->settingsnav->find_active_node();
    if ($settingsnode) {
        $settingsnode->make_inactive();
        $settingsnode->force_open();
        $PAGE->navbar->add($settingsnode->text, $settingsnode->action);
    }

//+++ Bearbeiten ausschalten als Button
    $buttons = '<table><tr><td><form method="get" action="category.php"><div>'.
            '<input type="hidden" name="id" value="'.$id.'" />'.
            '<input type="hidden" name="categoryedit" value="'.($PAGE->user_is_editing()?'0':'1').'" />'.
            '<input type="submit" value="'.get_string('turneditingoff').'" /></div></form></td></tr></table>';
    $PAGE->set_button($buttons);

    //--- Bearbeiten ausschalten als Button
    echo $OUTPUT->header();
} else {
    $PAGE->set_title("$site->shortname: $category->name");
    $PAGE->set_heading($site->fullname);

    if ($editingon) {
        //+++ Bearbeiten ausschalten als Button
        $buttons = '<table><tr><td><form method="get" action="category.php"><div>'.
                '<input type="hidden" name="id" value="'.$id.'" />'.
                '<input type="hidden" name="categoryedit" value="'.($PAGE->user_is_editing()?'0':'1').'" />'.
                '<input type="submit" value="'.get_string('turneditingoff').'" /></div></form></td></tr></table>';
        $PAGE->set_button($buttons);
    } else {
        $PAGE->set_button(print_course_search('', true, 'navbar'));
    }
    $PAGE->set_pagelayout('coursecategory');
    echo $OUTPUT->header();
}

//awag ab hier komplett verändert, 1. Navigation durch die Kursbereiche, 2. Kursliste
require_once ($CFG->dirroot."/blocks/custom_category/classes/class.directorylisting.php");
$dirlisting = directorylisting::getInstance($category, $context);

//Behandlung der neuen Aktionen
$dirlisting->doActions();

//Zustand prüfen und anzeigen
$dirlisting->displayState();

//Navigation durch die Kursbereiche
$dirlisting->print_CategoriesList($editingon);

//Kursbereichsbeschreibung
$dirlisting->print_CategoriesDescription();

//Trenner
echo "<div id=\"categories-footer\"></div>";

$position = array('aftercourseid' => '0', 'afterlinkid' => '0', 'beforecourseid' => '0', 'beforelinkid' => '0');

//falls im Verschiebemodus für Kurses keine sortierung erlauben....
$sortorder = ($dirlisting->isSortEditMode())? "sortorder ASC" : optional_param('sortorder', 'sortorder ASC', PARAM_NOTAGS);

/// Print out all the courses
$courses = directorylisting::get_courses_page($category->id, $sortorder,
        'c.id,c.shortname,c.fullname,c.summary,c.visible,c.timecreated',
        $totalcount, $page*$perpage, $perpage);

if (!$courses) {

    if (has_capability('moodle/category:manage', $context) and ($editingon)) {

        echo "<div id=\"courselist-left\"></div>";
        echo "<div id=\"courselist-right\"></div>";
        echo "<div id=\"courselist\">";

        $dirlisting->_printMoveCourseMarker($position);
        $dirlisting->_printCreateCourseLinkMarker($position);
        //Move - Kurslinkmarkierung
        $dirlisting->_printMoveCourseLinkMarker($position);
        
        echo "</div>";
    }

} else {

    ?>
<script type="text/javascript">

    M.hover_control = {}

    M.hover_control.init = function (Y) {
        this.Y = Y;
        this.infodock = this.Y.one('#infodock');
        this.infodock.on('mouseout', M.hover_control.infodockOut, this);
        //Statusvariablen
        this.infodockvisible = false;
        this.inforow = null;
        //an alle Event an alle Zeilen heften....
        this.inforows = this.Y.all('.inforow');
        this.inforows.on('mouseover', M.hover_control.inforowOver, this);
        this.inforows.on('mouseout', M.hover_control.inforowOut, this);
    }

    M.hover_control.inforowOver = function (e) {

        //nur erstmalig sichtbar schalten..
        if (this.infodockvisible) return true;

        //Zeile highlighten.
        e.currentTarget.set('className','inforow row-highlight');

        var inforow = e.currentTarget.getDOMNode();
        var data = inforow.id.split('_');

        var infodiv = this.Y.one("#infodiv_" + data[1]).getDOMNode();
        var courselisttable = this.Y.one("#courselist-table").getDOMNode();
        var infodock = this.infodock.getDOMNode();

        infodock.innerHTML = infodiv.innerHTML;
        infodock.style.marginTop = "-8px";
        infodock.style.display = "block";

        //position korrigieren.
        var inforow_bottom = courselisttable.offsetTop + inforow.offsetTop + inforow.offsetHeight;
        var infodock_bottom = infodock.offsetTop + infodock.offsetHeight;

        if (inforow_bottom > infodock_bottom) {
            infodock.style.marginTop = (inforow_bottom - infodock_bottom + 3) + "px";
        }

        this.inforow = inforow;
        this.infodockvisible = true;
    }

    M.hover_control.inforowOut = function (e) {

        var tg = e.currentTarget.getDOMNode();

        //wenn inforow nicht verlassen wurde, abbrechen
        if (tg.nodeName != 'TR') return;

        //Prüfen, ob in ein Kind oder zum InfoDock gewechselt wurde ..
        var reltg = e.relatedTarget.getDOMNode();
        while (reltg != tg  && reltg.id != 'infodock' && reltg.nodeName != 'BODY') reltg= reltg.parentNode
        //Event wurde bebubbelt, verlassen...
        if ((reltg == tg) || reltg.id == 'infodock') return;

        //Tabellenzeile wirklich verlassen...
        this.infodock.getDOMNode().innerHTML = "";
        if (this.inforow) this.inforow.className = 'inforow';
        this.infodockvisible = false;
    }

    M.hover_control.infodockOut = function (e) {

        var tg = e.currentTarget.getDOMNode();

        //wenn inforow nicht verlassen wurde, abbrechen
        if (tg.id != 'infodock') return;

        //Prüfen, ob in ein Kind oder zum InfoDock gewechselt wurde ..
        var reltg = e.relatedTarget.getDOMNode();
        while (reltg != tg && reltg.nodeName != 'BODY') reltg= reltg.parentNode
        //Event wurde bebubbelt, verlassen...
        if (reltg == tg) return;

        //Tabellenzeile wirklich verlassen...
        this.infodock.getDOMNode().innerHTML = "";
        this.infodock.getDOMNode().style.display = "none";
        if (this.inforow) this.inforow.className = 'inforow';
        this.infodockvisible = false;
    }

</script><?php

    $PAGE->requires->js_init_call('M.hover_control.init(Y);');

    echo "<div id=\"courselist-left\"></div>";
    echo "<div id=\"courselist-right\"></div>";

    echo "<div id=\"courselist\">";
    echo "<h2>".get_string('courselist', 'block_custom_category', $category->name)."</h2>";

    echo "<div id=\"courselist-form\">";
    echo '<form id="sortcourses" action="category.php" method="post">';

    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="hidden" name="id" value="'.$category->id.'" />';

    //Sortierung-Formular
    echo "<label for=\"sortorder\">".get_string('sortorder', 'block_custom_category').": </label>";
    $choices = array('sortorder ASC' => get_string('sortorder_asc', 'block_custom_category'),
            'sortorder DESC' => get_string('sortorder_desc' , 'block_custom_category'),
            'fullname ASC' => get_string('fullname_asc', 'block_custom_category'),
            'fullname DESC' => get_string('fullname_desc', 'block_custom_category'),
            'timecreated ASC' => get_string('timecreated_asc','block_custom_category'),
            'timecreated DESC' => get_string ('timecreated_desc', 'block_custom_category'));
    $options = array("onchange"=>'this.form.submit()');

    //falls Sortierung bearbeitet wird, keine andere Ansicht erlauben..
    if ($dirlisting->isSortEditMode()) $options['disabled'] = 'disabled';
    echo html_writer::select($choices, 'sortorder', $sortorder, null, $options);

    //Seitenaufteilung
    echo "<label for=\"perpage\">".get_string('perpage', 'block_custom_category').": </label>";
    $choices = array('2' => '2', '10' => '10','20' => '20', '50' => '50', '100' => '100');
    echo html_writer::select($choices, 'perpage', $perpage, null,  array("onchange"=>'this.form.submit()'));
    echo "</form>";

    echo "</div>";
    echo "<hr />";

    echo $OUTPUT->paging_bar($totalcount, $page, $perpage,"/course/category.php?id=$category->id&perpage=$perpage&sortorder=".urlencode($sortorder));

    $strcourses = get_string('courses');
    $strselect = get_string('select');
    $stredit = get_string('edit');
    $strdelete = get_string('delete');
    $strbackup = get_string('backup');
    $strrestore = get_string('restore');
    $strmoveup = get_string('moveup');
    $strmovedown = get_string('movedown');
    $strupdate = get_string('update');
    $strhide = get_string('hide');
    $strshow = get_string('show');
    $strsummary = get_string('summary');
    $strsettings = get_string('settings');
    $strcourse = get_string('course');
    $strcourselink = get_string('courselink', 'block_custom_category');


    echo '<form id="movecourses" action="category.php" method="post"><div>';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

    echo '<table id="courselist-table">';

    $count = 0;
    $abletomovecourses = false;  // for now
    $lastcourseid = 0; //letzte Kursid merken für Zielmarkierungen
    $lastcourselinkid = 0;

    if (empty($CFG->custom_category_coursenamelength)) {
        $CFG->custom_category_coursenamelength = 30;
    }

    //bei Verlinkungen besteht $courses aus Kurslinks und Kursen!
    foreach ($courses as $acourse) {

        $count++;

        $coursecontext = get_context_instance(CONTEXT_COURSE, $acourse->id);
        $isCourseLink = (isset($acourse->type) && ($acourse->type == 'courselink'));

        $position['afterlinkid'] = $position['beforelinkid'];
        $position['aftercourseid'] = $position['beforecourseid'];

        if ($isCourseLink) {
            //die letzte Linkid merken
            $position['beforelinkid'] = $acourse->courselinkid;
        } else {
            //die letzte Courseid merken
            $position['beforecourseid'] = $acourse->id;
        }

        //den Movelink nicht vor und nach dem aktuell gewählten Kurs ausgeben...
        $dirlisting->_printMoveCourseMarker($position);

        //erste Kurslinkmarkierung
        $dirlisting->_printCreateCourseLinkMarker($position);

        //Move - Kurslinkmarkierung
        $dirlisting->_printMoveCourseLinkMarker($position);

        $linkcss = $acourse->visible ? '' : ' class="dimmed" ';
        echo '<tr id="row_'.$acourse->id.'" class="inforow">';
        $coursename = get_course_display_name_for_list($acourse);

        if (strlen($coursename) > $CFG->custom_category_coursenamelength) {
            $coursename = substr($coursename, 0, $CFG->custom_category_coursenamelength)."...";
        }

        $icons = enrol_get_course_info_icons($acourse);

        $width = ($icons)? ((count($icons) + 1)* 25)."px":"25px";

        //Icons vor dem Kurs
        echo '<td class="enrol-icons" style="width:'.$width.'">';

        if ($isCourseLink) echo $OUTPUT->render(new pix_icon('category/icon-courselink', $strcourselink, 'theme'));
        else  echo $OUTPUT->render(new pix_icon('c/course', $strcourse));
        // print enrol info
        if ($icons) {

            foreach ($icons as $pix_icon) {
                echo $OUTPUT->render($pix_icon);
            }
        }
        echo '</td>';

        //Infoicon
        $infoicon = $OUTPUT->render(new pix_icon('i/info', $strsummary));

        $strinfo = "so: ".$acourse->sortorder;
        $strinfo .= "su: ".$acourse->suborder;
        $strinfo .= "acid ".$acourse->aftercourseid;
        $strinfo = "";

        echo '<td><a '.$linkcss.' href="view.php?id='.$acourse->id.'">'. format_string($coursename) .$strinfo.'</a></td>';

        if ($editingon) {

            if ($isCourseLink) { // Bearbeitungsmöglichkeiten für Kurslinks....

                echo '<td>';
                if (has_capability('moodle/category:manage', $context)) {

                    //Kurslink löschen
                    echo $OUTPUT->action_icon(new moodle_url('/course/category.php',
                    array('id' => $category->id, 'action' => 'deletecourselink', 'courselinkid' => $acourse->courselinkid, 'sesskey' => sesskey())),
                    new pix_icon('t/delete', get_string('delete')));

                    echo $OUTPUT->action_icon(new moodle_url('/course/category.php',
                    array('id' => $category->id, 'linktomove' => $acourse->courselinkid)),
                    new pix_icon('t/move', get_string('move')));

                    //Kurslink bewegen
                }
                echo '</td><td></td>';

            } else {

                echo '<td>';
                if (has_capability('moodle/course:update', $coursecontext)) {
                    echo $OUTPUT->action_icon(new moodle_url('/course/edit.php',
                    array('id' => $acourse->id, 'category' => $id, 'returnto' => 'category')),
                    new pix_icon('t/edit', $strsettings));
                }

                // role assignment link
                if (has_capability('moodle/course:enrolreview', $coursecontext)) {
                    echo $OUTPUT->action_icon(new moodle_url('/enrol/users.php', array('id' => $acourse->id)),
                    new pix_icon('i/users', get_string('enrolledusers', 'enrol')));
                }

                if (can_delete_course($acourse->id)) {
                    echo $OUTPUT->action_icon(new moodle_url('/course/delete.php', array('id' => $acourse->id)),
                    new pix_icon('t/delete', $strdelete));
                }

                // MDL-8885, users with no capability to view hidden courses, should not be able to lock themselves out
                if (has_capability('moodle/course:visibility', $coursecontext) && has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
                    if (!empty($acourse->visible)) {
                        echo $OUTPUT->action_icon(new moodle_url('/course/category.php',
                        array('id' => $category->id, 'page' => $page, 'perpage' => $perpage,
                                'hide' => $acourse->id, 'sesskey' => sesskey())),
                        new pix_icon('t/hide', $strhide));
                    } else {
                        echo $OUTPUT->action_icon(new moodle_url('/course/category.php',
                        array('id' => $category->id, 'page' => $page, 'perpage' => $perpage,
                                'show' => $acourse->id, 'sesskey' => sesskey())),
                        new pix_icon('t/show', $strshow));
                    }
                }

                if (has_capability('moodle/backup:backupcourse', $coursecontext)) {
                    echo $OUTPUT->action_icon(new moodle_url('/backup/backup.php', array('id' => $acourse->id)),
                    new pix_icon('i/backup', $strbackup));
                }

                if (has_capability('moodle/restore:restorecourse', $coursecontext)) {
                    echo $OUTPUT->action_icon(new moodle_url('/backup/restorefile.php', array('contextid' => $coursecontext->id)),
                    new pix_icon('i/restore', $strrestore));
                }

                //Symbol zum Verschieben...
                if (has_capability('moodle/category:manage', $context)) {

                    echo $OUTPUT->action_icon(new moodle_url('/course/category.php',
                    array('id' => $category->id, 'coursetomove' => $acourse->id)),
                    new pix_icon('t/move', get_string('move')));
                    $abletomovecourses = true;

                    if (!empty($CFG->custom_category_usecourselinks)) {
                        echo $OUTPUT->action_icon(new moodle_url('/course/category.php',
                        array('id' => $category->id, 'coursetolink' => $acourse->id)),
                        new pix_icon('category/icon-addcourselink', get_string('create_courselink', 'block_custom_category'), 'theme'));
                    }
                }

                echo '</td>';
                echo '<td align="center">';
                echo '<input type="checkbox" name="c'.$acourse->id.'" />';
                echo '</td>';
            }
        }
        echo '<td valign="top"><div id="infodiv_'.$acourse->id.'" class="courseinfo-hidden">'.directorylisting::render_CourseInformation($acourse).'</div></td>';
        echo '<td align="right">'.$infoicon.'</td>';
        echo "</tr>";
    }

    //die letzten Positionen ausgeben//
    $position['aftercourseid'] = $position['beforecourseid'];
    $position['afterlinkid'] = $position['beforelinkid'];
    $position['beforecourseid'] = 0;
    $position['beforelinkid'] = 0;

    //den Movelink nicht vor und nach dem aktuell gewählten Kurs ausgeben...
    $dirlisting->_printMoveCourseMarker($position);

    //erste Kurslinkmarkierung
    $dirlisting->_printCreateCourseLinkMarker($position);

    //Move - Kurslinkmarkierung
    $dirlisting->_printMoveCourseLinkMarker($position);

    echo '</table>';
    echo '<div id="infodock"></div>';
    echo '<div style="clear:both"></div>';

    if ($abletomovecourses) {
        echo "<table>";
        $movetocategories = array();
        $notused = array();
        make_categories_list($movetocategories, $notused, 'moodle/category:manage');
        $movetocategories[$category->id] = get_string('moveselectedcoursesto');
        echo '<tr><td colspan="3" align="right">';
        echo html_writer::select($movetocategories, 'moveto', $category->id, null, array('id'=>'movetoid'));
        $PAGE->requires->js_init_call('M.util.init_select_autosubmit', array('movecourses', 'movetoid', false));
        echo '<input type="hidden" name="id" value="'.$category->id.'" />';
        echo '</td></tr>';
        echo '</table>';
    }

    echo '</div></form>';

    //awag;
    echo "</div>";
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage,"/course/category.php?id=$category->id&perpage=$perpage&sortorder=".urlencode($sortorder));
}

echo '<div id="courselist-buttons">';
echo "<hr />";
if (has_capability('moodle/course:create', $context)) {

    $url = new moodle_url('/course/edit.php', array('category' => $category->id, 'returnto' => 'category'));
    $strnewcourse = get_string('addnewcourse');
    echo "<a href=\"{$url} title=\"{$strnewcourse}\" >".$OUTPUT->render(new pix_icon('category/icon-addcourse', $strnewcourse, 'theme')).$strnewcourse."</a>";
}

if (!empty($CFG->enablecourserequests) && $category->id == $CFG->defaultrequestcategory) {
    print_course_request_buttons(get_context_instance(CONTEXT_SYSTEM));
}
echo '</div>';

$dirlisting->print_RolesInfo($context);

//print_course_search();
echo $OUTPUT->footer();

die;

