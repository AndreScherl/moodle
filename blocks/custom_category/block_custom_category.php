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

require_once($CFG->dirroot."/blocks/moodleblock.class.php");

class block_custom_category extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_custom_category');
    }

    function applicable_formats() {
        // Default case: the block can be used in courses and site index, but not in activities
        return array('all' => false, 'site' => true);
    }

    function get_content() {
        global $CFG, $OUTPUT;


        if($this->content !== NULL) {
            return $this->content;
        }

        if (empty($this->instance)) {
            return '';
        }

        $this->content = new stdClass();
        $this->content->text = "";
        $this->content->footer = '';

        return $this->content;
    }

    public static function get_headerdata() {
        global $CFG, $DB, $PAGE;

        $headerdata = new stdClass();

        $category = $PAGE->category;
        if (!isset($category)) return $headerdata;


        $editingon = $PAGE->user_is_editing();
        
        if ($editingon and 
                (has_capability('block/custom_category:editheader', $PAGE->context) or
                 has_capability('block/custom_category:editheaderimage', $PAGE->context)))  {

            $headerdata->editlink = new moodle_url('/blocks/custom_category/header/index.php', array('categoryid' => $category->id));
        }
        
        $catstosearch = explode("/", trim($category->path, "/"));
        $sql = "SELECT categoryid, background_image, headline FROM {block_custom_category} WHERE categoryid IN (".implode(",", $catstosearch).")";

        $parentheader = $DB->get_records_sql($sql);

        if (!$parentheader) return $headerdata;

        $catstosearch = array_reverse($catstosearch);

        $headerdata->headline = "";
        $page_headerbackground = "";

        foreach ($catstosearch as $catid) {

            if (!isset($parentheader[$catid])) continue;

            $cat = $parentheader[$catid];

            if (empty($headerdata->headline) and !empty($cat->headline)) $headerdata->headline = $cat->headline;
            if (empty($headerdata->background) and !empty($cat->background_image)) {

                $context = get_context_instance(CONTEXT_COURSECAT, $cat->categoryid);
                $headerdata->background = "{$CFG->wwwroot}/pluginfile.php/{$context->id}/coursecat/description/header/".$cat->background_image;
            }

            if (!empty($headerdata->background) and !empty($headerdata->headline)) return $headerdata;
        }
        return $headerdata;
    }
}
