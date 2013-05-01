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
 * Library functions for the Meine Schulen block
 *
 * @package   block_meineschulen
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/blocks/meinekurse/lib.php');

/**
 * Class meineschulen
 */
class meineschulen {

    /** @var object $schoolcat the course_categories record for the school we are viewing */
    protected $schoolcat = null;
    /** @var context_coursecat $context the context for the school */
    protected $context = null;

    /**
     * @param object $schoolcat
     */
    public function __construct($schoolcat = null) {
        $this->schoolcat = $schoolcat;
        if ($this->schoolcat) {
            $this->context = context_coursecat::instance($this->schoolcat->id);
        }
    }

    /**
     * Get a list of all the schools within which the user is enroled in at least one course
     * Always lists the 'main' school and this is always the first on the list
     *
     * @return object[] containing: id, name, viewurl for each school
     */
    public static function get_my_schools() {
        global $USER, $DB;

        $schools = array();
        if ($mainschool = meinekurse_get_main_school($USER)) {
            // Make sure the 'main school' is always the first one listed.
            $schools[] = (object)array(
                'id' => $mainschool->id,
                'name' => $mainschool->name,
                'viewurl' => self::get_school_view_url($mainschool->id),
            );
        }

        // Get a list of all the categories within which the user is enroled in a course.
        $sql =  "SELECT DISTINCT ca.id, ca.path, ca.depth
                   FROM {course_categories} ca
                   JOIN {course} c ON c.category = ca.id
                   JOIN {enrol} e ON e.courseid = c.id
                   JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = :userid)
                  WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1
                    AND (ue.timeend = 0 OR ue.timeend > :now2)
                    AND ca.depth >= :schooldepth";
        $params = array(
            'userid' => $USER->id,
            'active' => ENROL_USER_ACTIVE,
            'enabled' => ENROL_INSTANCE_ENABLED,
            'now1' => time(),
            'now2' => time(),
            'schooldepth' => MEINEKURSE_SCHOOL_CAT_DEPTH,
        );
        $categories = $DB->get_records_sql($sql, $params);

        // Convert the category list into a list of IDs for the 'school' categories they fall within.
        $schoolids = array();
        foreach ($categories as $category) {
            if ($category->depth == MEINEKURSE_SCHOOL_CAT_DEPTH) {
                $schoolid = $category->id;
            } else {
                $path = explode('/', $category->path);
                if (count($path) < (MEINEKURSE_SCHOOL_CAT_DEPTH + 1)) {
                    debugging("Found bad category information - id: {$category->id}; depth: {$category->depth}; path: {$category->path}");
                    continue;
                }
                $schoolid = $path[MEINEKURSE_SCHOOL_CAT_DEPTH];
            }
            if ($mainschool && $mainschool->id == $schoolid) {
                continue;
            }
            $schoolids[$schoolid] = $schoolid;
        }

        // Use the IDs to retrieve the names of the schools.
        if (!empty($schoolids)) {
            $categories = $DB->get_records_list('course_categories', 'id', $schoolids, 'name', 'id, name');
            foreach ($categories as $category) {
                $schools[] = (object)array(
                    'id' => $category->id,
                    'name' => $category->name,
                    'viewurl' => self::get_school_view_url($category->id),
                );
            }
        }

