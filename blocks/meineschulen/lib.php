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

    /** Number of characters to truncate results to */
    const TRUNCATE_COURSE_SUMMARY = 50;

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
        global $DB, $USER;
        static $resp = null;

        if (is_null($resp)) {
            $resp = false;
            $roles = get_roles_with_capability('moodle/course:request');
            if ($roles) {
                list($rsql, $params) = $DB->get_in_or_equal(array_keys($roles), SQL_PARAMS_NAMED);
                $params['userid'] = $USER->id;
                $resp = $DB->record_exists_select('role_assignments', "userid = :userid AND roleid $rsql", $params);
            }
        }

        return $resp;
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
        $courses = $DB->get_records_list('course', 'category', $catids, 'sortorder', 'id, category, fullname, visible');

        // Add the courses to the categories.
        $toplevelcourses = array();
        foreach ($courses as $course) {
            if (!$course->visible) {
                $coursectx = context_course::instance($course->id);
                if (!has_capability('moodle/course:viewhiddencourses', $coursectx)) {
                    continue;
                }
            }

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
            $coordurl = new moodle_url('/message/index.php', array('id' => $coordinator->id));
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
        global $PAGE;

        $jsmodule = array(
            'name' => 'block_meineschulen_search',
            'fullpath' => new moodle_url('/blocks/meineschulen/search.js'),
            'requires' => array('node', 'io-base', 'json', 'lang', 'querystring'),
        );
        $opts = array('schoolid' => $this->schoolcat->id);
        $PAGE->requires->js_init_call('M.block_meineschulen_search.init_course_search', array($opts), true, $jsmodule);

        $searchtext = trim(optional_param('search', '', PARAM_TEXT));
        $sortby = optional_param('sortby', 'name', PARAM_ALPHA);
        $sortdir = optional_param('sortdir', 'asc', PARAM_ALPHA);

        $out = '';

        $forminner = '';
        $forminner .= html_writer::input_hidden_params($PAGE->url);
        $forminner .= html_writer::empty_tag('input', array('type' => 'text', 'size' => '60', 'name' => 'search',
                                                           'value' => $searchtext, 'id' => 'meineschulen_search_text'));
        $forminner .= html_writer::empty_tag('input', arraY('type' => 'submit', 'name' => 'dosearch',
                                                           'value' => get_string('search')));
        $out .= html_writer::tag('form', $forminner, array('action' => $PAGE->url->out_omit_querystring(),
                                                          'method' => 'get',
                                                          'id' => 'meineschulen_search_form'));

        $out .= html_writer::tag('div', $this->output_course_search_results($searchtext, $sortby, $sortdir),
                                 array('id' => 'meineschulen_search_results'));

        $out = html_writer::tag('div', $out, array('class' => 'meineschulen_search_inner'));
        $out = get_string('coursesearch', 'block_meineschulen').$out;

        return html_writer::tag('div', $out, array('class' => 'meineschulen_search'));
    }

    /**
     * Generate the results of searching for courses containing the given string
     *
     * @param string $searchtext
     * @param string $sortby - name, summary
     * @param string $sortdir - asc, desc
     * @return string HTML snipet to output
     */
    public function output_course_search_results($searchtext, $sortby, $sortdir) {
        global $DB, $OUTPUT;

        if (empty($searchtext)) {
            return '';
        }

        // Handle sorting.
        $baseurl = new moodle_url('/blocks/meineschulen/viewschool.php', array('id' => $this->schoolcat->id));
        /** @var moodle_url[] $urls */
        $urls = array(
            'name' => new moodle_url($baseurl, array('search' => $searchtext, 'sortby' => 'name')),
            'summary' => new moodle_url($baseurl, array('search' => $searchtext, 'sortby' => 'summary')),
        );
        $nosorticon = ' '.$OUTPUT->pix_icon('t/sort', '');
        $icons = array(
            'name' => $nosorticon,
            'summary' => $nosorticon,
        );
        if ($sortdir == 'desc') {
            $order = ' DESC';
            $sorticon = ' '.$OUTPUT->pix_icon('t/sort_desc', '');
            $changedir = 'asc';
        } else {
            $order = ' ASC';
            $sorticon = ' '.$OUTPUT->pix_icon('t/sort_asc', '');
            $changedir = 'desc';
        }
        if ($sortby == 'summary') {
            $order = 'c.summary'.$order;
        } else {
            $order = 'c.fullname'.$order;
            $sortby = 'name';
        }
        $order .= ', c.id ASC';
        $urls[$sortby]->param('sortdir', $changedir);
        $icons[$sortby] = $sorticon;

        // Do the search.
        $sql = "SELECT c.id, c.fullname, c.summary, c.visible
                      FROM {course} c
                      JOIN {course_categories} ca ON ca.id = c.category
                     WHERE (".$DB->sql_like('c.fullname', ':searchtext1', false, false)."
                        OR ".$DB->sql_like('c.summary', ':searchtext2', false, false).")
                       AND (ca.id = :catid OR ".$DB->sql_like('ca.path', ':catpath').")
                     ORDER BY $order";
        $params = array(
            'searchtext1' => "%$searchtext%",
            'searchtext2' => "%$searchtext%",
            'catid' => $this->schoolcat->id,
            'catpath' => "{$this->schoolcat->path}/%"
        );
        $results = $DB->get_records_sql($sql, $params);

        // Start the table.
        $table = new html_table;
        $table->head = array(
            html_writer::link($urls['name'], get_string('name').$icons['name']),
            html_writer::link($urls['summary'], get_string('description').$icons['summary']),
        );
        $table->size = array('40%', '60%');

        // Output the results.
        $table->data = array();
        if ($results) {
            foreach ($results as $result) {
                if (!$result->visible) {
                    $coursectx = context_course::instance($result->id);
                    if (!has_capability('moodle/course:viewhiddencourses', $coursectx)) {
                        continue;
                    }
                }
                $name = format_string($result->fullname);
                $summary = format_string($result->summary);
                $name = self::highlight_text($searchtext, $name);
                $summary = self::highlight_text($searchtext, $summary, self::TRUNCATE_COURSE_SUMMARY);

                $courselink = new moodle_url('/course/view.php', array('id' => $result->id));
                $name = html_writer::link($courselink, $name);

                $table->data[] = array($name, $summary);
            }
        }
        if (empty($table->data)) {
            $cell = new html_table_cell(get_string('nocoursesfound', 'block_meineschulen'));
            $cell->colspan = 2;
            $table->data = array(new html_table_row(array($cell)));
        }


        return html_writer::table($table);
    }

    /**
     * Highlights all occurances of the search term within the given text and, optionally, truncates the text
     * to the given number of characters. Ensures that the truncated section of the text includes the search term
     * (by mising off characters from the start of the result string)
     *
     * @param string $searchterm the text that was searched for
     * @param string $result the text that was found
     * @param int $truncateto optional the number of characters to truncate the result to
     * @return string the truncated result, with the search term highlighted within it
     */
    protected static function highlight_text($searchterm, $result, $truncateto = null) {
        $firstinsert = '<span class="highlight">';
        $lastinsert = '</span>';

        $resultlen = strlen($result);
        if (!is_null($truncateto) && $resultlen > $truncateto) {
            $firstpos = stripos($result, $searchterm);
            $truncateto -= 1;
            $start = 0;
            if ($firstpos !== false) {
                $firstendpos = $firstpos + strlen($searchterm);
                if ($firstendpos > $truncateto) {
                    $start = ($firstendpos + 1) - $truncateto;
                }
            }
            $result = substr($result, $start, $truncateto);
            if ($start > 0) {
                $result = '&hellip;'.$result;
            }
            if ($start + $truncateto < $resultlen) {
                $result .= '&hellip;';
            }
        }

        $pos = 0;
        while (($pos = stripos($result, $searchterm, $pos)) !== false) {
            $result = substr_replace($result, $firstinsert, $pos, 0);
            $pos += strlen($firstinsert) + strlen($searchterm);
            $result = substr_replace($result, $lastinsert, $pos, 0);
            $pos += strlen($lastinsert);
        }
        return $result;
    }

    public static function output_school_search() {
        global $PAGE;

        $out = '';

        $jsmodule = array(
            'name' => 'block_meineschulen_search',
            'fullpath' => new moodle_url('/blocks/meineschulen/search.js'),
            'requires' => array('node', 'io-base', 'json', 'lang', 'querystring'),
        );
        $opts = array();
        $PAGE->requires->js_init_call('M.block_meineschulen_search.init_school_search', array($opts), true, $jsmodule);

        $searchtext = trim(optional_param('schoolname', '', PARAM_TEXT));
        $schooltype = optional_param('schooltype', -1, PARAM_INT);
        $sortby = optional_param('sortby', 'name', PARAM_ALPHA);
        $sortdir = optional_param('sortdir', 'asc', PARAM_ALPHA);
        $numberofresults = optional_param('numberofresults', 20, PARAM_INT);
        $page = optional_param('page', 0, PARAM_INT);

        $form = get_string('searchcriteria', 'block_meineschulen');
        $form .= html_writer::tag('div', self::output_search_form($searchtext, $schooltype, $numberofresults),
                                 array('class' => 'meineschulen_school_form_inner'));
        $out .= html_writer::tag('div', $form, array('class' => 'meineschulen_school_form'));


        $resultsinner = self::output_school_search_results($searchtext, $schooltype, $sortby, $sortdir, $numberofresults, $page);
        $results = get_string('searchresults', 'block_meineschulen');
        $results .= html_writer::tag('div', $resultsinner,
                                 array('id' => 'meineschulen_school_results'));
        $attrib = array('class' => 'meineschulen_school_results');
        if (empty($resultsinner)) {
            $attrib['class'] .= ' hidden';
        }
        $out .= html_writer::tag('div', $results, $attrib);

        return html_writer::tag('div', $out, array('class' => 'meineschulen_content'));
    }

    protected static function output_search_form($searchtext, $schooltype, $numberofresults) {
        global $PAGE;

        $form = '';
        $form .= html_writer::tag('label', get_string('schoolname', 'block_meineschulen'), array('for' => 'schoolname'));
        $form .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'schoolname', 'id' => 'schoolname',
                                                      'value' => $searchtext, 'size' => 80));
        $form .= html_writer::empty_tag('br', array('class' => 'clearer'));

        $opts = self::get_school_types();
        $form .= html_writer::tag('label', get_string('schooltype', 'block_meineschulen'), array('for' => 'schooltype'));
        $form .= html_writer::select($opts, 'schooltype', $schooltype, false, array('id' => 'schooltype'));
        $form .= html_writer::empty_tag('br', array('class' => 'clearer'));

        $opts = array(10, 20, 50, 100);
        $opts = array_combine($opts, $opts);
        $opts[-1] = get_string('allresults', 'block_meineschulen');
        $form .= html_writer::tag('label', get_string('numberofresults', 'block_meineschulen'), array('for' => 'numberofresults'));
        $form .= html_writer::select($opts, 'numberofresults', $numberofresults, false, array('id' => 'numberofresults'));
        $form .= html_writer::empty_tag('br', array('class' => 'clearer'));

        $form .= html_writer::tag('label', '', array('for' => 'submitbutton'));
        $form .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'search', 'class' => 'submitbutton',
                                                      'id' => 'submitbutton', 'value' => get_string('search')));
        $form .= html_writer::empty_tag('br', array('class' => 'clearer'));

        return html_writer::tag('form', $form, array('action' => $PAGE->url, 'method' => 'get',
                                                    'id' => 'meineschulen_school_form'));
    }

    protected static function get_school_types() {
        global $DB;

        static $types = null;
        if (is_null($types)) {
            $types = $DB->get_records_menu('course_categories', array('depth' => 1), 'name', 'id, name');
            $types = array(-1 => get_string('alltypes', 'block_meineschulen')) + $types;
        }

        return $types;
    }

    public static function output_school_search_results($searchtext, $schooltype, $sortby, $sortdir, $numberofresults, $page) {
        global $DB, $OUTPUT, $PAGE;

        if (empty($searchtext)) {
            return '';
        }

        // Handle sorting.
        $baseurl = new moodle_url('/blocks/meineschulen/search.php', array(
                                                                          'schoolname' => $searchtext,
                                                                          'schooltype' => $schooltype,
                                                                          'numberofresults' => $numberofresults,
                                                                     ));
        /** @var moodle_url[] $urls */
        $urls = array(
            'name' => new moodle_url($baseurl, array('sortby' => 'name')),
            'type' => new moodle_url($baseurl, array('sortby' => 'type')),
        );
        $nosorticon = ' '.$OUTPUT->pix_icon('t/sort', '');
        $icons = array(
            'name' => $nosorticon,
            'type' => $nosorticon,
        );
        if ($sortdir == 'desc') {
            $order = ' DESC';
            $sorticon = ' '.$OUTPUT->pix_icon('t/sort_desc', '');
            $changedir = 'asc';
        } else {
            $order = ' ASC';
            $sorticon = ' '.$OUTPUT->pix_icon('t/sort_asc', '');
            $changedir = 'desc';
        }
        if ($sortby == 'type') {
            $order = 't.name'.$order.', sch.name ASC';
        } else {
            $order = 'sch.name'.$order;
            $sortby = 'name';
        }
        $order .= ', sch.id ASC';
        $urls[$sortby]->param('sortdir', $changedir);
        $icons[$sortby] = $sorticon;

        // Do the search.
        $typecriteria = '';
        $params = array(
            'searchtext1' => "%$searchtext%",
            'searchtext2' => "%$searchtext%",
            'schooldepth' => MEINEKURSE_SCHOOL_CAT_DEPTH,
        );
        if ($schooltype > 0) {
            $typecriteria = 'AND t.id = :schooltype';
            $params['schooltype'] = $schooltype;
        }
        $fields = " SELECT sch.id, sch.name, t.name AS type, sch.visible";
        $select = "   FROM {course_categories} sch
                      JOIN {course_categories} t ON t.depth = 1 AND sch.path LIKE CONCAT('/', t.id, '/%')
                     WHERE ".$DB->sql_like('sch.name', ':searchtext1', false, false)."
                       AND sch.depth = :schooldepth
                           $typecriteria
                     ORDER BY $order";
        $totalcount = $DB->count_records_sql("SELECT COUNT(*)".$select, $params);
        $limitnum = $numberofresults;
        if ($limitnum <= 0) {
            // Show all results.
            $limitnum = 0;
            $start = 0;
        } else {
            $start = $numberofresults * $page;
            if ($start > $totalcount) {
                // Page does not exist - go to first page.
                $start = 0;
                $page = 0;
            }
        }
        $results = $DB->get_records_sql($fields.$select, $params, $start, $limitnum);

        // Start the table.
        $table = new html_table;
        $table->head = array(
            html_writer::link($urls['name'], get_string('name').$icons['name']),
            html_writer::link($urls['type'], get_string('schooltype', 'block_meineschulen').$icons['type']),
        );
        $table->size = array('60%', '40%');

        // Output the results.
        if ($results) {
            $table->data = array();
            foreach ($results as $result) {
                if (!$result->visible) {
                    $catcontext = context_coursecat::instance($result->id);
                    if (!has_capability('moodle/category:viewhiddencategories', $catcontext)) {
                        continue;
                    }
                }
                $name = format_string($result->name);
                $type = format_string($result->type);
                $name = self::highlight_text($searchtext, $name);

                $schoolurl = new moodle_url('/blocks/meineschulen/viewschool.php', array('id' => $result->id));
                $name = html_writer::link($schoolurl, $name);

                $table->data[] = array($name, $type);
            }
        }
        if (empty($table->data)) {
            $cell = new html_table_cell(get_string('noschoolsfound', 'block_meineschulen'));
            $cell->colspan = 2;
            $table->data = array(new html_table_row(array($cell)));
        }

        $baseurl = new moodle_url($PAGE->url, array('schoolname' => $searchtext, 'schooltype' => $schooltype,
                                                   'sortby' => $sortby, 'sortdir' => $sortdir,
                                                   'numberofresults' => $numberofresults));

        $out = html_writer::table($table);
        if ($numberofresults > 0) { // No paging bar for 'All results'.
            $out .= $OUTPUT->paging_bar($totalcount, $page, $numberofresults, $baseurl);
        }

        return $out;
    }
}