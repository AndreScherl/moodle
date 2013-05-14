<?php
// This file is part of the category backup plugin for Moodle - http://moodle.org/
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
 * Library functions used by category backup plugin
 *
 * @package    local_categorybackup
 * @copyright  2012 Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');

class categorybackup {
    protected static $categoryfields = array('id', 'name', 'idnumber', 'description', 'descriptionformat', 'sortorder', 'theme');
    protected static $delimiter = ',';

    // Heavily based on 'get_course_category_tree' in course/lib.php
    // but fixed to work when starting below the top-level category (and
    // does not ignore 'hidden' categories)
    public static function get_courses_and_categories($category, $depth = 0) {
        global $DB;

        $categories = get_child_categories($category->id);
        $categoryids = array();
        foreach ($categories as $key => $childcat) {
            $categoryids[$childcat->id] = $childcat;
            list($childcat->categories, $subcategories) = self::get_courses_and_categories($childcat, $depth+1);
            foreach ($subcategories as $subid => $subcat) {
                $categoryids[$subid] = $subcat;
            }
            $childcat->courses = array();
        }

        if ($depth > 0) {
            // This is a recursive call so return the required array
            return array($categories, $categoryids);
        }

        $categoryids[$category->id] = $category;
        $category->courses = array();
        $category->categories = $categories;
        if ($courses = $DB->get_records_list('course', 'category', array_keys($categoryids), '', 'id, category, shortname')) {
            // loop throught them
            foreach ($courses as $course) {
                if ($course->id == SITEID) {
                    continue;
                }
                $categoryids[$course->category]->courses[$course->id] = $course;
            }
        }
        return $categoryids;
    }

    public static function export_categories($catinfo, $startingid, $fp, $depth = 0) {
        // Save a list of categories (with contained courses) to the filepointer
        $cat = $catinfo[$startingid];

        $encdelim = '&#'.ord(self::$delimiter);

        $line = array($depth);
        foreach (self::$categoryfields as $field) {
            $line[] = str_replace(self::$delimiter, $encdelim, $cat->$field);
        }
        $line[] = implode(':', array_keys($cat->courses));

        fwrite($fp, implode(self::$delimiter, $line)."\n");

        foreach($cat->categories as $subcat) {
            self::export_categories($catinfo, $subcat->id, $fp, $depth+1);
        }
    }

    public static function zip_files($dirname, $category) {
        // Create a zip file from all the files in the directory
        // skipping .zip files and sub-directories
        // Name the zipfile after the category name
        if (!file_exists($dirname) || !is_dir($dirname)) {
            return false;
        }

        $archivefile = 'backup-'.$category->name.'.zip';
        $archivefile = clean_param(strtolower($archivefile), PARAM_FILE);
        $archivefile = $dirname.'/'.$archivefile;

        $ziparch = new zip_archive();
        if (!$ziparch->open($archivefile, file_archive::OVERWRITE)) {
            return false;
        }

        if ($dir = opendir($dirname)) {
            while (false !== ($file = readdir($dir))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                if (substr_compare($file, '.zip', -4) == 0) {
                    continue;
                }
                if (is_dir($dirname.'/'.$file)) {
                    continue;
                }

                $ziparch->add_file_from_pathname($file, $dirname.'/'.$file);
            }
            closedir($dir);
        }
        $ziparch->close();

        return $archivefile;
    }

    public static function unzip_files($zipfile, $destdir) {
        if (!file_exists($zipfile) || is_dir($zipfile)) {
            return false;
        }
        if (!file_exists($destdir) || !is_dir($destdir)) {
            return false;
        }

        $ziparch = new zip_archive();
        if (!$ziparch->open($zipfile, file_archive::OPEN)) {
            return false;
        }

        foreach ($ziparch as $info) {
            if ($info->is_directory) {
                continue; // There shouldn't be and directories - skip them
            }

            $pathinfo = pathinfo($info->pathname);
            $newfile = $destdir.'/'.$pathinfo['basename'];
            if (!$fp = fopen($newfile, 'wb')) {
                return false; // Unable to open destination file
            }
            if (!$fz = $ziparch->get_stream($info->index)) {
                fclose($fp); // Unable to open file within zip archive
                return false;
            }
            while (!feof($fz)) {
                $content = fread($fz, 262143);
                fwrite($fp, $content);
            }
            fclose($fz);
            fclose($fp);
            if (filesize($newfile) !== $info->size) {
                @unlink($newfile); // Size of new file does not match archive
                return false;
            }
        }
        $ziparch->close();
        return true;
    }

