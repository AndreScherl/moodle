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
 * @package block
 * @subpackage mbstemplating
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstemplating;

defined('MOODLE_INTERNAL') || die();

/**
 * Class course
 * For course-related operations.
 * @package block_mbstemplating
 */
class course {

    const TPLPREFIX = 'Musterkurs';

    /**
     * Extends the navigation, depending on capability.
     * @param \navigation_node $coursenode
     * @param \context $coursecontext
     */
    public static function extend_coursenav(\navigation_node &$coursenode, \context $coursecontext) {
        $tplnode = $coursenode->create(get_string('pluginname', 'block_mbstemplating'), null, \navigation_node::COURSE_CURRENT);

        if (has_capability('block/mbstemplating:sendcoursetemplate', $coursecontext)) {
            $url = new \moodle_url('/blocks/mbstemplating/sendtemplate.php', array('course' => $coursecontext->instanceid));
            $tplnode->add(get_string('sendcoursetemplate', 'block_mbstemplating'), $url);
        }

        if ($tplnode->has_children()) {
            $coursenode->add_node($tplnode);
        }
    }
}