        return $schools;
    }

    /**
     * Get a link to the given school.
     *
     * @param int $schoolid
     * @return moodle_url
     */
    public static function get_school_view_url($schoolid) {
        return new moodle_url('/blocks/meineschulen/viewschool.php', array('id' => $schoolid));
    }

    /**
     * Get a link to the school search page.
     *
     * @return moodle_url
     */
    public static function get_search_url() {
        return new moodle_url('/blocks/meineschulen/search.php');
    }

    /**
     * Can the user see the 'coordinators' area of the school page?
     *
     * @return bool
     */
    protected function can_see_coordinators() {
        return has_capability('block/meineschulen:viewcoordinators', $this->context);
    }

    /**
     * Can the user see the 'create course' link?
     *
     * @return bool
     */
    protected function can_create_course() {
        return has_capability('moodle/course:create', $this->context);
    }

    /**
     * Can the user see the 'request course' link?
     *
     * @return bool
     */
    protected function can_request_course() {
        return has_capability('moodle/course:request', $this->context);
    }

    /**
     * Return the information about the current school.
     *
     * @return string html snipet
     */
    public function output_info() {
        $out = '';

        $out .= $this->output_courses();

        if ($this->can_see_coordinators()) {
            $out .= $this->output_coordinators();
        }
        if ($this->can_create_course() || $this->can_request_course()) {
            $out .= $this->output_new_course();
        }
        $out .= $this->output_course_search();

        return html_writer::tag('div', $out, array('class' => 'meineschulen_content'));
    }

    /**
     * Return the course list box
     *
     * @return string html snipet
     */
    protected function output_courses() {
        global $DB, $PAGE;
        $out = '';

        // Javascript for the tree view.
        $jsmodule = array(
            'name' => 'block_meineschulen_collapse',
            'fullpath' => new moodle_url('/blocks/meineschulen/collapse.js'),
            'requires' => array('yui2-treeview'),
        );
        $PAGE->requires->js_init_call('M.block_meineschulen_collapse.init', array(), true, $jsmodule);

        // Get the categories + courses.
        $categories = get_categories($this->schoolcat->id, null, false);
        $catids = array_keys($categories);
        $catids[] = $this->schoolcat->id;
        $courses = $DB->get_records_list('course', 'category', $catids, 'sortorder', 'id, category, fullname');

        // Add the courses to the categories.
        $toplevelcourses = array();
        foreach ($courses as $course) {
            if (!isset($categories[$course->category])) {
                $toplevelcourses[] = $course;
            } else {
                if (!isset($categories[$course->category]->courses)) {
                    $categories[$course->category]->courses = array();
                }
                $categories[$course->category]->courses[] = $course;
            }
        }

        // Arrange the categories into the correct heirarchy.
        $toplevelcats = array();
        foreach ($categories as $category) {
            if (!isset($category->courses)) {
                $category->courses = array();
            }
            if ($category->parent == $this->schoolcat->id) {
                $toplevelcats[] = $category;
            } else {
                if (!isset($categories[$category->parent]->children)) {
                    $categories[$category->parent]->children = array();
                }
                $categories[$category->parent]->children[] = $category;
            }
        }

        // Output the categories + courses.
        foreach ($toplevelcats as $cat) {
            $out .= $this->output_category($cat);
        }
        foreach ($toplevelcourses as $course) {
            $out .= $this->output_course($course);
        }
        // Wrap the tree within a div.
        $out = html_writer::tag('ul', $out);
        $out = html_writer::tag('div', $out, array('id' => 'meineschulen_coursetree'));

        // Wrap within an outer box.
        $out = html_writer::tag('div', $out, array('class' => 'meineschulen_courses_inner'));
        $out = get_string('courselist', 'block_meineschulen').$out;
        $fullwidth = '';
        if (!$this->can_see_coordinators() && !$this->can_create_course() && !$this->can_request_course()) {
            $fullwidth = 'fullwidth';
        }
        return html_writer::tag('div', $out, array('class' => "meineschulen_courses {$fullwidth}"));
    }

    /**
     * Return a category + subcategories formatted for the courses tree.
     *
     * @param $category
     * @return string
     */
    protected function output_category($category) {
        $out = format_string($category->name);

        $children = '';
        if (isset($category->children)) {
            foreach ($category->children as $subcat) {
                $children .= $this->output_category($subcat);
            }
        }
        foreach ($category->courses as $course) {
            $children .= $this->output_course($course);
        }
        $out .= html_writer::nonempty_tag('ul', $children);
        return html_writer::tag('li', $out);
    }

    /**
     * Return a single course formatted for the courses tree.
     *
     * @param $course
     * @return string
     */
    protected function output_course($course) {
        global $OUTPUT;
        $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
        $courseicon = $OUTPUT->pix_icon('c/course', '').' ';
        $courselink = html_writer::link($courseurl, $courseicon.format_string($course->fullname));
        return html_writer::tag('li', $courselink);
    }

    /**
     * Return a formatted list of school coordinators.
     *
     * @return string
     */
    protected function output_coordinators() {
        $out = '';

        $coordinators = $this->get_coordinators();
        foreach ($coordinators as $coordinator) {
            $coordurl = new moodle_url('/user/view.php', array('id' => $coordinator->id));
            $coordlink = html_writer::link($coordurl, fullname($coordinator));
            $out .= html_writer::tag('li', $coordlink);
        }
        $out = html_writer::nonempty_tag('ul', $out);

        $out = html_writer::tag('div', $out, array('class' => 'meineschulen_coordinators_inner'));
        $out = get_string('coordinators', 'block_meineschulen').$out;

        return html_writer::tag('div', $out, array('class' => 'meineschulen_coordinators'));
    }

    /**
     * Return a list of all the users who are 'coordinators' for this school.
     *
     * @return object[]
     */
    protected function get_coordinators() {
        return get_users_by_capability($this->context, 'moodle/category:manage', 'u.id, u.firstname, u.lastname',
                                       'lastname ASC, firstname ASC');
    }

    /**
     * Return the links to create / request courses.
     *
     * @return string
     */
    protected function output_new_course() {
        $out = '';
        if ($this->can_create_course()) {
            $createurl = new moodle_url('/course/edit.php', array('category' => $this->schoolcat->id,
                                                                 'returnto' => 'category'));
            $createtext = html_writer::tag('span', get_string('createcourse', 'block_meineschulen'));
            $createlink = html_writer::link($createurl, $createtext,
                                            array('class' => 'meineschulen_createcourse_link'));
            $out .= html_writer::tag('span', get_string('createcourse', 'block_meineschulen').$createlink,
                                     array('class' => 'meineschulen_createcourse'));
            $out .= html_writer::empty_tag('br');
        }
        if ($this->can_request_course()) {
            $requesturl = new moodle_url('/course/request.php');
            $createtext = html_writer::tag('span', get_string('requestcourse', 'block_meineschulen'));
            $createlink = html_writer::link($requesturl, $createtext,
                                            array('class' => 'meineschulen_requestcourse_link'));
            $out .= html_writer::tag('span', get_string('requestcourse', 'block_meineschulen').$createlink,
                                     array('class' => 'meineschulen_requestcourse'));
            $out .= html_writer::empty_tag('br');
        }

        $out = html_writer::tag('div', $out, array('class' => 'meineschulen_newcourse_inner'));
        $out = get_string('newcourse', 'block_meineschulen').$out;

        return html_writer::tag('div', $out, array('class' => 'meineschulen_newcourse'));
    }

    /**
     * Return the course search box.
     *
     * @return string
     */
    protected function output_course_search() {
        $out = 'Search';
        return html_writer::tag('div', $out, array('class' => 'meineschulen_search'));
    }
}