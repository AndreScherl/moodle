<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/theme/bootstrap/renderers/core_renderer.php");

class theme_mebis_core_renderer extends theme_bootstrap_core_renderer {

    protected $header_renderer;
    protected $footer_renderer;
    protected $help_renderer;

    public function __construct(\moodle_page $page, $target) {
        parent::__construct($page, $target);
        $this->header_renderer = new theme_mebis_header_renderer($page, $target);
        $this->footer_renderer = new theme_mebis_footer_renderer($page, $target);
        $this->help_renderer = new theme_mebis_help_renderer($page, $target);
    }

    public function main_navbar() {
        return $this->header_renderer->main_navbar();
    }

    public function main_sidebar() {
        return $this->header_renderer->main_sidebar();
    }

    public function main_header($isCourse = false) {
        return $this->header_renderer->main_header($isCourse);
    }

    public function main_footer() {
        return $this->footer_renderer->main_footer();
    }

    public function main_eventfooter() {
        return $this->footer_renderer->main_eventfooter();
    }

    public function main_searchbar() {
        return $this->footer_renderer->main_searchbar();
    }

    public function main_breadcrumbs() {
        return $this->header_renderer->main_breadcrumbs();
    }

    public function main_menubar($isCourse) {
        return $this->header_renderer->main_menubar($isCourse);
    }

    public function page_action_navigation() {
        return $this->help_renderer->page_action_navigation();
    }

    public function render_adminnav_selectbox() {
        return $this->help_renderer->get_adminnav_selectbox();
    }

    /**
     * Renders a block in bootstrap in the new mebis design
     * @param block_contents $bc
     * @param type $region
     * @return String Html string of the block
     */
    public function block(block_contents $bc, $region) {
        // top region blocks (see theme_mebis_help_renderer) are returned just the way they are
        /*if ($region === 'top') {
            return $bc->content;
        }*/

        $bc = clone($bc); // Avoid messing up the object passed in.
        /*
          $bc->tag = 'h2';
          $bc->action_toggle = true;
         */

        if (empty($bc->blockinstanceid) || !strip_tags($bc->title)) {
            $bc->collapsible = block_contents::NOT_HIDEABLE;
        }
        if (!empty($bc->blockinstanceid)) {
            $bc->attributes['data-instanceid'] = $bc->blockinstanceid;
        }
        $skiptitle = strip_tags($bc->title);
        if ($bc->blockinstanceid && !empty($skiptitle)) {
            $bc->attributes['aria-labelledby'] = 'instance-' . $bc->blockinstanceid . '-header';
        } else if (!empty($bc->arialabel)) {
            $bc->attributes['aria-label'] = $bc->arialabel;
        }
        if ($bc->dockable) {
            $bc->attributes['data-dockable'] = 1;
        }
        if ($bc->collapsible == block_contents::HIDDEN) {
            $bc->add_class('hidden');
        }
        if (!empty($bc->controls)) {
            $bc->add_class('block_with_controls');
        }

        $rowblocks = array('mbsmycourses', 'mbsmyschools');
        foreach($rowblocks as $block){
            if ($bc->attributes['data-block'] == $block) {
                $bc->attributes['class'] = 'block_'.$block.' row';
            }
        }

        if (empty($skiptitle)) {
            $output = '';
            $skipdest = '';
        } else {
            $output = html_writer::tag('a', get_string('skipa', 'access', $skiptitle), array('href' => '#sb-' . $bc->skipid, 'class' => 'skip-block')
            );
            $skipdest = html_writer::tag('span', '', array('id' => 'sb-' . $bc->skipid, 'class' => 'skip-block-to'));
        }

        $full = array('mbsmycourses', 'mbsmyschools', 'mbsgettingstarted');

        $transparent = array('mbsmycourses', 'mbsmyschools');

        if (in_array($bc->attributes['data-block'], $full) || $region == 'admin-navi') {
            $tr = in_array($bc->attributes['data-block'], $transparent) ? ' block-transparent' : '';
            $output .= html_writer::start_tag('div', array('class' => 'col-md-12' . $tr));
        } else {
            $output .= html_writer::start_tag('div', array('class' => 'col-md-4 dragblock'));
        }

        $output .= html_writer::start_tag('div', $bc->attributes);

        $output .= $this->mebis_block_header($bc);
        //$output .= $this->block_header($bc);
        $output .= $this->block_content($bc);

        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

        $output .= $this->block_annotation($bc);

        //$output .= $skipdest;

        $this->init_block_hider_js($bc);
        return $output;
    }

