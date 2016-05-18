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

use backup_anonymizer_helper;

/**
 * Class course
 * For course backup and restore operations.
 * @package block_mbstpl
 */
class backup {

    const PREFIX_PRIMARY = 'origbkp_';
    const PREFIX_SECONDARY = 'tplbkp_';

    /** @var dataobj\backup */
    private static $primaryrestoreof = null;
    /** @var int[] */
    private static $mappedexcludedeploydataids = null;

    /** @var \context_course nasty hack to get course context when deep inside the backup anonymisation code */
    private static $coursecontext = null;

    /**
     * Generates filename.
     * @param int $id of the backup or template.
     * @param bool|true $primary
     * @return string
     */
    private static function get_filename($id, $primary = true) {
        $prefix = $primary ? self::PREFIX_PRIMARY : self::PREFIX_SECONDARY;
        return $prefix . $id . '.mbz';
    }

    /**
     * Generate a shortname for the restored course. Make sure it's unique.
     * @param $origshortname
     * @param $versionid
     * @param bool $istemplate false means a duplicate.
     * @return string
     */
    public static function generate_course_shortname($origshortname, $versionid, $istemplate = true) {
        global $DB;

        $mid = $istemplate ? '_Austauschkurs_' : '_dpl_';
        $shortname = $origshortname . $mid . $versionid;
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
     * Create a backup for a template.
     * @param \block_mbstpl\dataobj\backup $backup
     * @return string filename or throws error on failure
     */
    public static function backup_primary(dataobj\backup $backup) {
        $filename = self::get_filename($backup->id);
        $user = get_admin();
        if (!$filename = self::launch_primary_backup($backup->origcourseid, $backup->id, $backup->incluserdata, $user->id)) {
            throw new \moodle_exception('errorbackinguptemplate', 'block_mbstpl');
        }
        return $filename;
    }

    /**
     * Deploy a backed up template.
     * @param \block_mbstpl\dataobj\backup $backup
     * @return int course id.
     */
    public static function restore_primary(dataobj\backup $backup) {
        $versionid = empty($backup->lastversion) ? 0 : $backup->lastversion;
        $versionid++;
        $backup->lastversion = $versionid;
        $courseid = self::launch_primary_restore($backup);
        
        // Delete all enrolments except manual
        course::delete_enrol_instances($courseid);
        // Unenrol everybody who was enroled manual, e. g. anonymous users
        $userenrolments = course::get_all_enrolled_users($courseid);
        if (!empty($userenrolments)) {
            course::unenrol($courseid, $userenrolments);
        }

        $backup->update();

        // Save template record.
        $templatedata = array(
            'courseid' => $courseid,
            'backupid' => $backup->id,
            'authorid' => $backup->creatorid,
        );
        $template = new dataobj\template($templatedata);
        $template->set_exclude_deploydata_ids(self::$mappedexcludedeploydataids);
        $template->insert();
        self::$mappedexcludedeploydataids = null;

        // Copy over metadata.
        $bkpmeta = new dataobj\meta(array('backupid' => $backup->id), true, MUST_EXIST);
        $tplmeta = new dataobj\meta(array('templateid' => $template->id), true, MUST_EXIST);
        $tplmeta->copy_from($bkpmeta);

        // Enrol creator as author.
        user::enrol_author($template->courseid, $backup->creatorid);

        return $courseid;
    }


    /**
     * Create a backup for a template.
     * @param \block_mbstpl\dataobj\template $backup
     * @param object $settings
     * @return string filename or throws error on failure
     */
    public static function backup_secondary(dataobj\template $template, $settings) {
        $backupsettings = isset($settings->backupsettings) ? (array) $settings->backupsettings : array();
        $user = get_admin();
        $filename = self::launch_secondary_backup($template->courseid, $template->id, $backupsettings, $user->id);
        return $filename;
    }




    /**
     * Deploy a backed up template.
     * @param \block_mbstpl\dataobj\template $template
     * @param string $filename
     * @param object $settings
     * @return \block_mbstpl\dataobj\coursefromtpl
     */
    public static function restore_secondary(dataobj\template $template, $filename, $settings, $requesterid) {
        $targetcat = 0;
        $targetcrs = 0;
        if (!empty($settings->tocat)) {
            $targetcat = $settings->tocat;
        } else {
            $targetcrs = $settings->tocrs;
        }
        $cid = self::launch_secondary_restore($template, $filename, $targetcat, $targetcrs);

        // Add coursefromtpl entry.
        $coursefromtpl = new dataobj\coursefromtpl(array(
            'courseid' => $cid,
            'templateid' => $template->id,
            'createdby' => $requesterid,
            'licence' => $settings->licence
        ));

        $coursefromtpl->insert();

        // Unenrol everybody        
        $userenrolments = course::get_all_enrolled_users($cid);
        if (!empty($userenrolments)) {
            course::unenrol($cid, $userenrolments);
        }

        return $coursefromtpl;
    }

    /**
     * Backup a template for revision.
     * @param dataobj\template $template
     * @return string filename or throws error on failure
     */
    public static function backup_revision(dataobj\template $template) {
        $user = get_admin();
        $filename = self::launch_secondary_backup($template->courseid, $template->id, array(), $user->id, true);
        return $filename;
    }

    /**
     * Restore a template for revision.
     * @param dataobj\template $template
     * @param string $filename
     * @param string $message
     * @return int course id.
     */
    public static function restore_revision(dataobj\template $template, $filename, $message) {
        $targetcat = get_config('block_mbstpl', 'deploycat');
        $targetcrs = 0;
        $cid = self::launch_secondary_restore($template, $filename, $targetcat, $targetcrs);

        // Add template entry.
        $newtpl = clone($template);
        $newtpl->id = null;
        $newtpl->courseid = $cid;
        $newtpl->status = dataobj\template::STATUS_UNDER_REVIEW;
        $newtpl->feedback = $message;
        $newtpl->feedbackformat = FORMAT_PLAIN;
        $newtpl->rating = null;
        $newtpl->reminded = 0;
        $newtpl->insert();

        // Copy over metadata.
        $origmeta = new dataobj\meta(array('templateid' => $template->id), true, MUST_EXIST);
        $tplmeta = new dataobj\meta(array('templateid' => $newtpl->id), true, MUST_EXIST);
        $tplmeta->copy_from($origmeta);
        
        // Unenrol everybody        
        $userenrolments = course::get_all_enrolled_users($cid);
        if (!empty($userenrolments)) {
            course::unenrol($cid, $userenrolments);
        }
        
        //Enrol reviewer
        user::enrol_reviewer($cid, $newtpl->reviewerid, true);
        
        return $cid;
    }

    /**
     * Should this particular user be anonymised?
     * @param \base_final_element $element
     * @return bool
     */
    public static function should_anonymise(\base_final_element $element) {
        if (!self::$coursecontext) {
            return true; // If coursecontext is not set, then this is not a partial-anonymisation backup.
        }

        $parent = $element->get_parent();
        if ($parent->get_name() != 'user') {
            return true; // The parent element is not a user, so carry on as usual.
        }

        $useridattr = $parent->get_attribute('id');
        $userid = $useridattr->get_value();
        return !has_capability('block/mbstpl:notanonymised', self::$coursecontext, $userid);
    }

    /**
     * Backup an original course.
     * Similar to launch_automated_backup(), but with our own settings.
     *
     * @param int $courseid
     * @param int $backupid
     * @param bool $withusers
     * @param int $userid
     * @return mixed filename|false on error
     */
    private static function launch_primary_backup($courseid, $backupid, $withusers, $userid) {
        global $CFG;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/helper/backup_cron_helper.class.php');

        $filename = self::get_filename($backupid);
        $dir = $CFG->dataroot . '/' . course::BACKUP_LOCALPATH . '/backup';
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

        $backup = new dataobj\backup(array('id' => $backupid));
        $userdataids = $backup->get_userdata_ids();

        // Needed to determine partial anonymisations.
        self::$coursecontext = \context_course::instance($courseid);

        $bc = new \backup_controller(\backup::TYPE_1COURSE, $courseid, \backup::FORMAT_MOODLE, \backup::INTERACTIVE_NO,
            \backup::MODE_AUTOMATED, $userid);
        $backupok = true;
        try {
            foreach ($settings as $setting => $value) {
                if ($bc->get_plan()->setting_exists($setting)) {
                    $bc->get_plan()->get_setting($setting)->set_value($value);
                }
            }
            if ($withusers && $userdataids !== null) {
                // Remove userdata from any activities that have been excluded.
                /** @var \backup_setting $setting */
                foreach ($bc->get_plan()->get_settings() as $setting) {
                    $parts = explode('_', $setting->get_name(), 3);
                    if (count($parts) < 3) {
                        continue;
                    }
                    if ($parts[2] != 'userinfo' || $parts[0] == 'section') {
                        continue;
                    }
                    if (!in_array($parts[1], $userdataids)) {
                        $setting->set_value(0); // Disable user data for this field.
                    }
                }
            }

            // Set the default filename.
            $format = $bc->get_format();
            $type = $bc->get_type();
            $id = $bc->get_id();
            $users = $bc->get_plan()->get_setting('users')->get_value();
            $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
            $bc->get_plan()->get_setting('filename')->set_value(
                \backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised));

            $bc->set_status(\backup::STATUS_AWAITING);

            // Class \backup_anonymizer_helper is missing methods for anonymizing certain user data fields (MDL-46541).
            @$bc->execute_plan();
            $results = $bc->get_results();
            $file = $results['backup_destination'];
            if (!check_dir_exists($dir)) {
                throw new \moodle_exception('errorbackupdir', 'block_mbstpl');
            }

            $filepath = $dir . '/' . $filename;
            @unlink($filepath);
            $outcome = $file->copy_content_to($dir . '/' . $filename);
            if ($outcome) {
                $file->delete();
            }

            $fs = get_file_storage();
            $context = \context_system::instance();
            $cleanfilename = $fs->get_unused_filename($context->id, 'block_mbstpl', 'backups', $backupid, '/', $filename);
            $filerecord = (object)array(
                'contextid' => $context->id,
                'component' => 'block_mbstpl',
                'filearea' => 'backups',
                'itemid' => $backupid,
                'filepath' => '/',
                'filename' => $cleanfilename,
                'userid' => $userid,
            );
            $fs->create_file_from_pathname($filerecord, $filepath);
            @unlink($filepath);
        } catch (\Exception $e) {
            $backupok = false;
        }

        self::$coursecontext = null;

        $bc->destroy();
        unset($bc);

        if ($backupok) {
            return $cleanfilename;
        }
        return false;
    }

