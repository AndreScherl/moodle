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

/**
 * Class perms
 * For permission issues.
 * @package block_mbstpl
 */
class perms {
    /**
     * Tells us whether the current user can view the feedback page.
     * @param \context_course $coursecontext
     * @param dataobj\template $template will be fetched from db if not provided.
     * @return bool
     */
    public static function can_viewfeedback(\context_course $coursecontext, dataobj\template $template = null) {
        global $USER;

        if (is_null($template)) {
            $template = new dataobj\template(array('courseid' => $coursecontext->instanceid));
            if (!$template->id) {
                return false;
            }
        }

        if (has_capability('block/mbstpl:coursetemplatemanager', $coursecontext)) {
            return true;
        }

        if ($template->authorid == $USER->id) {
            return $template->status == $template::STATUS_UNDER_REVISION;
        }

        if ($template->reviewerid == $USER->id) {
            $allowed = array(
                $template::STATUS_PUBLISHED,
                $template::STATUS_UNDER_REVIEW,
                $template::STATUS_ARCHIVED,
            );
            return in_array($template->status, $allowed);
        }

        return false;
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
     * Tells us whether the current user can publish edit the meta settings.
     * @param dataobj\template $template will be fetched from db if not provided.
     * @return bool
     */
    public static function can_editmeta(\context_course $coursecontext) {
        return has_capability('block/mbstpl:coursetemplatereview', $coursecontext);
    }

    /**
     * Tells us whether the course can be assigned a reviewer
     * @param context_course $coursecontext
     * @return bool
     */
    public static function can_assignreview(\context_course $coursecontext) {
        global $DB;
        if (!has_capability('block/mbstpl:sendcoursetemplate', $coursecontext)) {
            return false;
        }

        $cid = $coursecontext->instanceid;
        return $DB->record_exists('block_mbstpl_template', array('courseid' => $cid, 'reviewerid' => 0));
    }
}