    /** render a block for the mebis design.
     * 
     * This method is based on block_header method and does some different rendering
     * on special blocks, which are:
     * 
     * - noactionblocks: hide action menu by resetting block controls.
     * - combine rendering of headers for mbsmycourses and mbsnewcourse blocks
     * 
     * @param block_content $bc
     * @return string HTML for the header of the block
     */
    public function mebis_block_header($bc) {
        global $OUTPUT;
        $noactionblocks = array('mbsmycourses'); // No actions for this blocks.
        $nomoveblocks = array('mbsmyschools'); // Just don't move these blocks.
        
        // Unset block actions for special blocks.
        if (in_array($bc->attributes['data-block'], $noactionblocks)) {
            $bc->controls = array();
        }
        
        // Remove the move and config action of this block.
        if (in_array($bc->attributes['data-block'], $nomoveblocks)) {
            $ctx = context_block::instance($bc->blockinstanceid);
            $toremove = array();
            for ($i=0; $i<count($bc->controls); $i++) {
                if (($bc->controls[$i]->attributes['class'] === 'editing_move menu-action'
                        || $bc->controls[$i]->attributes['class'] === 'editing_edit menu-action')
                        && !has_capability('block/mbsmyschools:moveblock', $ctx)) {
                    $toremove[] = $i;
                }
            }
            for($i=0; $i<count($toremove); $i++) {
                unset($bc->controls[$toremove[$i]]);
            }
        }
        
        // Use core methode to render blocks.
        if ($bc->attributes['data-block'] != 'mbsmycourses') {
            return parent::block_header($bc);
        }
        
        // From here on: Special rendering of mbsmycourses 
        // for proper position of mbsnecourses block
        // 
        // This is exceptionally done for this theme, so we decided to do this in the
        // renderer and do intentionally not create a general dependency between the
        // block mbsmycourses and mbs newcourse.
        if ($bc->attributes['data-block'] == 'mbsmycourses') {
            $title = '';
            if ($bc->title) {
                $attributes = array();
                if ($bc->blockinstanceid) {
                    $attributes['id'] = 'instance-' . $bc->blockinstanceid . '-header';
                }
                $title = html_writer::tag('h2', $bc->title, $attributes);
            }

            $output = '';
        
            if ($title) {
                $output .= html_writer::start_div('header');
                $output .= html_writer::start_div('title');
                $output .= $OUTPUT->raw_block('mbsnewcourse');
                $output .= $title;
                $output .= html_writer::end_div();
                $output .= html_writer::end_div();
            }
            return $output;
        }         
    }
    
    /**
     * Renders a block region in bootstrap in the new mebis design
     * @param type $region
     * @param type $classes
     * @param type $tag
     * @return String Html string of the block region
     */
    public function mebis_blocks($region, $classes = array(), $tag = 'aside', $droptarget = '1') {
        
        $displayregion = $this->page->apply_theme_region_manipulations($region);
        $classes = (array) $classes;
        $classes[] = 'block-region';
        $attributes = array(
            'id' => 'block-region-' . preg_replace('#[^a-zA-Z0-9_\-]+#', '-', $displayregion),
            'class' => join(' ', $classes),
            'data-blockregion' => $displayregion,
            'data-droptarget' => $droptarget
        );
        $content = '';
        if ($this->page->blocks->region_has_content($displayregion, $this)) {
            $content .= $this->blocks_for_region($displayregion);
        } else {
            $content .= '';
        }

        return $content;
    }
    
    /**
     * Renders a custom block region.
     * 
     * Andre Scherl - 30.05.2015: overwrite this method to prevent the content region (custom block region of /my) to be a drop target
     *
     * Use this method if you want to add an additional block region to the content of the page.
     * Please note this should only be used in special situations.
     * We want to leave the theme is control where ever possible!
     *
     * This method must use the same method that the theme uses within its layout file.
     * As such it asks the theme what method it is using.
     * It can be one of two values, blocks or blocks_for_region (deprecated).
     *
     * @param string $regionname The name of the custom region to add.
     * @return string HTML for the block region.
     */
    public function custom_block_region($regionname) {
        if ($this->page->theme->get_block_render_method() === 'blocks') {
            return $this->mebis_blocks($regionname, array(), 'div', '0');
        } else {
            return $this->blocks_for_region($regionname);
        }
    }
    
