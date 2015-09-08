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
 * Script to add or delete a specific block.
 *
 * @package   local_mbs
 * @copyright 2015 ISB Bayern
 * @author    Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/my/lib.php');

// Get CLI options.
list($options, $unrecognized) = cli_get_params(
        array(
            'action' => false,
            'blockname' => '',
            'role' => '',
            'help' => false
        ), 
        array(
            'h' => 'help'
        )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || !$options['blockname']) {
    $help = "This script can
    * delete all instances of a specific block or
    * add a specific block to 'my-index'-page. If a role is given only for users with this role.
        
    Options:
    --action=INTEGER        
        * 0 for delete, 
        * 1 for add,
        * 2 for delete and add. 
        * If not set the block will be deleted.    
    --blockname=STRING      
        * required 
        * Name of the block, which should be deleted or added.     
    --role=STRING           
        * Role of the users where to add the block. 
        * If not set the block will be added for everybody over the whole platform.
    -h, --help              Print out this help.

    Example:
    \$sudo -u www-data /usr/bin/php local/mbs/cli/add_delete_block.php --action=0 --blockname=mbschangeplatform
    Microsoft Windows: 'path/to/php.exe' moodledir/local/mbs/cli/add_delete_block.php --blockname=mbschangeplatform --role=betatester 
    ";

    echo $help;
    die;
}

$numberofactions = 0;

// Get options.
$blockname = $options['blockname']; 
if (!empty($options['action'])) {
    $action = $options['action'];
} else {
    $action = 0;
}
if (!empty($options['role'])) {
    $role = $options['role'];
} else {
    $role = 0;
}

switch ($action) {
    case 0: 
        $numberofactions = local_mbs_cli_delete($blockname); 
        break;
    case 1: 
        $numberofactions = local_mbs_cli_add($blockname, $role); 
        break;
    case 2: 
        $numberofactions = local_mbs_cli_delete($blockname);
        $numberofactions = $numberofactions + local_mbs_cli_add($blockname, $role); 
        break;
    default: 
        cli_error(get_string('cliunknowoption', 'admin', 'action='.$action)); 
        break;    
}

echo "Block reorganisation finished with $numberofactions actions done.";

/*
 * Remove all instances of the given block
 */
function local_mbs_cli_delete($blockname) {
    global $DB;
    $numberofactions = 0;
    echo "Delete block $blockname for all users. \n";
    
    if ($blocks = $DB->get_records_select('block_instances', "blockname = '" . $blockname . "'")) {
        foreach ($blocks as $block) {
            blocks_delete_instance($block);
            echo "Deleted instance {$block->id} of block {$block->blockname}. \n";
            $numberofactions++;
        }
    }
    
    return $numberofactions;
}

/*
 * Add the given block to 'my-index'-page. If a role is given only for users with this role.
 */
function local_mbs_cli_add($blockname, $role) {
    global $DB;
    $numberofactions = 0;
    
    if ($role) { // is a specific role given?
        echo "Add block $blockname for all users with the role $role. \n";
        //Get all users with the specific role 
        $sql = "SELECT DISTINCT u.id, r.shortname  
                        FROM mdl_user u
                        JOIN mdl_role_assignments ra ON ra.userid = u.id
                        JOIN mdl_role r ON r.id = ra.roleid AND r.shortname = '" . $role . "'";
        $users = $DB->get_records_sql($sql);
    } 
    else {
        echo "Add block $blockname for everyone. \n";
        $sql = "SELECT DISTINCT u.id FROM mdl_user u";
        $users = $DB->get_records_sql($sql);
    }

    if ($users) {
        foreach ($users as $user) {
            
            //get my-page
            $page = my_get_page($user->id);
            if (empty($page->userid)) { 
                //if returned page is the system default page we have to copy the system default page to the current user                    
                $page = my_copy_page($user->id);
            }                

            $pagetype = 'my-index';
            $usercontext = context_user::instance($user->id);

            $blockinstance = block_instance($blockname);
            $blockinstance->blockname = $blockname;
            $blockinstance->parentcontextid = $usercontext->id;
            $blockinstance->showinsubcontexts = 0;
            $blockinstance->pagetypepattern = $pagetype;
            $blockinstance->subpagepattern = $page->id;
            $blockinstance->defaultregion = 'side-pre';
            $blockinstance->defaultweight = 10;
            $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);
            $blockcontext = context_block::instance($blockinstance->id);  // Just creates the context record
            echo "Added block {$blockname} to my-index-page of user {$user->id}. \n";
            $numberofactions++;
        }
    }
    else {
        cli_error('DB error: No users were found.');
    }
    
    return $numberofactions;
}
