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

    function has_config() {
        return true;
    }

    function applicable_formats() {
        // Default case: the block can be used in courses and site index, but not in activities
        return array('all' => true);
    }

    function get_contexts_by_capability($capability, $contextlevel) {
        global $CFG, $DB, $USER;

        $sql = "SELECT DISTINCT cc.*
                FROM {role_capabilities} as rc
                JOIN {role_assignments} as ra ON rc.roleid = ra.roleid
                JOIN {context} as ctx ON ctx.id = ra.contextid
                JOIN {course_categories} as cc ON cc.id = ctx.instanceid
                WHERE capability = :capability AND userid = :userid AND ctx.contextlevel = :contextlevel";
        $result = $DB->get_records_sql($sql, array('capability' => $capability, 'userid' => $USER->id, 'contextlevel' => $contextlevel));

        if ($result)
            return $result;
        return array();
    }

    function get_managed_categories() {

        return $this->get_contexts_by_capability('moodle/category:manage', CONTEXT_COURSECAT);
    }

    /** bestimme die obersten Kategorien, in dessen Kursen man in einer bestimmten Rolle teilnimmt */
    function get_maincats_by_role() {
        global $DB, $USER, $CFG;

        if (empty($CFG->block_dlb_rolesformycategories))
            return array();

        //Pfade zu den Kursen als Trainer holen
        $sql = "SELECT DISTINCT cc.id, cc.path as path
                FROM {role_assignments} as ra
                JOIN {context} as  ctx ON ctx.id = ra.contextid
                JOIN {course} as c ON c.id = ctx.instanceid
                JOIN {course_categories} cc ON cc.id = c.category
                WHERE userid = :userid AND ctx.contextlevel = :contextlevel AND ra.roleid in (" . $CFG->block_dlb_rolesformycategories . ")";

        $pathes = $DB->get_records_sql($sql, array('userid' => $USER->id, 'contextlevel' => CONTEXT_COURSE));

        if (!$pathes)
            return array();

        //oberste Kategorien sammeln
        $maincats = array();

        foreach ($pathes as $path) {
            $cats = explode("/", trim($path->path, "/"));
            if (!empty($cats[0]))
                $maincats[$cats[0]] = $cats[0];
        }

        //falls mehr als zwei Kategorien betroffen sind, Kategorienamen holen
        if (count($maincats) == 1) {

            $cat = new stdClass();
            $cat->id = reset($maincats);
            $cat->name = get_string('myschool', 'block_dlb');

            return array($cat);
        } else {

            $catset = implode(",", $maincats);

            $sql = "SELECT id, name from {course_categories} " .
                    "WHERE id in (" . $catset . ")";

            return $DB->get_records_sql($sql);
        }
    }

    function get_required_javascript() {
        parent::get_required_javascript();

        $arguments = array(
            'id'             => $this->instance->id,
            'instance'       => $this->instance->id,
            'candock'        => $this->instance_can_be_docked(),
            'courselimit'    => 20,
            'expansionlimit' => 0,
        );
        $this->page->requires->yui_module('moodle-block_navigation-navigation',
                                          'M.block_navigation.init_add_tree',
                                          array($arguments));
    }

    function get_content() {
        global $CFG, $OUTPUT, $COURSE, $DB, $USER;

        //betreute Kursbereiche aktualisieren und in die Session schreiben
        if (!isset($USER->managed_categories)) {
            $USER->managed_categories = $this->get_managed_categories();
        }

        //oberste Kategories bestimmen, in dessen Kurse der User Trainer ist
        if (!isset($USER->hascourses_in_categories)) {
            $USER->hascourses_in_categories = $this->get_maincats_by_role();
        }

        //Block wird nicht angezeigt, falls
        if ((count($USER->managed_categories) == 0) //keine Kursbereiche zu betreuen.
                and !has_capability('moodle/user:update', context_system::instance()))  //keine Nutzungverwalterfunktionen
            return "";


        if ($this->content !== NULL) {
            return $this->content;
        }

        if (empty($this->instance)) {
            return '';
        }


        $this->content = new stdClass();
        $this->content->text = "";
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $str = "<div id=\"dlb-navigation\">";

        $nodes = array();

        //betreute Kursbereiche
        if (count($USER->managed_categories) > 0) {

            //Kursbereich bearbeiten
            $properties = array(
                'type' => navigation_node::TYPE_ROOTNODE,
                'text' => get_string('managed_categories', 'block_dlb')
            );

            $node_managedcats = new navigation_node($properties);

            //Header bearbeiten
            $properties = array(
                'type' => navigation_node::TYPE_ROOTNODE,
                'text' => get_string('managed_headers', 'block_dlb')
            );

            $node_editheader = new navigation_node($properties);
            $node_editcount = 0;

            foreach ($USER->managed_categories as $category) {

                $node_managedcats->add($category->name,
                                       new moodle_url('/course/management.php', array('categoryid' => $category->id)),
                        navigation_node::TYPE_CUSTOM);

                if (has_capability('block/custom_category:editheader', context_coursecat::instance($category->id))) {
                    $node_editheader->add($category->name, new moodle_url('/blocks/custom_category/header/index.php?', array('categoryid' => $category->id)),
                        navigation_node::TYPE_CUSTOM);
                    $node_editcount++;
                }
            }

            $nodes[] = $node_managedcats;
            if ($node_editcount > 0) $nodes[] = $node_editheader;
        }

        $renderer = $this->page->get_renderer('block_dlb');
        $str .= $renderer->navigation_tree($nodes, 2, array('depth' => '0'));
        $str .= "</div>";

        $this->content->text = $str;

        // ...display the course requests here.
        if (file_exists($CFG->dirroot.'/blocks/meineschulen/lib.php')) {
            require_once($CFG->dirroot.'/blocks/meineschulen/lib.php');
            $requests = meineschulen::get_course_requests();

            $list = '';
            $c = 0;
            foreach ($requests as $request) {
                $info = (object) array('name' => format_string($request->name), 'count' => $request->count);
                $str = get_string('viewcourserequests', 'block_meineschulen', $info);
                $list .= html_writer::tag('li', html_writer::link($request->viewurl, $str), array('class' => 'column c1'));
                $c = ($c + 1) % 2;
            }

            if (!empty($list)) {
                $this->content->text .= html_writer::tag('ul', $list, array('class' => 'meineschulen-courserequests'));
            }
        }

        return $this->content;
    }

    /**
     * Returns the role that best describes the settings block.
     *
     * @return string 'navigation'
     */
    public function get_aria_role() {
        return 'navigation';
    }

}