    /**
     * Output a place where the block that is currently being moved can be dropped.
     *
     * Andre Scherl - 12.06.2015: overwrite this method to render drop target container correctly
     * 
     * @param block_move_target $target with the necessary details.
     * @param array $zones array of areas where the block can be moved to
     * @param string $previous the block located before the area currently being rendered.
     * @param string $region the name of the region
     * @return string the HTML to be output.
     */
    public function block_move_target($target, $zones, $previous, $region) {
        // Some regions of my-page should not be move targets.
        if($this->page->pagetype === 'my-index') {
            // No target regions.
            $ntregions = array('top', 'bottom', 'content');
            if (in_array($region, $ntregions)) {
                return;
            }
        }
        
        if ($previous == null) {
            if (empty($zones)) {
                // There are no zones, probably because there are no blocks.
                $regions = $this->page->theme->get_all_block_regions();
                $position = get_string('moveblockinregion', 'block', $regions[$region]);
            } else {
                $position = get_string('moveblockbefore', 'block', $zones[0]);
            }
        } else {
            $position = get_string('moveblockafter', 'block', $previous);
        }
        $output = html_writer::tag('a', html_writer::tag('span', $position, array('class' => 'accesshide')),
                array('href' => $target->url, 'class' => 'blockmovetarget'));
        
        // Put output in div to meet bootstrap requirements.
        return html_writer::div($output, 'col-md-1');
    }

    /**
     * Renders a "single button" widget for insertion into the menu bar
     *
     * @param single_button $button
     * @return string HTML fragment
     * 
     * awag - 24.04.2015: overriding this method leads get calls. In many cases moodle
     * expected a post via a form, so this will break functionality.
     * 
     * I have done a little investigation to see, why this was done by trio and 
     * cannot see a case where this should be necessary.
     *  
     * For the first step i decided to keep the old code as a reference.
     * 
     * DO NOT COMMENT that in!!!
     * 
     * @TODO delete this commented code fragment.
     */
    /* protected function render_single_button(single_button $button) {
      $output = html_writer::tag('a', $button->label, array('class' => 'internal', 'href' => $button->url));
      return html_writer::tag('li', $output);
      } */

    /** create a fake block in given region. This is a approach to embed blocks
     *  without creating an instance by using database table "mdl_block_instances".
     * 
     * @global type $PAGE
     * @param type $blockname
     * @param type $region
     * @return boolean
     */
    public function add_fake_block($blockname, $region, array $attributes = null) {
        global $PAGE;
        
        if (!$blockinstance = block_instance($blockname)) {
            return false;
        }
        $bc = new block_contents();
        $bc->content = $blockinstance->get_content()->text;
        if ($attributes != null) {
            $bc->attributes = $attributes;
        }
        $PAGE->blocks->add_fake_block($bc, $region);
        return true;
    }

    /** get the raw content (i. e. text) of a block, without creating an instance by using
     * database table "mdl_block_instances".
     * This is used to generate "sticky" blocks, which are outputted on every page in the
     * layout file.
     * 
     * Note that capabilities and return value of method applicable_formats() 
     * of these blocks should prevent users from creating instances on special pages.
     * 
     * @param type $blockname
     * @return string
     */
    public function raw_block($blockname) {
        if (!$blockinstance = block_instance($blockname)) {
            return '';
        }
        return $blockinstance->get_content()->text;
    }
    
    /**
     * render the mbswizzard block, if needed
     * 
     * @global object $USER
     * @global object $PAGE
     * @param string $region Region to Render the block
     * @param bool $blockdependency - block is needed by other block, e.g. mbsgettingstarted
     * @return string - htmlstring of block contents 
     */    
    public function add_block_mbswizzard_if_needed($region, $blockdependency = false) {
        global $USER, $PAGE;
        if(($blockdependency || (isset($USER->mbswizzard_activesequence) && ($USER->mbswizzard_activesequence != false)))
            && !$PAGE->blocks->is_block_present('mbswizzard')) {
            $attr['data-block'] = 'block_mbswizzard';
            $attr['class'] = 'block_mbswizzard block';
            return $this->add_fake_block('mbswizzard', $region, $attr);
        } else {
            return '';
        }
    }
       
    /**
     * Print the mebis footer containing the search and schooltitle block
     * 
     * @return string
     */
    public function mebis_footer() {
        $output = '';
        $output .= $this->raw_block('mbssearch');
        $output .= $this->raw_block('mbsschooltitle');
        return $output;
    }