    /**
     * Restore a backed up original course.
     * @param dataobj\backup $backup
     * @param string $fileid if not provided, latest backup will be used.
     * @return int courseid
     */
    private static function launch_primary_restore(dataobj\backup $backup, $fileid = false) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $catid = get_config('block_mbstpl', 'deploycat');
        if (!$catid || !$DB->record_exists('course_categories', array('id' => $catid))) {
            throw new \moodle_exception('errorcatnotexists', 'block_mbstpl');
        }

        $fs = get_file_storage();
        if ($fileid) {
            $file = $fs->get_file_by_id($fileid);
        } else {
            $context = \context_system::instance();
            $files = $fs->get_area_files($context->id, 'block_mbstpl', 'backups', $backup->id, false, null, false);
            $file = array_pop($files);
        }
        if (empty($file)) {
            throw new \moodle_exception('errornobackupfound', 'block_mbstpl', '', $backup->id);
        }
        $filename = uniqid('tpl') . '.mbz';
        $dir = $CFG->dataroot . '/' . course::BACKUP_LOCALPATH . '/restore/';
        if (!check_dir_exists($dir)) {
            throw new \moodle_exception('errorbackupdir', 'block_mbstpl');
        }
        $filepath = $dir . '/' . $filename;
        $file->copy_content_to($filepath);

