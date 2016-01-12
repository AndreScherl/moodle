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
 * @package   block_mbsnews
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbsnews\local;

class newshelper {

    /**
     * Search for recipients in two steps (assuming there are many results)
     * 1. Count results
     * 2. If there are lower than 10 retrieve the details of the users.
     * 
     * @param array $searchparams
     * @return array result array contains error flag and result as a string. 
     */
    public static function search_recipients($searchparams) {
        global $DB;

        $config = get_config('block_mbsnews');

        $selectcount = " SELECT  count(DISTINCT u.id) ";
        $select = " SELECT DISTINCT u.* ";
        $join = " FROM {user} u ";

        $cond = array();
        $params = array();

        // Exclude deleted users.
        $cond[] = " u.deleted = 0";

        // Include auth users.
        if (!empty($config->includeauth)) {

            $auths = explode(',', $config->includeauth);

            $authcond = array();
            foreach ($auths as $auth) {
                $authcond[] = " u.auth = '{$auth}' ";
            }
            $cond[] = implode(" OR ", $authcond);
        }

        // Check roleid.
        if (!empty($searchparams['contextlevel'])) {
            
            $join .= " JOIN {role_assignments} ra ON ra.userid = u.id ";
            $join .= " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :contextlevel ";
            $params['contextlevel'] = $searchparams['contextlevel'];
            
            if ($searchparams['contextlevel'] == CONTEXT_COURSECAT) {
                $cond[] = ' ctx.depth = :contextdepth ';
                
                // Note that context depth is cat depth + 1!
                $params['contextdepth'] = \local_mbs\local\schoolcategory::$schoolcatdepth + 1;
            }
            
        }
        
        // Check roleid.
        if (!empty($searchparams['roleid'])) {
            $cond[] = " ra.roleid = :roleid "; 
            $params['roleid'] = $searchparams['roleid'];
        }

        // Check instanceids.
        if (!empty($searchparams['instanceidsselected'])) {

            $instancesids = explode('_', $searchparams['instanceidsselected']);
            
            $instancecond = array();

            foreach ($instancesids as $instanceid) {
                $instancecond[] = " ctx.instanceid = '{$instanceid}' ";
            }
            
            $cond[] = implode(' OR ', $instancecond);
        }

        $where = "WHERE (".implode(') AND (', $cond).")";
        
        // Count records.
        if (!$count = $DB->count_records_sql($selectcount.$join.$where, $params)) {
            return array('error' => 0, 'results' => get_string('recipientsselected', 'block_mbsnews', $count));
        }
        
        if ($count > 5) {
            return array('error' => 0, 'results' => get_string('recipientsselected', 'block_mbsnews', $count));
        }

        // Get records.
        $users = $DB->get_records_sql($select.$join.$where, $params);

        $usernames = array();
        foreach ($users as $user) {
            $url = new \moodle_url('/user/profile.php', array('id' => $user->id));
            $usernames[] =  \html_writer::link($url, fullname($user), array('target' => '_blank'));
        }
        
        $results = implode(", ", $usernames);

        return array('error' => 0, 'results' => $results);
    }

}