    public static function untgz_files($zipfile, $destdir) {
        if (!file_exists($zipfile) || is_dir($zipfile)) {
            return false;
        }
        if (!file_exists($destdir) || !is_dir($destdir)) {
            return false;
        }

        $out = array();
        $ret = 0;
        $cmd = "tar -xvzf \"$zipfile\" -C \"$destdir\"";
        exec($cmd, $out, $ret);
        if ($ret != 0) {
            echo $cmd;
            return false;
        }

        return true;
    }

    public static function tgz_files($dirname, $category) {
        // Create a zip file from all the files in the directory
        // skipping .zip files and sub-directories
        // Name the zipfile after the category name
        if (!file_exists($dirname) || !is_dir($dirname)) {
            return false;
        }

        $archivefile = str_replace(' ','_','backup-'.$category->name.'.tgz');
        $archivefile = clean_param(strtolower($archivefile), PARAM_FILE);
        $archivefile = $dirname.'/'.$archivefile;

        $cmd = "cd \"$dirname\" && tar -czf \"$archivefile\" --exclude=*.tgz --exclude=*.zip *";
        $out = array();
        $retval = 0;
        exec($cmd, $out, $retval);

        if ($retval == 0) {
            return $archivefile;
        }

        echo $cmd;
        return false;
    }

    public static function delete_files($destdir) {
        // Recursively remove all the files in this folder (leaving .zip and .tgz files intact)
        if ($dir = opendir($destdir)) {
            while (false !== ($file = readdir($dir))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                if (substr_compare($file, '.zip', -4) == 0) {
                    continue;
                }
                if (substr_compare($file, '.tgz', -4) == 0) {
                    continue;
                }
                $fullpath = $destdir.'/'.$file;
                if (is_dir($fullpath)) {
                    self::delete_files($fullpath);
                    @rmdir($fullpath);
                    continue;
                }
                unlink($fullpath);
            }
            closedir($dir);
        }
    }

    public static function create_categories($newcats, $parentcategory) {
        global $DB;

        // Loop through each line in the 'categories.lst' file and create categories as needed
        $encdelim = '&#'.ord(self::$delimiter);
        $depth = 0;
        $parents = array();
        $parentid = $parentcategory->id;
        $categorymapping = array();
        $categoryfields = array_merge(array('depth'), self::$categoryfields, array('courses'));

        $lastcategoryid = 0;
        foreach ($newcats as $newcat) {
            $info = explode(self::$delimiter, $newcat);
            foreach ($info as $key => $item) {
                $info[$key] = str_replace($encdelim, self::$delimiter, $item);
            }
            $details = array_combine($categoryfields, $info);
            if ($details['depth'] > $depth) {
                if ($lastcategoryid) {
                    // Last category processed is now the parent
                    array_push($parents, $parentid);
                    $parentid = $lastcategoryid;
                }
            } else if ($details['depth'] < $depth) {
                // Moving back up the category tree
                $parentid = array_pop($parents);
            }
            $depth = $details['depth'];

            $inscat = new stdClass();
            $inscat->name = $details['name'];
            $inscat->idnumber = empty($details['idnumber']) ? '' : $details['idnumber'];
            $inscat->idnumber = empty($details['description']) ? '' : $details['description'];
            $inscat->idnumber = empty($details['descriptionformat']) ? FORMAT_HTML : $details['descriptionformat'];
            $inscat->idnumber = empty($details['sortorder']) ? '' : $details['sortorder'];
            $inscat->idnumber = empty($details['theme']) ? '' : $details['theme'];
            $inscat->parent = $parentid;

            if ($prevcat = $DB->get_record('course_categories', array('parent' => $inscat->parent,
                                                                      'name' => $inscat->name))) {
                // Category with that name already exists in the right place in the hierarchy - use instead of creating a new one
                $lastcategoryid = $prevcat->id;
                $msg = get_string('existingcategory', 'local_categorybackup', $inscat->name);
                if (!defined('CLI_SCRIPT')) {
                    echo html_writer::tag('li', $msg);
                } else {
                    echo '* '.$msg."\n";
                }
            } else {
                // Create a new category
                if (!$lastcategoryid = $DB->insert_record('course_categories', $inscat)) {
                    print_error('cannotcreatecategory', 'local_categorybackup');
                }
                $msg = get_string('createdcategory', 'local_categorybackup', $inscat->name);
                if (!defined('CLI_SCRIPT')) {
                    echo html_writer::tag('li', $msg);
                } else {
                    echo '* '.$msg."\n";
                }
            }

            if (!empty($details['courses'])) {
                // Note the courses within each category (mapped to the new category id)
                $courses = explode(':', $details['courses']);
                foreach ($courses as $courseid) {
                    $categorymapping[$courseid] = $lastcategoryid;
                }
            }
        }

        return $categorymapping;
    }