    /** adding debug output for profile form, this is very useful to check, whether 
     * sessionvariables are correctly retrieved from IDM.
     *  
     * To output $USER object you have to turn on debug mode and visit users 
     * profile edit page. 
     * 
     * Originally there was a similar option up to moodle 2.5, but unfortunately it was
     * removed for this version of moodle.
     */

    public function standard_footer_html() {
        global $CFG, $USER, $PAGE, $OUTPUT;

        if ($CFG->debugdisplay && debugging('', DEBUG_DEVELOPER)) {  // Show user object
            if (($PAGE->pagetype == 'user-edit') or ($PAGE->pagetype == 'user-editadvanced')) {

                echo html_writer::tag('div', '', array('class' => 'clearfix'));
                echo $OUTPUT->heading('DEBUG MODE:  User session variables');
                echo html_writer::start_tag('div', array('style' => 'text-align:left'));
                print_object($USER);
                echo html_writer::end_tag('div');
            }
        }

        return parent::standard_footer_html();
    }

}

// The following code embeds the mediathek player in the 'preview' page when inserting video/audion
require_once($CFG->libdir . '/medialib.php');

class core_media_player_mediathek extends core_media_player_external {

    protected function embed_external(moodle_url $url, $name, $width, $height,
                                      $options) {
        global $DB;
        $hash = $this->matches[1];
        if ($desturl = $DB->get_field('repository_mediathek_link', 'url', array('hash' => $hash))) {
            return '<iframe style="height:300px;width:400px;" src="' . $desturl . '"></iframe>';
        }

        return core_media_player::PLACEHOLDER;
    }

    protected function get_regex() {
        global $CFG;
        $basepath = preg_quote("{$CFG->wwwroot}/repository/mediathek/link.php?hash=");
        $regex = "%{$basepath}([a-z0-9]*)(&|&amp;)embed=1%";
        return $regex;
    }

    public function get_rank() {
        return 1020;
    }

    public function get_embeddable_markers() {
        return array('repository/mediathek/link.php');
    }

    public function is_enabled() {
        return true;
    }

}

/** Note, that there is another constant in local_mbs\local\schoolcategory,
 *  which holds the catdepth of school categories.
 *  if category structure would be changed, both constants must be adapted!
 */
define('DLB_SCHOOL_CAT_DEPTH', 3);

class theme_mebis_core_course_management_renderer extends core_course_management_renderer {

    /** get (and cache) the category ids below an optional level (level == 3 for school-catgories), where
     *  the user has the capability moodle/category:manage or moodle/course:create
     *
     * @global type $USER
     * @param type $category
     */
    protected function get_editable_schoolids($level = DLB_SCHOOL_CAT_DEPTH) {
        global $USER, $DB;

        if (!empty($USER->editableschoolids)) {
            return $USER->editableschoolids;
        }

        // get roleids with caps.
        $sql = "SELECT DISTINCT rc.roleid FROM {role_capabilities} rc
                JOIN {role_context_levels} rcl ON rcl.roleid = rc.roleid
                WHERE rcl.contextlevel = ? and (rc.capability = ? OR rc.capability = ?)";

        $params = array(CONTEXT_COURSECAT, 'moodle/category:manage', 'moodle/course:create');

        if (!$roleids = $DB->get_fieldset_sql($sql, $params)) {
            return array();
        }

        // now get the category ids below that special level.
        list($inroleids, $params) = $DB->get_in_or_equal($roleids);
        $params[] = $USER->id;
        $params[] = CONTEXT_COURSECAT;
        $params[] = $level;

        $sql = "SELECT DISTINCT cat.id, cat.path FROM {context} ctx
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                JOIN {course_categories} cat on ctx.instanceid = cat.id
                WHERE ra.roleid {$inroleids} and ra.userid = ? and ctx.contextlevel = ? and ctx.depth >= ?";

        if (!$catdata = $DB->get_records_sql($sql, $params)) {
            return array();
        }

        // level of retrieved cats may be higher than school cat (normally level == 3)
        // so retrieve the id of the parent of the school category at level 3.
        $categoryids = array();

        foreach ($catdata as $catdate) {
            $parents = explode('/', $catdate->path);
            if (!empty($parents[$level])) {
                $categoryids[$parents[$level]] = $parents[$level];
            }
        }

        $USER->editableschoolids = $categoryids;

        return $categoryids;
    }

