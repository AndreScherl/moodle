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
 * Selector for potential reviewers
 *
 * @package   block_mbstl
 * @copyright 2015 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\selector;

use coding_exception;
use user_selector_base;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/user/selector/lib.php');

class user_potential extends user_selector_base {
    /** @var int[] */
    protected $allowedreviewerids = array();
    /** @var int */
    protected $default = null;

    /**
     * Required options:
     *   * allowedreviewerids int[]|null - restrict to given userids OR allow any userid
     *
     * @param null $name
     * @param array $options
     * @throws coding_exception
     */
    public function __construct($name = null, $options = array()) {
        if (!$name) {
            $name = 'selectusers';
        }
        if (!array_key_exists('allowedreviewerids', $options)) {
            throw new \coding_exception('Must set the \'allowedreviewerids\' option');
        }
        if (!is_array($options['allowedreviewerids']) && $options['allowedreviewerids'] !== null) {
            throw new \coding_exception('Option \'allowedreviewerids\' must be an array OR null');
        }
        $options['multiselect'] = false;
        $this->allowedreviewerids = $options['allowedreviewerids'];
        parent::__construct($name, $options);

        $this->rows = 10;
        $this->autoselectunique = true;
        $this->preserveselected = true;
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['allowedreviewerids'] = $this->allowedreviewerids;
        return $options;
    }

    public function find_users($search) {
        global $DB;

        list($wherecondition, $params) = $this->search_sql($search, 'u');

        $fields = 'SELECT DISTINCT '.$this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(DISTINCT u.id)';

        if ($this->allowedreviewerids !== null) {
            // Only include users with the capability 'block/mbstpl:coursetemplatereview'.
            $userids = $this->allowedreviewerids;
            if (!$userids) {
                // No users to select from.
                return array();
            }

            list($usql, $uparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            if ($wherecondition) {
                $wherecondition .= ' AND ';
            }
            $wherecondition .= "u.id $usql";
            $params = array_merge($params, $uparams);
        }

        $sql = " FROM {user} u
                WHERE $wherecondition ";

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY '.$sort;

        // Check to see if there are too many to show sensibly.
        if (!$this->is_validating()) {
            $potentialcount = $DB->count_records_sql($countfields.$sql, $params);
            if ($potentialcount > $this->maxusersperpage) {
                $ret = $this->too_many_results($search, $potentialcount);
                if ($sel = $this->get_selected_user()) {
                    return array_merge(array(get_string('selecteduser', 'block_mbstpl') => array($sel->id => $sel)), $ret);
                }
            }
        }

        $availableusers = $DB->get_records_sql($fields.$sql.$order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('potusersmatching', 'core_role', $search);
        } else {
            $groupname = get_string('potusers', 'core_role');
        }

        return array($groupname => $availableusers);
    }

    /**
     * The userid to select, if none are selected via the
     *
     * @param int $userid
     */
    public function set_default($userid) {
        $this->default = $userid;
    }

    /**
     * Get the list of users that were selected by doing optional_param then validating the result.
     *
     * @return array of user objects.
     */
    protected function load_selected_users() {
        // Nasty hack to push the default into the url params.
        $reset = false;
        if ($this->default && !optional_param($this->name, null, PARAM_INT)) {
            if (!empty($_POST)) {
                $_POST[$this->name] = $this->default;
            } else {
                $_GET[$this->name] = $this->default;
            }
            $reset = true;
        }

        $ret = parent::load_selected_users();

        if ($reset) {
            // If we've added to the url params, remove the values again.
            unset($_POST[$this->name]);
            unset($_GET[$this->name]);
        }

        return $ret;
    }
}
