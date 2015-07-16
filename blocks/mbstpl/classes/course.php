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
 * Class course
 * For course-related operations.
 * @package block_mbstpl
 */
class course {

    const TPLPREFIX = 'Musterkurs';
    const BACKUP_LOCALPATH = 'mbstemplatebkp';
    const BACKUP_PREFIX = 'tplbkp_';

    /**
     * Extends the navigation, depending on capability.
     * @param \navigation_node $coursenode
     * @param \context $coursecontext
     */
    public static function extend_coursenav(\navigation_node &$coursenode, \context $coursecontext) {
        $tplnode = $coursenode->create(get_string('pluginname', 'block_mbstpl'), null, \navigation_node::COURSE_CURRENT);
        $cid = $coursecontext->instanceid;

        if (has_capability('block/mbstpl:sendcoursetemplate', $coursecontext)) {
            $url = new \moodle_url('/blocks/mbstpl/sendtemplate.php', array('course' => $cid));
            $tplnode->add(get_string('sendcoursetemplate', 'block_mbstpl'), $url);
        }

        if (self::can_assignreview($coursecontext)) {
            $url = new \moodle_url('/blocks/mbstpl/assignreviewer.php', array('course' => $cid));
            $tplnode->add(get_string('assignreviewer', 'block_mbstpl'), $url);
        }

        if (self::can_viewfeedback($coursecontext)) {
            $url = new \moodle_url('/blocks/mbstpl/viewfeedback.php', array('course' => $cid));
            $tplnode->add(get_string('templatefeedback', 'block_mbstpl'), $url);
        }

        if ($tplnode->has_children()) {
            $coursenode->add_node($tplnode);
        }
    }


    /**
     * Returns the shortname of the status.
     * @param $status
     */
    public static function get_statusshortname($status) {
        $statuses = array(
            dataobj\template::STATUS_CREATED => 'statuscreated',
            dataobj\template::STATUS_UNDER_REVIEW => 'statusunderreview',
            dataobj\template::STATUS_UNDER_REVISION => 'statusunderrevision',
            dataobj\template::STATUS_PUBLISHED => 'statuspublished',
            dataobj\template::STATUS_ARCHIVED => 'statusarchived',
        );
        return $statuses[$status];
    }

    /**
     * Clean up after a course has been deleted.
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;

        $data = $event->get_data();
        $cid = $data['courseid'];
        $DB->delete_records('block_mbstpl_template', array('courseid' => $cid));
    }

    /**
     * Tells us whether the current user can view the feedback page.
     * @param \context_course $coursecontext
     * @return bool
     */
    public static function can_viewfeedback(\context_course $coursecontext) {
        global $USER;

        $dobj = new dataobj\template(array('courseid' => $coursecontext->instanceid));
        if (!$dobj->id) {
            return false;
        }

        $allwed = array(
            dataobj\template::STATUS_PUBLISHED,
            dataobj\template::STATUS_UNDER_REVIEW,
            dataobj\template::STATUS_UNDER_REVISION,
        );
        if (!in_array($dobj->status, $allwed)) {
            return false;
        }

        if (has_capability('block/mbstpl:coursetemplatereview', $coursecontext)) {
            return true;
        }

        return $dobj->reviewerid == $USER->id;
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

    /**
     * Assign reviewer to a course. Assumes can_assignreview() has already been called.
     * @param $courseid
     * @param $userid
     */
    public static function assign_reviewer($courseid, $userid) {
        // Mark reviewer in the template record.
        $dobj = new dataobj\template(array('courseid' => $courseid));
        if (empty($dobj->id)) {
            throw new \moodle_exception('errorcoursenottemplate', 'block_mbstpl');
        }
        $dobj->reviewerid = $userid;
        $dobj->status = dataobj\template::STATUS_UNDER_REVIEW;
        $dobj->update();

        // Enrol reviewer.
        user::enrol_reviewer($courseid, $userid);
    }



    private static function get_template_filename($backup) {
        return self::BACKUP_PREFIX . $backup->id . '.mbz';
    }

    /**
     * Create a backup for a template.
     * @param object $backup
     * @return bool success
     */

    public static function backup_template($backup) {
        $filename = self::get_template_filename($backup);
        $user = get_admin();
        if (!$filename = self::automated_backup($backup->origcourseid, $filename, $backup->incluserdata, $user->id)) {
            throw new \moodle_exception('errorbackinguptemplate', 'block_mbstpl');
        }
        return true;
    }

    /**
     * Deploy a backed up template.
     * @param object $backup
     * @return int course id.
     */
    public static function restore_template($backup) {
        global $DB;

        $versionid = empty($backup->lastversion) ? 0 : $backup->lastversion;
        $versionid++;
        $backup->lastversion = $versionid;
        $courseid = self::launch_restore($backup);

        $updateobj = (object)array('id' => $backup->id, 'lastversion' => $versionid);
        $DB->update_record('block_mbstpl_backup', $updateobj);

        // Save template record.
        $template = array(
            'courseid' => $courseid,
            'backupid' => $backup->id,
            'authorid' => $backup->creatorid,
        );
        $dobj = new dataobj\template($template);
        $dobj->insert();
        return $courseid;
    }

    /**
     * Generate a shortname for the restored course. Make sure it's unique.
     * @param $origshortname
     * @param $versionid
     */
    private static function generate_course_shortname($origshortname, $versionid) {
        global $DB;

        $shortname = $origshortname.'_musterkurs_'.$versionid;
        if (!$DB->record_exists('course', array('shortname' => $shortname))) {
            return $shortname;
        }

        $like = $DB->sql_like('shortname', '?');
        $existings = $DB->get_records_select_menu('course', $like, array($shortname . '%'), null, 'id,shortname');
        $success = false;
        $subrelease = 0;
        while (!$success) {
            $subrelease++;
            $newshortname = $shortname . 'd' . $subrelease;
            if (!in_array($newshortname, $existings)) {
                $success = true;
                $shortname = $newshortname;
            }
        }
        return $shortname;
    }

    /**
     * Similar to launch_automated_backup(), but with our own settings
     *
     * @param int $courseid
     * @param bool $filename
     * @param bool $withusers
     * @param int $userid
     * @return mixed filename|false on error
     */
    private static function automated_backup($courseid, $filename, $withusers, $userid) {
        global $CFG;

        require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot.'/backup/util/helper/backup_cron_helper.class.php');

        $dir = $CFG->dataroot . '/' . self::BACKUP_LOCALPATH;
        $settings = array(
            'users' => 0,
            'anonymize' => 0,
            'role_assignments' => 0,
            'user_files' => 0,
            'activities' => 1,
            'blocks' => 1,
            'filters' => 1,
            'comments' => 0,
            'completion_information' => 0,
            'logs' => 0,
            'histories' => 0,
        );
        if ($withusers) {
            $settings['users'] = 1;
            $settings['anonymize'] = 1;
        }

        $bc = new \backup_controller(\backup::TYPE_1COURSE, $courseid, \backup::FORMAT_MOODLE, \backup::INTERACTIVE_NO, \backup::MODE_AUTOMATED, $userid);
        $backupok = true;
        try {
            foreach ($settings as $setting => $value) {
                if ($bc->get_plan()->setting_exists($setting)) {
                    $bc->get_plan()->get_setting($setting)->set_value($value);
                }
            }

            // Set the default filename
            $format = $bc->get_format();
            $type = $bc->get_type();
            $id = $bc->get_id();
            $users = $bc->get_plan()->get_setting('users')->get_value();
            $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
            $bc->get_plan()->get_setting('filename')->set_value(\backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised));

            $bc->set_status(\backup::STATUS_AWAITING);

            $bc->execute_plan();
            $results = $bc->get_results();
            $file = $results['backup_destination'];
            if (!check_dir_exists($dir)) {
                throw new \moodle_exception('errorbackupdir', 'block_mbstpl');
            }
            $filepath = $dir.'/'.$filename;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            $outcome = $file->copy_content_to($dir.'/'.$filename);
            if ($outcome) {
                $file->delete();
            }
        } catch (Exception $e) {
            $backupok = false;
        }

