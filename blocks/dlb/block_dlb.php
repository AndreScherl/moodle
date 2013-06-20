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
class block_dlb extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_dlb');
    }

    function has_config() {return true;}

    function applicable_formats() {
        // Default case: the block can be used in courses and site index, but not in activities
        return array('all' => true);
    }

    function get_contexts_by_capability($capability, $contextlevel) {
        global $CFG, $DB, $USER;

        $sql = "SELECT cc.*
                FROM {role_capabilities} as rc
                JOIN {role_assignments} as ra ON rc.roleid = ra.roleid
                JOIN {context} as ctx ON ctx.id = ra.contextid
                JOIN {course_categories} as cc ON cc.id = ctx.instanceid
                WHERE capability = :capability AND userid = :userid AND ctx.contextlevel = :contextlevel";
        $result = $DB->get_records_sql($sql, array('capability' =>$capability, 'userid' =>$USER->id, 'contextlevel' =>$contextlevel));

        if ($result) return $result;
        return array();
    }

    function get_managed_categories () {

        return $this->get_contexts_by_capability('moodle/category:manage', CONTEXT_COURSECAT);
    }

    /** bestimme die obersten Kategorien, in dessen Kursen man in einer bestimmten Rolle teilnimmt */
    function get_maincats_by_role() {
        global $DB, $USER, $CFG;

        if (empty($CFG->block_dlb_rolesformycategories)) return array();

        //Pfade zu den Kursen als Trainer holen
        $sql = "SELECT DISTINCT cc.id, cc.path as path
                FROM {role_assignments} as ra
                JOIN {context} as  ctx ON ctx.id = ra.contextid
                JOIN {course} as c ON c.id = ctx.instanceid
                JOIN {course_categories} cc ON cc.id = c.category
                WHERE userid = :userid AND ctx.contextlevel = :contextlevel AND ra.roleid in (".$CFG->block_dlb_rolesformycategories.")";

        $pathes = $DB->get_records_sql($sql, array('userid' =>$USER->id, 'contextlevel' => CONTEXT_COURSE));

        if (!$pathes) return array();

        //oberste Kategorien sammeln
        $maincats = array();

        foreach ($pathes as $path) {
            $cats = explode("/", trim($path->path, "/"));
            if (!empty($cats[0])) $maincats[$cats[0]] = $cats[0];
        }

        //falls mehr als zwei Kategorien betroffen sind, Kategorienamen holen
        if (count($maincats) == 1) {

            $cat = new stdClass();
            $cat->id = reset($maincats);
            $cat->name = get_string('myschool', 'block_dlb');

            return array($cat);

        } else {

            $catset = implode(",", $maincats);

            $sql = "SELECT id, name from {course_categories} ".
                    "WHERE id in (".$catset.")";

            return $DB->get_records_sql($sql);
        }
    }


    function get_content() {
        global $CFG, $OUTPUT, $COURSE, $DB, $USER;

        //betreute Kursbereiche aktualisieren und in die Session schreiben
        if (!isset($USER->managed_categories)) {
            $USER->managed_categories =  $this->get_managed_categories();
        }

        //oberste Kategories bestimmen, in dessen Kurse der User Trainer ist
        if (!isset($USER->hascourses_in_categories)) {
            $USER->hascourses_in_categories = $this->get_maincats_by_role();
        }

        //Block wird nicht angezeigt, falls
        if ((count($USER->managed_categories) == 0) //keine Kursbereiche zu betreuen.
           and !has_capability('moodle/user:update', get_context_instance(CONTEXT_SYSTEM))  //keine Nutzungverwalterfunktionen
           and !has_capability('moodle/site:dlbuploadusers', get_context_instance(CONTEXT_SYSTEM))) //keine Uploadfunktion
        return "";


        if($this->content !== NULL) {
            return $this->content;
        }

        if (empty($this->instance)) {
            return '';
        }


        $this->content = new stdClass();
        $this->content->text = "";
        $this->content->footer = '';

        $str = "<div id=\"dlb-navigation\">";

        //Falls man sich in einem Kurs befindet die übergeordnete Kategorie anzeigen
        if ($COURSE->id != SITEID) {
            $category = $DB->get_record('course_categories', array('id'=> $COURSE->category));
            $str .= "<b>".get_string('category_of_course', 'block_dlb').":</b><ul>";
            $str .= "<li><a href=\"{$CFG->wwwroot}/course/category.php?id={$category->id} \">".$category->name."</a></li></ul>";
        }

        //Kursbereiche zeigen, in denen man an Kursen teilnimmt
         $mycategories = "";
        if (count($USER->hascourses_in_categories) == 1) {

            $category = $USER->hascourses_in_categories[0];
            $mycategories = "<p><b><a href=\"{$CFG->wwwroot}/course/category.php?id={$category->id} \">".$category->name."</a></b></p>";

        } else {
              foreach ($USER->hascourses_in_categories as $category) {

                $mycategories .= "<li><a href=\"{$CFG->wwwroot}/course/category.php?id={$category->id} \">".$category->name."</a></li>";
            }

            if (!empty($mycategories)) $mycategories = "<b>".get_string('mycategories', "block_dlb").":</b><ul>".$mycategories."</ul>";
        }
        $str .=$mycategories;

        //betreute Kursbereiche
        $managecats = "";
        $link_createcourse = "";
        $link_editcategoryheader = "";
        foreach ($USER->managed_categories as $category) {
            $managecats .= "<li><a href=\"{$CFG->wwwroot}/course/category.php?id={$category->id} \">".$category->name."</a></li>";
            $link_createcourse = "<li><a href=\"{$CFG->wwwroot}/course/edit.php?category={$category->id}\">". get_string('addnewcourse')."</a></li>";

            if (has_capability('block/custom_category:editheader', context_coursecat::instance($category->id))) {

                $link_editcategoryheader .= "<li><a href=\"{$CFG->wwwroot}/blocks/custom_category/header/index.php?categoryid={$category->id}\" >".$category->name." - "
                    . get_string('editcategoryheader', 'block_dlb')."</a></li>";
            }
        }

        if (!empty($managecats)) $str .= "<b>".get_string('managed_categories','block_dlb').":</b><ul>".$managecats."</ul>";

        if (!empty($link_editcategoryheader)) {
            $str .= "<b>".get_string('managed_headers','block_dlb').":</b><ul>".$link_editcategoryheader."</ul>";
        }

        $managecourses = "<b>".get_string('courses').":</b><ul>";
        $managecourses .= $link_createcourse;
        $str .= $managecourses;

        //Nutzerverwaltung
        $manageusers = "";

        //Nutzerliste einsehen und bearbeiten
        if (has_capability('moodle/user:update', get_context_instance(CONTEXT_SYSTEM))) {
            $manageusers .= "<li><a href=\"$CFG->wwwroot/$CFG->admin/user.php\">".get_string('userlist','admin')."</a></li>";
        }

        //Nutzer hochladen
        if (has_capability('moodle/site:dlbuploadusers', get_context_instance(CONTEXT_SYSTEM))) {

            $manageusers .= "<li><a href=\"$CFG->wwwroot/admin/tool/dlbuploaduser/index.php\">".get_string('upload_users','block_dlb')."</a></li>";
        }

        if (!empty($manageusers)) {
            $str .= "<b>".get_string('accounts', 'admin').":</b><ul>".$manageusers."</ul>";
        }

        $str .= "</div>";

        $this->content->text = $str;
        return $this->content;
    }
}