        // Extraction mostly copied from \backup_general_helper::get_backup_information_from_mbz().
        $tmpname = 'mbstemplatting_' . $backup->id . '_' . $backup->lastversion . '_' . time();
        $tmpdir = $CFG->tempdir . '/backup/' . $tmpname;
        $fp = get_file_packer('application/vnd.moodle.backup');
        $extracted = $fp->extract_to_pathname($filepath, $tmpdir);
        @unlink($filepath);
        $moodlefile = $tmpdir . '/' . 'moodle_backup.xml';
        if (!$extracted || !is_readable($moodlefile)) {
            throw new \backup_helper_exception('missing_moodle_backup_xml_file', $moodlefile);
        }

        // Load info.
        $info = \backup_general_helper::get_backup_information($tmpname);

        // Create course.
        $cdata = (object)array(
            'category' => $catid,
            'shortname' => self::generate_course_shortname($info->original_course_shortname, $backup->lastversion),
            'fullname' => $info->original_course_fullname,
            'visible' => 0,
        );
        $course = create_course($cdata);

        // Restore.
        $admin = get_admin();
        self::$primaryrestoreof = $backup;
        try {
            $rc = new \restore_controller($tmpname, $course->id, false, \backup::MODE_SAMESITE,
                $admin->id, \backup::TARGET_NEW_COURSE);
            $rc->execute_precheck();
            $rc->execute_plan();
        } catch (\Exception $e) {
            throw new \moodle_exception('errorrestoringtemplate', 'block_mbstpl');
        }
        self::$primaryrestoreof = null;
        remove_dir($tmpdir);