        $bc->destroy();
        unset($bc);

        if($backupok) {
            return $filename;
        }
        return false;
    }

    /**
     * Restore a backed up template.
     * @param object $backup
     * @return int courseid
     */
    private static function launch_restore($backup) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $catid = get_config('block_mbstpl', 'deploycat');
        if (!$catid || !$DB->record_exists('course_categories', array('id' => $catid))) {
            throw new \moodle_exception('errorcatnotexists', 'block_mbstpl');
        }

        $filename = self::get_template_filename($backup);
        $dir = $CFG->dataroot . '/' . self::BACKUP_LOCALPATH;
        $filepath = $dir . '/' . $filename;
        if (!is_readable($filepath)) {
            throw new \backup_helper_exception('missing_moodle_backup_file', $filepath);
        }

        // Extraction mostly copied from \backup_general_helper::get_backup_information_from_mbz().
        $tmpname = 'mbstemplatting_' . $backup->id . '_' . $backup->lastversion . '_' . time();
        $tmpdir = $CFG->tempdir . '/backup/' . $tmpname;
        $fp = get_file_packer('application/vnd.moodle.backup');
        $extracted = $fp->extract_to_pathname($filepath, $tmpdir);
        $moodlefile =  $tmpdir . '/' . 'moodle_backup.xml';
        if (!$extracted || !is_readable($moodlefile)) {
            throw new \backup_helper_exception('missing_moodle_backup_xml_file', $moodlefile);
        }

        // Load format.
        $info = \backup_general_helper::get_backup_information($tmpname);
        $format = $info->format;
        $plugins = get_sorted_course_formats();
        if (!in_array($format, $plugins)) {
            if ($origformat = $DB->get_field('course', 'format', array('id' => $backup->origcourseid))) {
                $format = $origformat;
            } else {
                $format = reset($plugins);
            }
        }

        // Create course.
        $cdata = (object)array(
            'category' => $catid,
            'shortname' => self::generate_course_shortname($info->original_course_shortname, $backup->lastversion),
            'fullname' => $info->original_course_fullname,
            'format' => $format,
            'numsections' => empty($info->sections) ? 0 : count($info->sections),
        );
        $course = create_course($cdata);

        // Restore.
        $admin = get_admin();
        try {
            $rc = new \restore_controller($tmpname, $course->id, false, \backup::MODE_SAMESITE, $admin->id, \backup::TARGET_CURRENT_ADDING);
            $rc->execute_precheck();
            $rc->execute_plan();
        } catch (Exception $e) {
            throw new \moodle_exception('errorrestoringtemplate', 'block_mbstpl');
        }
        remove_dir($tmpdir);
        return $course->id;
    }
}