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

        $sql = "SELECT cc.*
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
        global $CFG;

        $this->page->requires->js_module('core_dock');

        $arguments = array('id' => $this->instance->id, 'instance' => $this->instance->id, 'candock' => $this->instance_can_be_docked());
        $this->page->requires->yui_module(array('core_dock', 'moodle-block_navigation-navigation'), 'M.block_navigation.init_add_tree', array($arguments));
        user_preference_allow_ajax_update('docked_block_instance_' . $this->instance->id, PARAM_INT);
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
                and !has_capability('moodle/user:update', get_context_instance(CONTEXT_SYSTEM))  //keine Nutzungverwalterfunktionen
                and !has_capability('moodle/site:dlbuploadusers', get_context_instance(CONTEXT_SYSTEM))) //keine Uploadfunktion
            return "";


        if ($this->content !== NULL) {
            return $this->content;
        }

        if (empty($this->instance)) {
            return '';
        }


        $this->content = new stdClass();
        $this->content->text = "";
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
                
                $node_managedcats->add($category->name, new moodle_url('/course/category.php', array('id' => $category->id)), 
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

        $nodes_user = array();
        
        //Nutzerliste einsehen und bearbeiten
        if (has_capability('moodle/user:update', get_context_instance(CONTEXT_SYSTEM))) {
            
             $properties = array(
                'type' => navigation_node::TYPE_CUSTOM,
                'text' => get_string('userlist', 'admin'),
                'action' => new moodle_url('/admin/user.php'), 
            );
            
            $nodes_user[] = new navigation_node($properties);
        }

        //Nutzer hochladen
        if (has_capability('moodle/site:dlbuploadusers', get_context_instance(CONTEXT_SYSTEM))) {

             $properties = array(
                'type' => navigation_node::TYPE_CUSTOM,
                'text' => get_string('upload_users', 'block_dlb'),
                'action' => new moodle_url('/admin/tool/dlbuploaduser/index.php'), 
            );
            
            $nodes_user[] = new navigation_node($properties);
        }

        if (count($nodes_user) > 0) {
            
            $properties = array(
                'type' => navigation_node::TYPE_ROOTNODE,
                'text' => get_string('manage_users', 'block_dlb')
            );

            $node_manageusers = new navigation_node($properties);
            
            foreach ($nodes_user as $node) {
                $node_manageusers->add_node($node);
            }
            $nodes[] = $node_manageusers;
        }

        $renderer = $this->page->get_renderer('block_dlb');
        $str .= $renderer->navigation_tree($nodes, 2, array('depth' => '0'));
        $str .= "</div>";

        $this->content->text = $str;
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