        // Reset a few fields that are overwritten during 'TARGET_NEW_COURSE' restores.
        $upd = (object)array(
            'id' => $course->id,
            'shortname' => $cdata->shortname,
            'fullname' => $cdata->fullname,
            'visible' => $cdata->visible,
            'visibleold' => $cdata->visible,
        );
        $DB->update_record('course', $upd);

        return $course->id;
    }

    public static function fix_exclude_deploydata_ids($restoreid) {
        if (!self::$primaryrestoreof) {
            return; // Only include if we're doing a primary restore.
        }
        // The excludedeploydataids are the cmids in the original course that are not allowed to deploy with user data in them.
        // These need to be mapped onto the cmids in the template course (and saved into the template record).
        $excludeids = self::$primaryrestoreof->get_exclude_deploydata_ids();
        $newexcludeids = array();
        foreach ($excludeids as $excludeid) {
            $rec = \restore_dbops::get_backup_ids_record($restoreid, 'course_module', $excludeid);
            if ($rec && $rec->newitemid) {
                $newexcludeids[] = (int)$rec->newitemid;
            }
        }
        self::$mappedexcludedeploydataids = $newexcludeids;
    }

    /**
     * Backup an template
     * Similar to launch_automated_backup(), but with our own settings.
     *
     * @param int $courseid
     * @param int $templateid
     * @param array $backupsettings what parts to backup
     * @param int $userid
     * @param bool $includeall include all activities and user info.
     * @return mixed filename|false on error
     */
    private static function launch_secondary_backup($courseid, $templateid, $backupsettings, $userid, $includeall = false) {
        global $CFG;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/helper/backup_cron_helper.class.php');

        $filename = self::get_filename($templateid, false);
        $dir = $CFG->dataroot . '/' . course::BACKUP_LOCALPATH . '/backup';
        $settings = array_merge(array(
            'users' => 1,
            'anonymize' => 1,
            'role_assignments' => 0,
            'user_files' => 0,
            'activities' => 1,
            'blocks' => 1,
            'filters' => 1,
            'comments' => 0,
            'completion_information' => 0,
            'logs' => 0,
            'histories' => 0,
        ), $backupsettings);

        // Needed to determine partial anonymisations.
        self::$coursecontext = \context_course::instance($courseid);

        $bc = new \backup_controller(\backup::TYPE_1COURSE, $courseid, \backup::FORMAT_MOODLE, \backup::INTERACTIVE_NO,
            \backup::MODE_AUTOMATED, $userid);
        $backupok = true;
        try {

            foreach ($bc->get_plan()->get_settings() as $settingname => $setting) {

                if ($includeall) {
                    $ending = substr($settingname, -9);
                    if ($ending == '_userinfo' || $ending == '_included') {
                        $setting->set_value(1);
                        continue;
                    }
                }

                $hassetting = isset($settings[$settingname]);

                // Since 'users' and 'anonymize' needs to start as 1, we need to explicity set each
                // 'userinfo' setting, defaulting to 0 if it's not explicitly set by the user.
                if ($setting instanceof \backup_activity_userinfo_setting
                        || $setting instanceof \backup_section_userinfo_setting) {
                    $value = $hassetting ? $settings[$settingname] : 0;
                    $setting->set_value($value);
                } else if ($hassetting) {
                    $setting->set_value($settings[$settingname]);
                }
            }

            // Set the default filename.
            $format = $bc->get_format();
            $type = $bc->get_type();
            $id = $bc->get_id();
            $users = $bc->get_plan()->get_setting('users')->get_value();
            $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
            $bc->get_plan()->get_setting('filename')->set_value(
                \backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised));

            $bc->set_status(\backup::STATUS_AWAITING);

            // Class \backup_anonymizer_helper is missing methods for anonymizing certain user data fields (MDL-46541).
            @$bc->execute_plan();
            $results = $bc->get_results();
            $file = $results['backup_destination'];
            if (!check_dir_exists($dir)) {
                throw new \moodle_exception('errorbackupdir', 'block_mbstpl');
            }

            $filepath = $dir . '/' . $filename;
            @unlink($filepath);
            $outcome = $file->copy_content_to($dir . '/' . $filename);
            if ($outcome) {
                $file->delete();
            }
        } catch (\Exception $e) {
            $backupok = false;
        }

        self::$coursecontext = null;

        $bc->destroy();
        unset($bc);

        if ($backupok) {
            return $filename;
        }
        return false;
    }

    /**
     * Restore a backed up original course.
     * @param dataobj\tempate $template
     * @param string $filename
     * @param int $targetcat
     * @param int $targetcrs
     * @return int courseid
     */
    private static function launch_secondary_restore(dataobj\template $template, $filename, $targetcat = 0, $targetcrs = 0) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        // Move backup file to restore location.
        $backupdir = $CFG->dataroot . '/' . course::BACKUP_LOCALPATH . '/backup';
        $backuppath = $backupdir . '/' . $filename;
        $dir = $CFG->dataroot . '/' . course::BACKUP_LOCALPATH . '/backup';
        $filepath = $dir . '/' . $filename;
        if (!check_dir_exists($dir)) {
            throw new \moodle_exception('errorbackupdir', 'block_mbstpl');
        }
        if (!file_exists($backuppath)) {
            throw new \moodle_exception('errorrestorefilenotexists', 'block_mbstpl');
        }
        if (!rename($backuppath, $filepath)) {
            throw new \moodle_exception('errorcannotmovefile', 'block_mbstpl');
        }

        // Extraction mostly copied from \backup_general_helper::get_backup_information_from_mbz().
        $tmpname = 'mbstemplatting_' . $template->id . '_' . time();
        $tmpdir = $CFG->tempdir . '/backup/' . $tmpname;
        $fp = get_file_packer('application/vnd.moodle.backup');
        $extracted = $fp->extract_to_pathname($filepath, $tmpdir);
        @unlink($filepath);
        $moodlefile = $tmpdir . '/' . 'moodle_backup.xml';
        if (!$extracted || !is_readable($moodlefile)) {
            throw new \backup_helper_exception('missing_moodle_backup_xml_file', $moodlefile);
        }

        // Load info.
        $info = \backup_general_helper::get_backup_information($tmpname);

        $visible = 1;
        if ($targetcat == get_config('block_mbstpl', 'deploycat')) {
            $visible = 0;
        }
        if ($targetcat) {
            // Create course.
            $cdata = (object)array(
                'category' => $targetcat,
                'shortname' => self::generate_course_shortname($info->original_course_shortname, 1, false),
                'fullname' => $info->original_course_fullname,
                'visible' => $visible
            );
            $course = create_course($cdata);
            $restoretype = \backup::TARGET_NEW_COURSE;
        } else {
            $course = get_course($targetcrs);
            $restoretype = \backup::TARGET_EXISTING_ADDING;
        }

        // Restore.
        $admin = get_admin();
        try {
            $rc = new \restore_controller($tmpname, $course->id, false, \backup::MODE_SAMESITE,
                $admin->id, $restoretype);
            $rc->execute_precheck();
            $rc->execute_plan();
        } catch (\Exception $e) {
            throw new \moodle_exception('errorrestoringtemplate', 'block_mbstpl');
        }
        remove_dir($tmpdir);

        // Reset a few fields that are overwritten during 'TARGET_NEW_COURSE' restores.
        if ($restoretype == \backup::TARGET_NEW_COURSE && isset($cdata)) {
            $upd = (object)array(
                'id' => $course->id,
                'shortname' => $cdata->shortname,
                'fullname' => $cdata->fullname,
                'visible' => $cdata->visible,
                'visibleold' => $cdata->visible,
            );
            $DB->update_record('course', $upd);
        }

        return $course->id;
    }

    public static function build_html_block(dataobj\coursefromtpl $coursefromtpl, dataobj\template $template) {

        global $CFG, $DB, $PAGE;

        require_once($CFG->dirroot . "/lib/blocklib.php");
        require_once($CFG->dirroot . "/lib/pagelib.php");

        $page = new \moodle_page();
        $page->set_context(\context_course::instance($coursefromtpl->courseid));
        
        $blocktitle = get_string('newblocktitle', 'block_mbstpl');
        $licence = $template->get_license();
        $licencelink = \html_writer::link($licence->source, $licence->fullname);
        $licencestring = get_string('duplcourselicensedefault', 'block_mbstpl', array(
            'creator' => course::get_creators($template->id),
            'licence' => $licencelink
        ));
        $blockconfig = array(
            'title' => $blocktitle,
            'text' => array(
                'text' => $licencestring,
                'format' => FORMAT_HTML,
                'itemid' => file_get_submitted_draft_itemid('config_text')
            )
        );
        
        // There might be a teachSHARE-html block -> use it (don't add a second one)
        $blockrecords = $DB->get_records('block_instances', array('blockname' => 'html', 'parentcontextid' => $page->context->id));
        foreach ($blockrecords as $blockrecord) {
            $blockcontent = unserialize(base64_decode($blockrecord->configdata));
            if (!empty($blockcontent)) {
                if ($blockcontent->title == $blocktitle) {
                    $block = block_instance('html', $blockrecord, $page);
                    $block->instance_config_save((object)$blockconfig);
                    return;
                }
            }
            
        }

        // There is no teachSHARE-html block -> create one
        // Use the 1st available region of the theme's course layout.
        $region = $PAGE->theme->layouts['course']['regions'][0];

        $bm = new \block_manager($page);
        $bm->add_region($region);
        $bm->add_block('html', $region, 0, false, 'course-view-*');          
        
        $blockrecords = $DB->get_records('block_instances', array('blockname' => 'html', 'parentcontextid' => $page->context->id), 'id');
        $blockrecord = array_pop($blockrecords);
        $block = block_instance('html', $blockrecord, $page);
        $block->instance_config_save((object)$blockconfig);
    }

    /**
     * Adds the template backups to /backup/restorefile.php (needs to be called by core).
     * @param \context $currentcontext
     */
    public static function restorefile_add_tplbackups(\context $currentcontext) {
        if (!perms::can_viewbackups()) {
            return;
        }
        $renderer = course::get_renderer();
        echo $renderer->heading(get_string('coursetemplates', 'block_mbstpl'));
        echo $renderer->container_start();
        $files = self::get_backup_files(false);
        $renderer->render_backup_files_viewer();
        echo $renderer->container_end();
    }

    /**
     * Copy the backup and redirect from restorefile.php to the correct restore url.
     * @param $itemid
     * @param $filename
     */
    public static function restorefile_redirect_restore($itemid, $filename) {
        global $CFG;

        if (!perms::can_viewbackups()) {
            return;
        }

        $context = \context_system::instance();
        $fs = get_file_storage();
        if (!$file = $fs->get_file($context->id, 'block_mbstpl', 'backups', $itemid, '/', $filename)) {
            return;
        }

        $filename = \restore_controller::get_tempdir_name();
        $pathname = $CFG->tempdir . '/backup/' . $filename;
        $file->copy_content_to($pathname);
        $restore_url = new \moodle_url('/backup/restore.php', array(
            'contextid' => $context->id, 'filename' => $filename));
        redirect($restore_url);
    }

    /**
     * Returns all area files within the limit. Modified from get_area_files().
     * @param bool $justcount
     * @param int $limitfrom
     * @param int $limitnum
     * @param $sort
     * @return stored_file[] array of stored_files indexed by pathanmehash
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_backup_files($justcount = false, $limitfrom = null, $limitnum = null, $sort = '') {
        global $DB;

        $context = \context_system::instance();
        $params = array('contextid'=>$context->id, 'component'=>'block_mbstpl', 'filearea'=>'backups', 'dir'=> '.');
        $fs = get_file_storage();

        if (empty($sort)) {
            $sort = 'itemid ASC, filepath ASC, filename ASC';
        }

        $select = "
        SELECT f.id AS id, f.contenthash, f.pathnamehash, f.contextid, f.component, f.filearea, f.itemid,
                       f.filepath, f.filename, f.userid, f.filesize, f.mimetype, f.status, f.source, f.author,
                       f.license, f.timecreated, f.timemodified, f.sortorder, f.referencefileid,
                       r.repositoryid AS repositoryid, r.reference AS reference, r.lastsync AS referencelastsync
        ";
        $basesql = "
                  FROM {files} f
             LEFT JOIN {files_reference} r
                       ON f.referencefileid = r.id
                 WHERE f.contextid = :contextid
                       AND f.component = :component
                       AND f.filearea = :filearea
                       AND f.filename <> :dir
                 ORDER BY $sort
                       ";
        if ($justcount) {
            return $DB->count_records_sql("SELECT COUNT(1) $basesql", $params);
        }
        $sql = "
        $select
        $basesql
        ";
        $results = array();
        $filerecords = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        foreach ($filerecords as $filerecord) {
            $results[] = $fs->get_file_instance($filerecord);
        }
        return $results;
    }
}
