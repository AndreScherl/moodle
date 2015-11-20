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
 * @package block_mbstpl
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl;

defined('MOODLE_INTERNAL') || die();

/**
 * Class perms
 * For permission issues.
 * @package block_mbstpl
 */
class perms {
    /**
     * Tells us whether the current user can view the feedback page.
     * @param dataobj\template
     * @param \context_course $coursecontext
     * @return bool
     */
    public static function can_viewfeedback(dataobj\template $template = null, \context_course $coursecontext) {
        global $USER;

        if (has_capability('block/mbstpl:coursetemplatemanager', $coursecontext)) {
            return true;
        }

        return $template->authorid == $USER->id || $template->reviewerid == $USER->id;
    }

    /**
     * Tells us whether the current user can send the template to archive.
     * @param dataobj\template
     * @return bool
     */
    public static function can_archive(dataobj\template $template) {
        global $USER;

        if ($template->status == $template::STATUS_ARCHIVED) {
            return false;
        }

        if ($template->reviewerid == $USER->id) {
            return true;
        }

        $coursecontext = \context_course::instance($template->courseid);
        return has_capability('block/mbstpl:coursetemplatemanager', $coursecontext);
    }

    /**
     * Tells us whether the current user can publish the template.
     * @param dataobj\template
     * @return bool
     */
    public static function can_publish(dataobj\template $template) {
        global $USER;

        if ($template->status == $template::STATUS_PUBLISHED) {
            return false;
        }

        if ($template->reviewerid == $USER->id) {
            return true;
        }

        $coursecontext = \context_course::instance($template->courseid);
        return has_capability('block/mbstpl:coursetemplatemanager', $coursecontext);
    }

    /**
     * Tells us whether the current user can send the template for revision.
     * @param dataobj\template $template
     * @param \context_course $coursecontext
     * @return bool
     */
    public static function can_sendrevision(dataobj\template $template, \context_course $coursecontext) {
        return has_capability('block/mbstpl:coursetemplatemanager', $coursecontext);
    }

    /**
     * Tells us whether the current user can publish edit the meta settings.
     * He needs to be the last assignee and to have the capability coursetemplateeditmeta.
     * @param dataobj\template $template
     * @param \context_course $coursecontext
     * @return bool
     */
    public static function can_editmeta(dataobj\template $template, \context_course $coursecontext) {
        global $USER;
        $assigned = (\block_mbstpl\course::get_lastassignee($template->id)->id == $USER->id);
        return (has_capability('block/mbstpl:coursetemplateeditmeta', $coursecontext) && $assigned);
    }

    /**
     * Tells us whether the course can be assigned an author
     * @param dataobj\template $template
     * @param \context_course $coursecontext
     * @return bool
     */
    public static function can_assignauthor(dataobj\template $template, \context_course $coursecontext) {
        if ($template->status != $template::STATUS_UNDER_REVIEW) {
            return false; // Not currently with the reviewer, so cannot be passed on to the author.
        }
        return has_capability('block/mbstpl:assignauthor', $coursecontext);
    }

    /**
     * Tells us whether the course can be assigned a reviewer
     * @param dataobj\template $template
     * @param \context_course $coursecontext
     * @return bool
     */
    public static function can_assignreview(dataobj\template $template, \context_course $coursecontext) {
        if ($template->reviewerid) {
            return false;
        }

        return has_capability('block/mbstpl:coursetemplatemanager', $coursecontext);
    }

    /**
     * The user can send the template back to the reviewer (but not choose who the reviewer is).
     *
     * @param dataobj\template $template
     * @param \context_course $coursecontext
     * @return bool
     */
    public static function can_returnreview(dataobj\template $template, \context_course $coursecontext) {
        global $USER;

        if (!$template->reviewerid) {
            return false; // Must have a reviewer allocted, in order to send the course to them.
        }
        if ($template->status != $template::STATUS_UNDER_REVISION) {
            return false; // Can only return to the reviewer if it is currently being revised by the author.
        }

        if ($template->authorid == $USER->id) {
            return true; // Assigned author can return the course to the reviewer.
        }
        if (has_capability('block/mbstpl:coursetemplatemanager', $coursecontext)) {
            return true; // Course template manager can also return it to the reviewer.
        }

        return false;
    }

    /**
     * Tells us whether the template can be duplicated to a course by the current user.
     * @param dataobj\template $template
     * @param \context_course $coursecontext
     * @return bool
     */
    public static function can_coursefromtpl(dataobj\template $template, \context_course $coursecontext) {
        if ($template->status != $template::STATUS_PUBLISHED) {
            return false;
        }

        return has_capability('block/mbstpl:createcoursefromtemplate', $coursecontext);
    }

    /**
     * Tells us whether the current user can rate the template
     * @param \context_course $coursecontext
     * @return bool
     */
    public static function can_leaverating(\context_course $coursecontext) {
        if (!has_capability('block/mbstpl:ratetemplate', $coursecontext)) {
            return false;
        }

        return dataobj\template::get_from_course($coursecontext->instanceid) != null;
    }

    /**
     * Tells us whether the current user can rate the template
     * @param \context_course $coursecontext
     */
    public static function can_viewrating(\context_course $coursecontext) {
        if (!has_capability('block/mbstpl:viewrating', $coursecontext)) {
            return false;
        }

        return \block_mbstpl\dataobj\template::get_from_course($coursecontext->instanceid) != null;
    }

    /**
     * Tells us whether the current user can view this template's history
     * @param \context_course $coursecontext
     */
    public static function can_viewhistory(\context_course $coursecontext) {
        if (!has_capability('block/mbstpl:viewhistory', $coursecontext)) {
            return false;
        }

        if (!\block_mbstpl\dataobj\template::fetch(array('courseid' => $coursecontext->instanceid))) {
            return false;
        }
        return true;
    }

    /**
     * Does the user have the capability to view template backups on the system level.
     * @return bool
     */
    public static function can_viewbackups() {
        return has_capability('block/mbstpl:viewcoursetemplatebackups', \context_system::instance());
    }


    /**
     * Does the user have the capability to search for templates.
     * @return bool
     */
    public static function can_searchtemplates() {
        return has_capability('block/mbstpl:createcoursefromtemplate', \context_system::instance());
    }

}
