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
 * Main Class for block_mbs_schooltitle
 *
 * @package   block_mbs_schooltitle
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class block_mbs_schooltitle extends block_base {
    
    public function init() {
        $this->title = get_string('pluginname', 'block_mbs_schooltitle');
    }

    public function get_content() {

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $renderer = $this->page->get_renderer('block_mbs_schooltitle');
        $this->content->text .= $renderer->render_content($this->get_titledata());

        return $this->content;
    }

    /** get all the data necessary for displaying a correct header. Note that this
     *  depends on the given context:
     * 
     *  context_user: return data from school category where user belongs to.
     *  context_course: return data from school category, where course is in.
     *  context_category: return data from school category of the subtree,
     *                    which contains the current category.
     */
    public function get_titledata() {
        global $COURSE, $USER, $DB;

        $contextlevel = $this->page->context->contextlevel;
        
        // ...get the id for users school
        $usersschoolcatid = \local_mbs\local\schoolcategory::get_users_schoolcatid($USER);
        
        switch ($contextlevel) {
            
            case CONTEXT_SYSTEM:
            case CONTEXT_USER:
                
                $schoolcatid = $usersschoolcatid;
                break;
            
            case CONTEXT_COURSE:
                $categoryid = $COURSE->category;
                $schoolcatid = \local_mbs\local\schoolcategory::get_schoolcategoryid($categoryid);
                break;

            case CONTEXT_COURSECAT:
                $categoryid = $this->page->context->instanceid;
                $schoolcatid = \local_mbs\local\schoolcategory::get_schoolcategoryid($categoryid);
                break;

            default:
                $schoolcatid = SITEID;
                break;
        }
        
        if ($schoolcatid) {

            if ($titledata = $DB->get_record('block_mbs_schooltitle', array('categoryid' => $schoolcatid))) {
                
                $titledata->usersschoolid = $usersschoolcatid;
                
                $titledata->imageurl = \block_mbs_schooltitle\local\imagehelper::get_imageurl($schoolcatid, $titledata->image);
                
                $schoolcatcontext = context_coursecat::instance($schoolcatid);
                $showeditinglink = (has_capability('block/mbs_schooltitle:edittitle', $schoolcatcontext));
                
                if ($showeditinglink) {
                    
                    $redirecturl = base64_encode($this->page->url->out());
                    $params = array('categoryid' => $schoolcatid, 'redirecturl' => $redirecturl);
                    
                    $editurl = new moodle_url('/blocks/mbs_schooltitle/edittitle.php',$params);
                    $titledata->editurl = $editurl->out();
                }
                
                return $titledata;
            }
        }
        $titledata = new stdClass();
        $titledata->categoryid = 0;
        $titledata->imageurl = '';
        $titledata->headline = '';
        $titledata->usersschoolid = $usersschoolcatid;
                
        return $titledata;
    }

    public function hide_header() {
        return true;
    }
    
    function has_config() {
        return true;
    }

}