    public static function restore_courses($srcdir, $categorymapping) {
        global $CFG, $USER;

        // Restore all the 'mbz' files found in the temporary directory
        $unpackdir = 'categorybackup_unpack'.$USER->id;
        $unpackpath = $CFG->tempdir.'/backup/'.$unpackdir;
        if (!file_exists($unpackpath)) {
            mkdir($unpackpath, 0777, true);
        } else if (!is_dir($unpackpath)) {
            print_error('invaliddir', 'local_categorybackup');
        }

        $CFG->categorybackup_restore = true;

        if ($dir = opendir($srcdir)) {
            while (false !== ($file = readdir($dir))) {
                if (substr_compare($file, '.mbz', -4) == 0) {
                    self::restore_course($unpackdir, $srcdir.'/'.$file, $categorymapping);
                }
            }
            closedir($dir);
        }

        $CFG->categorybackup_restore = false;
    }

    protected static function restore_course($unpackdir, $file, $categorymapping) {
        global $USER, $CFG, $DB;

        // Extract the files
        $unpackpath = $CFG->tempdir.'/backup/'.$unpackdir;
        $fb = get_file_packer();
        $fb->extract_to_pathname($file, $unpackpath);

        if (backup_general_helper::detect_backup_format($unpackdir) != backup::FORMAT_MOODLE) {
            print_error('unsupportedbackupformat', 'local_categorybackup');
        }

        // Gather information about the course being restored
        $details = backup_general_helper::get_backup_information($unpackdir);

        $fullname = $details->original_course_fullname;
        $shortname = $details->original_course_shortname;
        $categoryid = $categorymapping[$details->original_course_id];

        if (empty($categoryid)) {
            echo "Unable to find mapping for course {$details->original_course_id}<br/>\n";
            return;
        }

        if ($DB->record_exists('course', array('category' => $categoryid, 'shortname' => $shortname))) {
            // A course with this name already exists in this category - skip restoring this course
            $category = $DB->get_record('course_categories', array('id' => $categoryid));
            $msgdetails = new stdClass();
            $msgdetails->shortname = $shortname;
            $msgdetails->category = $category->name;
            echo html_writer::tag('li', get_string('coursealreadyexists', 'local_categorybackup', $msgdetails));
            categorybackup::delete_files($unpackpath);
            @rmdir($unpackpath);
            return;
        }

        // Create a course to restore into
        list($fullname, $shortname) = restore_dbops::calculate_course_names(0, $fullname, $shortname);
        $courseid = restore_dbops::create_new_course($fullname, $shortname, $categoryid);
        $context = context_course::instance($courseid); // Make sure the context has been created

        // Do the actual restore
        $msg = get_string('restorecourse', 'local_categorybackup', (object)array(
            'fullname' => $fullname, 'shortname' => $shortname
        ));
        if (!defined('CLI_SCRIPT')) {
            echo html_writer::tag('li', $msg);
        } else {
            echo '* '.$msg."\n";
        }
        flush();
        $rc = new restore_controller($unpackdir, $courseid, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id, backup::TARGET_NEW_COURSE);
        if (!$rc->execute_precheck()) {
            $res = $rc->get_precheck_results();
            if (isset($res['warnings'])) {
                foreach ($res['warnings'] as $warning) {
                    echo html_writer::tag('p', $warning);
                }
            }
            if (isset($res['errors'])) {
                $DB->delete_records('course', array('id' => $courseid));
                echo "Error during precheck<br/>\n";
                foreach ($res['errors'] as $error) {
                    echo html_writer::tag('p', $error);
                }
                die();
            }
        }
        $rc->execute_plan();
    }
}
