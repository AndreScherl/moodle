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
 * Main class of local mbslicenseinfo
 *
 * @package   local_mbslicenseinfo
 * @copyright 2015, ISB Bayern
 * @author    Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mbslicenseinfo\local;

defined('MOODLE_INTERNAL') || die();

class mbslicenseinfo {

    /**
     * To group the files by content hash and order them, 
     * we must fetch the license data in two steps:
     * 
     * 1. we search all the files ordered title ASC and id DESC to get no edited
     * and most recent entries first and group it by contenthash, so we can limit
     * the result to paging size.
     * 
     * 2. we get all files of the course belonging to one contenthash, which means
     * that multiple occurances will be detected and the entries can be grouped
     * by physical file existance.
     * 
     * @param int $courseid
     * @param int $limitfrom
     * @param int $limitsize
     * @param int $onlyincomplete
     * @param int $onlymine value of 1 will show all files, note that capability check must be done before!
     * @return \stdClass object containing result information
     */
    public function get_coursefiles_data($courseid, $limitfrom, $limitsize, $pageparams) {
        global $DB, $USER;

        $select = "SELECT f.contenthash ";
        $countselect = "SELECT count(DISTINCT f.contenthash) as total ";

        $from = "FROM {files} f
                 JOIN {context} c ON f.contextid = c.id AND c.contextlevel >= :contextlevel";

        // Get where.
        $cond = array(" f.filename <> '.' AND f.filearea <> 'draft' ");
        $params = array('contextlevel' => CONTEXT_COURSE);

        // Restrict to coursecontext.
        $coursecontext = \context_course::instance($courseid);
        $cond[] = $DB->sql_like('c.path', ':contextpath');
        $params['contextpath'] = $coursecontext->path . '%';

        // Restrict to mimetypes.
        $neededmimetypes = get_config('local_mbslicenseinfo', 'mimewhitelist');
        if (!empty($neededmimetypes)) {
            $list = explode(',', $neededmimetypes);
            $cond[] = " f.mimetype IN ('" . implode("', '", $list) . "') ";
        } else {
            // When no mime type is checked, show nothing.
            $cond[] = ' 1 = 2 ';
        }

        // Show only incomplete.
        if (!empty($pageparams['onlyincomplete'])) {
            $from .= "LEFT JOIN {local_mbslicenseinfo_fmeta} fm ON fm.files_id = f.id ";
            $cond[] = " ((fm.title = '') OR (fm.title IS NULL) OR (fm.source = '') OR (fm.source IS NULL)) ";
        }

        // Show only own.
        if (!empty($pageparams['onlymine'])) {
            $cond[] = ' f.userid = :userid ';
            $params['userid'] = $USER->id;
        }

        $where = "WHERE " . implode(" AND ", $cond);

        // Build SQL.
        $sql = $select . $from . $where . "GROUP BY f.contenthash ORDER BY f.id desc";

        /*$esql = str_replace('{', 'mdl_', $sql);
        $esql = str_replace('}', '', $esql);
        foreach ($params as $key => $value) {
            $esql = str_replace(':' . $key, "'$value'", $esql);
        }

        print_r($esql);*/

        $countsql = $countselect . $from . $where;

        $result = new \stdClass();
        $result->total = 0;
        $result->data = array();

        // Step 1: Get the contenthashes ordered by empty title and most recent.
        if (!$result->total = $DB->count_records_sql($countsql, $params)) {
            return $result;
        }

        if (!$orderedhashes = $DB->get_records_sql($sql, $params, $limitfrom, $limitsize)) {
            return $result;
        }

        // For each content hash retrieve other coursefiles with same content hash.
        $contenthashes = array_keys($orderedhashes);

        list($incontenthash, $inparams) = $DB->get_in_or_equal($contenthashes, SQL_PARAMS_NAMED);
        $params = $params + $inparams;

        $select = "SELECT f.id, f.contenthash, f.filename, f.author, fm.title, fm.source, f.license, f.userid
                   FROM {files} f
                   JOIN {context} c ON f.contextid = c.id 
                   LEFT JOIN {local_mbslicenseinfo_fmeta} fm ON fm.files_id = f.id ";

        $where .= " AND f.contenthash {$incontenthash}";

        $orderby = " ORDER by f.id desc";

        $sql = $select . $where . $orderby;

        if (!$allcoursefiles = $DB->get_records_sql($sql, $params)) {
            return array();
        }

        $filesordered = array();
        foreach ($allcoursefiles as $file) {

            if (!isset($filesordered[$file->contenthash])) {
                $filesordered[$file->contenthash] = array();
            }

            $filesordered[$file->contenthash][$file->id] = new mbsfile($file);
        }

        $result->data = $filesordered;

        return $result;
    }

