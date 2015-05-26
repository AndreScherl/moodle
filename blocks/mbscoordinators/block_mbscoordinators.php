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
 * main class for block coordinators.
 *
 * @package    block_mbscoordinators
 * @copyright  Andre Scherl <andre.scherl@isb.bayern.de>
 * @license    todo
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/mbscoordinators/renderer.php');

class block_mbscoordinators extends block_base {

    protected $seecoordinators = null;

    public function init() {
        $this->title = get_string('pluginname', 'block_mbscoordinators');
    }

    public function get_content() {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        /*if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }*/

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if ($PAGE->context->contextlevel != CONTEXT_COURSECAT) {
            return $this->content;
        }
        
        $renderer = $PAGE->get_renderer('block_mbscoordinators');
        $this->content->text .= $renderer->render_categoryheader($PAGE->category);

        if (!$this->can_see_coordinators()) {
            return $this->content;
        }

        if ($coordinators = $this->get_coordinators()) {
            
            $this->content->text .= $renderer->render_coordinators($coordinators);
        }

        return $this->content;
    }

    /**
     * Can the user see the 'coordinators' area of the course category pages?
     *
     * @return bool
     */
    public function can_see_coordinators() {
        global $DB, $USER, $PAGE;

        if (is_null($this->seecoordinators)) {

            $this->seecoordinators = false;

            // ... $USER->isTeacher is a flag, which is set to 1 during login via shibboleth
            // for all users, who owns the teacherrole in the IDM of mebis.
            // We are NOT happy with this solution, maybe the use of this flag will be changed in the future.

            if (!empty($USER->isTeacher)) {
                $this->seecoordinators = true;
            } else if (has_capability('block/mbscoordinators:viewcoordinators', $PAGE->context)) {
                // Has the capability in the current context.
                $this->seecoordinators = true;
            } else {
                // Find the roles that can see the coordinators list.
                $roles = get_roles_with_capability('block/mbscoordinators:viewcoordinators');
                if ($roles) {
                    // See if the user has one of those roles in a child of the current context.
                    list($rsql, $params) = $DB->get_in_or_equal(array_keys($roles), SQL_PARAMS_NAMED);
                    $likesql = $DB->sql_like('cx.path', ':likecontextpath');
                    $params['userid'] = $USER->id;
                    $params['likecontextpath'] = "{$this->page->context->path}/%";
                    $sql = "SELECT ra.id
                              FROM {role_assignments} ra
                              JOIN {context} cx ON cx.id = ra.contextid
                             WHERE ra.roleid $rsql
                               AND $likesql
                               AND ra.userid = :userid";
                    $this->seecoordinators = $DB->record_exists_sql($sql, $params);
                }
            }
        }

        return $this->seecoordinators;
    }

    /**
     * Return a list of all the users who are 'coordinators' for this school (i. e. 
     * in a category or in the subtree of category of one school).
     *
     * @return object[]
     */
    protected function get_coordinators() {
        global $PAGE;

        // ...get the school category from current category.
        $categoryid = $PAGE->context->instanceid;

        if (!$schoolcat = \local_mbs\local\schoolcategory::get_schoolcategory($categoryid)) {
            return array();
        }

        // ...get all users, which may manage the school category.
        $fields = 'u.id, ' . get_all_user_name_fields(true, 'u');
        $schoolcatcontext = context_coursecat::instance($schoolcat->id, MUST_EXIST);

        return get_users_by_capability($schoolcatcontext, 'moodle/category:manage', $fields, 'lastname ASC, firstname ASC');
    }

    public function hide_header() {
        return true;
    }

    public function applicable_formats() {

        return array('all' => true, 'my' => false, '*category' => false);
    }
}