    /** check, wheter this category can be managed,
     *  i. e. at least one of given editable categories is a parent of this
     *  category or this category is called directly.
     *
     * @param object $category, category object.
     * @param array $parentids, list of possible parents.
     * @return boolean, true if one of the parent id is in the parent list of the category.
     */
    protected function can_manage_category($category, $editablecatids) {

        if (empty($category)) {
            return false;
        }

        $catidstocheck = $category->get_parents();

        // possibility to manage main category.
        $catidstocheck[] = $category->id;

        $result = array_intersect($editablecatids, $catidstocheck);

        return (count($result) > 0);
    }

    /**
     * Presents a course category listing.
     *
     * @param coursecat $category The currently selected category. Also the category to highlight in the listing.
     * @return string
     */
    public function category_listing(coursecat $category = null) {
        global $PAGE;

        $perfdebug = optional_param('perfdebug', 0, PARAM_INT);

        if (optional_param('purge', 0, PARAM_INT) == 1) {
            cache_helper::purge_by_event('changesincoursecat');
            if ($perfdebug) {
                echo "<br/>cache purged";
            }
        }
        $starttime = microtime(true);

        if ($category === null) {
            $selectedparents = array();
            $selectedcategory = null;
        } else {
            $selectedparents = $category->get_parents();
            $selectedparents[] = $category->id;
            $selectedcategory = $category->id;
        }
        $catatlevel = \core_course\management\helper::get_expanded_categories('');
        $catatlevel[] = array_shift($selectedparents);
        $catatlevel = array_unique($catatlevel);

        // +++ awag: get all editable schools //
        $listings = array();

        $datatime = 0;
        $startdatatime = microtime(true);
        // don't restrict the list for site-admins.
        if (is_siteadmin()) {

            $listings[] = coursecat::get(0)->get_children();
        } else { // non site admins.
            // get schoolids (category of level 3), which contains elements (category, subcategories or courses) this user can edit.
            $editableschoolids = $this->get_editable_schoolids();

            // when required category is not in editable school, redirect the user, when he is no siteadmin.
            $usercanedit = (!empty($editableschoolids) && $this->can_manage_category($category, $editableschoolids));

            if (!$usercanedit) {
                $param = (isset($category)) ? array('categoryid' => $category->id) : array();
                $url = new moodle_url('/course/index.php', $param);
                redirect($url);
            }

            // prepare listings data for rendereing.
            foreach ($editableschoolids as $catid) {

                $coursecat = coursecat::get($catid);

                if (in_array($catid, $selectedparents)) {
                    $catatlevel[] = $catid;
                    $catatlevel = array_unique($catatlevel);
                }
                $listings[] = array($catid => $coursecat);
            }
        }
        $datatime += (microtime(true) - $startdatatime);
        // --- awag;

        $attributes = array(
            'class' => 'ml',
            'role' => 'tree',
            'aria-labelledby' => 'category-listing-title'
        );

        $html = html_writer::start_div('category-listing');
        $html .= html_writer::tag('h3', get_string('categories'), array('id' => 'category-listing-title'));
        $html .= $this->category_listing_actions($category);

        // +++ awag: print out all editable schools, like original renders but in a loop.


        $rendertime = 0;

        foreach ($listings as $listing) {

            $html .= html_writer::start_tag('ul', $attributes);
            foreach ($listing as $listitem) {
                // Render each category in the listing.
                $subcategories = array();
                if (in_array($listitem->id, $catatlevel)) {
                    $startdatatime = microtime(true);
                    $subcategories = $listitem->get_children();
                    $datatime += (microtime(true) - $startdatatime);
                }
                $startrendertime = microtime(true);
                $html .= $this->category_listitem(
                        $listitem, $subcategories, $listitem->get_children_count(), $selectedcategory, $selectedparents
                );
                $rendertime += (microtime(true) - $startrendertime);
            }
            $html .= html_writer::end_tag('ul');
        }
        $html .= $this->category_bulk_actions($category);
        $html .= html_writer::end_div();

        if ($perfdebug) {
            echo "<br/>category_listing: " . (microtime(true) - $starttime);
            echo "<br/>datatime: " . $datatime;
            echo "<br/>renderttime: " . $rendertime;
        }

        return $html;
    }

}

class theme_mebis_core_renderer_maintenance extends theme_mebis_core_renderer {
    
}
