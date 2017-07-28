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
 * Script to configure the block settings of all users to meet the requirements of the mebis redesign theme
 *
 * @package   local_mbs
 * @copyright 2015 ISB Bayern
 * @author    Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__).'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot . '/my/lib.php');

// Get CLI options.
list($options, $unrecognized) = cli_get_params (
    array(
        'resetmypages' => false,
        'help' => false
    ),
    array(
        'r' => 'resetmypages',
        'h' => 'help'
    )
);

if ($options['help']) {
    $help = 
    "
This script performs the following block cleanup to meet the requirements
of the mebis redesign 2015.

* removes old dlb blocks like 'dlb', 'meinekurse', 'meineschulen', 'meinesuche'
* removes the instances of the new blocks, which are rendered as raw blocks
* moves all block instances of all pagetypepatterns into the region side-pre,
  except mbsmycourses and mbsmyschools and blocks placed in side-post
* move mbsmycourses into content region of pagetypepattern 'my-index',
  remove the block in all other pagetypepatterns
* move mbsmyschools into region bottom of pagetypepattern 'my-index',
  remove the block in all other pagetypepatterns
* optional: reset all my-pages to system default

Options:
 -r, --resetmypages   Reset all my-pages to default.
 -h, --help           Print out this help.

Example:
 \$sudo -u www-data /usr/bin/php local/mbs/cli/global_blocks_cleanup.php
 \$sudo -u www-data /usr/bin/php local/mbs/cli/global_blocks_cleanup.php -r
";

    echo $help;
    die;
}

$numberofactions = 0;

// Remove the instances of the old dlb blocks 'dlb', 'meinekurse', 'meineschulen', 'meinesuche'.
if ($blocks = $DB->get_records_select('block_instances',
        "blockname = 'dlb' OR blockname = 'meinekurse' OR blockname = 'meineschulen' OR blockname = 'meinesuche'")) {
    foreach ($blocks as $block) {
        blocks_delete_instance($block);
        echo "Deleted instance {$block->id} of block {$block->blockname}. \n";
        $numberofactions++;
    }
}

// Remove the instances of the new blocks, which are rendered as raw blocks.
if ($blocks = $DB->get_records_select('block_instances',
        "blockname = 'mbsschooltitle' OR blockname = 'mbssearch' OR blockname = 'mbsgettingstarted'"
        . " OR blockname = 'mbsnewcourse' OR blockname = 'mbscoordinators'")) {
    foreach ($blocks as $block) {
        blocks_delete_instance($block);
        echo "Deleted instance {$block->id} of block {$block->blockname}. \n";
        $numberofactions++;
    }
}

// Move all block instances of all pagetypepatterns into the region side-pre, except mbsmycourses and mbsmyschools
// and Blocks placed in side-post.
if ($blocks = $DB->get_records_select('block_instances', "blockname <> 'mbsmycourses' AND blockname <> 'mbsmyschools'"
        . " AND defaultregion <> 'side-pre' AND defaultregion <> 'side-post'")) {
    foreach ($blocks as $block) {
        $DB->set_field('block_instances', 'defaultregion', 'side-pre', array('id' => $block->id));
        echo "Moved instance {$block->id} of block {$block->blockname} into region side-pre. \n";
        $numberofactions++;
    }
}

// Move mbsmycourses into content region of pagetypepattern 'my-index', remove the block in all other pagetypepatterns.
if ($blocks = $DB->get_records('block_instances', array('blockname' => 'mbsmycourses'))) {
    foreach ($blocks as $block) {
        // Delete instance if region already contains this block or is not in the right pagetypepattern, else move.
        if ($block->pagetypepattern !== 'my-index' || ($b = $DB->get_record_select('block_instances',
                "blockname = 'mbsmycourses' AND parentcontextid = {$block->parentcontextid} AND defaultregion = 'content'"
                . "AND id <> {$block->id}"))) {
            blocks_delete_instance($block);
            echo "Deleted instance {$block->id} of block {$block->blockname}. \n";
            $numberofactions++;
        } else {
            if ($block->defaultregion !== 'content') {
                $DB->set_field('block_instances', 'defaultregion', 'content', array('id' => $block->id));
                echo "Moved instance {$block->id} of block {$block->blockname} into region content. \n";
                $numberofactions++;
            }
        }
    }
}

// Move mbsmyschools into region bottom of pagetypepattern 'my-index', remove the block in all other pagetypepatterns.
if ($blocks = $DB->get_records('block_instances', array('blockname' => 'mbsmyschools'))) {
    foreach ($blocks as $block) {
        // Delete instance if region already contains this block or is not in the right pagetypepattern, else move.
        if ($block->pagetypepattern !== 'my-index' || ($b = $DB->get_record_select('block_instances',
                "blockname = 'mbsmyschools' AND parentcontextid = {$block->parentcontextid} AND defaultregion = 'bottom' "
                . "AND id <> {$block->id}"))) {
            blocks_delete_instance($block);
            echo "Deleted instance {$block->id} of block {$block->blockname}. \n";
            $numberofactions++;
        } else {
            if ($block->defaultregion !== 'bottom') {
                $DB->set_field('block_instances', 'defaultregion', 'bottom', array('id' => $block->id));
                echo "Moved instance {$block->id} of block {$block->blockname} into region bottom. \n";
                $numberofactions++;
            }
        }
    }
}

// Reset all my-pages to system default, exept those with userid = null
if ($options['resetmypages'] && $mypages = $DB->get_records_select('my_pages', "userid >= 1")) {
    foreach ($mypages as $mypage) {
        my_reset_page($mypage->userid);
        echo "Reset my page of userid {$mypage->userid} to default. \n";
        $numberofactions++;
    }
}

echo "Block cleanup finished with {$numberofactions} actions done.";