    /**
     * Update the course files information
     * 
     * @param object $data - object containing form data (arrays for fiels with similar name)
     * @return bool - success of operation
     */
    public static function update_course_files($data) {
        global $DB, $USER;

        $files = self::resort_formdata($data);
        $success = true;

        foreach ($files as $file) {

            // Insert/update local_mbslicenseinfo_fmeta table.
            $filemeta = new \stdClass();
            $filemeta->title = $file->title;
            $filemeta->source = $file->source;
            if ($fmid = $DB->get_field('local_mbslicenseinfo_fmeta', 'id', array('files_id' => $file->id))) {
                $filemeta->id = $fmid;
                $success *= $DB->update_record('local_mbslicenseinfo_fmeta', $filemeta);
            } else {
                $filemeta->files_id = $file->id;
                $success *= $DB->insert_record('local_mbslicenseinfo_fmeta', $filemeta);
            }

            // User license stuff.
            $ul = $file->license;
            // Insert local_mbslicenseinfo_ul table.
            if ($ul->shortname == '__createnewlicense__') {
                $ul->userid = $USER->id;
                $ul->shortname = null;
                $newulid = $DB->insert_record('local_mbslicenseinfo_ul', $ul);
                $ul->id = $newulid;
                $ul->shortname = 'ul_' . $newulid;
                $success *= $DB->update_record('local_mbslicenseinfo_ul', $ul);
            }
            // Update local_mbslicenseinfo_ul table.
            if ($ulic = $DB->get_record('local_mbslicenseinfo_ul', array('shortname' => $ul->shortname))) {
                $ul->fullname = empty($ul->fullname) ? $ulic->fullname : $ul->fullname;
                $ul->source = empty($ul->source) ? $ulic->source : $ul->source;
                $ul->id = $ulic->id;
                $ul->userid = $ulic->userid;
                $success *= $DB->update_record('local_mbslicenseinfo_ul', $ul);
            }

            // Update files table (no insert because the file must exist).
            $filedata = new \stdClass();
            $filedata->id = $file->id;
            $filedata->author = $file->author;
            $filedata->license = $file->license->shortname;
            $success *= $DB->update_record('files', $filedata);
        }

        return $success;
    }

    /**
     * Change the sort style of edit form data from column to row
     * 
     * @param object $data - object containing form data (arrays for fiels with similar name)
     * @return array of mbsfiles objects
     */
    protected static function resort_formdata($data) {

        $files = array();

        foreach ($data->fileid as $i) {

            $file = new \stdClass();
            $file->id = $data->fileid[$i];
            $file->filename = $data->filename[$i];
            $file->title = $data->title[$i];
            $file->source = $data->filesource[$i];
            $file->author = $data->author[$i];

            $license = new \stdClass();
            $license->id = (empty($data->licenseid[$i])) ? null : $data->licenseid[$i];
            $license->userid = (empty($data->licenseuserid[$i])) ? null : $data->licenseuserid[$i];
            $license->shortname = (empty($data->licenseshortname[$i])) ? null : $data->licenseshortname[$i];
            $license->fullname = (empty($data->licensefullname[$i])) ? null : $data->licensefullname[$i];
            $license->source = (empty($data->licensesource[$i])) ? null : $data->licensesource[$i];
            $file->license = $license;
            $files[] = $file;
        }

        return $files;
    }

    /**
     * Get all the mimetype moodle can deal with, group it and create an menu for
     * multicheckboxes in admin settings.
     * 
     * @return array
     */
    public static function get_grouped_mimetypes_menu() {
        global $OUTPUT;

        $mimetypes = get_mimetypes_array();

        $mimetypegrouped = array();
        foreach ($mimetypes as $fileext => $mimetype) {
            if (!isset($mimetypegrouped[$mimetype['type']])) {
                $mimetypegrouped[$mimetype['type']] = array();
                $mimetypegrouped[$mimetype['type']]['fileext'] = array();
                $mimetypegrouped[$mimetype['type']]['icon'] = $mimetype['icon'];
            }
            $mimetypegrouped[$mimetype['type']]['fileext'][] = $fileext;
        }

        $choices = array();
        foreach ($mimetypegrouped as $mimetype => $item) {
            $icon = $OUTPUT->pix_icon('f/' . $item['icon'], $mimetype, 'moodle', array('class' => 'iconsmall'));
            $choices[$mimetype] = $icon . ' ' . $mimetype . " [" . implode(", ", $item['fileext']) . "]";
        }

        asort($choices);

        return $choices;
    }

    /**
     * Check whether this user has the capability to edit at least his own or all licenes.
     * Note that we check own license capability first, because normally user with 
     * cap "all" has cap for "own too.
     * 
     * @param context $context the context, i. e. course context.
     * @return boolean true, when user has the on af the capabilities
     */
    public static function can_edit_license($context) {
        return ((has_capability('local/mbslicenseinfo:editownlicenses', $context)) OR (has_capability('local/mbslicenseinfo:editalllicenses', $context)));
    }

    /**
     * Get and set the users preference for showing only incomplete license information
     * 
     * @return int 0 = show incomplete and complete licenses information
     */
    public static function get_onlyincomplete_pref() {

        $userincomplete = get_user_preferences('mbslicenseshowincomplete', 0);
        $onlyincomplete = optional_param('onlyincomplete', $userincomplete, PARAM_INT);
        if ($onlyincomplete <> $userincomplete) {
            set_user_preference('mbslicenseshowincomplete', $onlyincomplete);
        }
        return $onlyincomplete;
    }

    /**
     * Get and set users preference to show license information only for the files
     * uploaded by this user depending on the capability local/mbslicenseinfo:editalllicenses.
     * 
     * @param object $coursecontext
     * @return int 0 = show licenseinformation for all course files
     */
    public static function get_onlymine_pref($coursecontext) {

        $caneditall = has_capability('local/mbslicenseinfo:editalllicenses', $coursecontext);

        $useronlymine = get_user_preferences('mbslicensesonlymine', 1);
        if (!$caneditall) {
            $onlymine = 1;
        } else {
            $onlymine = optional_param('onlymine', $useronlymine, PARAM_INT);
        }
        if ($onlymine <> $useronlymine) {
            set_user_preference('mbslicensesonlymine', $onlymine);
        }

        return $onlymine;
    }

}
