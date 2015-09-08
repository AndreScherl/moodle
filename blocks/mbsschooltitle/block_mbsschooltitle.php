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
 * Main Class for block_mbsschooltitle
 *
 * @package   block_mbsschooltitle
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class block_mbsschooltitle extends block_base {

    public function init() {

        $this->title = get_string('pluginname', 'block_mbsschooltitle');
    }

    public function get_content() {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $renderer = $PAGE->get_renderer('block_mbsschooltitle');
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
        global $COURSE, $DB, $PAGE;

        $contextlevel = $PAGE->context->contextlevel;

        // ...get the id for users school, may be false!
        $usersschoolcatid = \local_mbs\local\schoolcategory::get_users_schoolcatid();

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
                $categoryid = $PAGE->context->instanceid;
                $schoolcatid = \local_mbs\local\schoolcategory::get_schoolcategoryid($categoryid);
                break;

            default:
                $schoolcatid = SITEID;
                break;
        }

        if (!empty($schoolcatid)) {

            if ($titledata = $DB->get_record('block_mbsschooltitle', array('categoryid' => $schoolcatid))) {

                $titledata->usersschoolid = $usersschoolcatid;
                $titledata->imageurl = \block_mbsschooltitle\local\imagehelper::get_imageurl($schoolcatid, $titledata->image);
                $titledata->editurl = $this->get_editurl($schoolcatid);

                return $titledata;
            }
        } 
        
        $titledata = new stdClass();
        $titledata->categoryid = 0;
        $titledata->imageurl = '';
        $titledata->headline = '';
        $titledata->usersschoolid = $usersschoolcatid;
        $titledata->editurl = '';

        return $titledata;
    }

    private function get_editurl($schoolcatid) {
        global $PAGE;

        $schoolcatcontext = context_coursecat::instance($schoolcatid);
        $showeditinglink = (has_capability('block/mbsschooltitle:edittitle', $schoolcatcontext));

        if ($showeditinglink) {

            $redirecturl = base64_encode($PAGE->url->out());
            $params = array('categoryid' => $schoolcatid, 'redirecturl' => $redirecturl);

            $editurl = new moodle_url('/blocks/mbsschooltitle/edittitle.php', $params);
            return $editurl->out();
        }

        return false;
    }

    public function hide_header() {
        return true;
    }

    function has_config() {
        return true;
    }

    public function applicable_formats() {
        // self test of block base class will fail if sum of the format array is zero
        // workaround: set format true for unimportant context
        return array('all' => false, 'site-index' => true);
    }

}