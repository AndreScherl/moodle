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

    public static $captype_viewall = 10;
    public static $captype_editown = 20;
    public static $captype_editall = 30;
    
    public static $component = 'local_mbslicenseinfo';
    public static $fileareathumb = 'mbslicenseinfo_thumbs';

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

        $countsql = $countselect . $from . $where;

        $result = new \stdClass();
        $result->total = 0;
        $result->data = array();

        // Step 1: Get the contenthashes ordered by empty title and most recent.
        if (!$result->total = $DB->count_records_sql($countsql, $params)) {
            return $result;
        }
        //print_r(self::create_sql($sql, $params));
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
        //print_r(self::create_sql($sql, $params));
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
     * To group the files by content hash and order them,
     * we must fetch the license data in two steps:
     *
     * 1. We search all the files meeting the searchtext ordered title ASC and id DESC to get no edited
     * and most recent entries first and group it by contenthash.
     *
     * 2. We get all files of the course belonging to one contenthash, which means that multiple occurances will be detected
     * and the entries can be grouped by physical file existance.
     *
     * @param int $courseid
     * @param int $pageparams
     * @param int $searchtext
     * @return array containing result information
     */
    public function search_coursefiles($courseid, $pageparams, $searchtext) {
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
            $from .= " LEFT JOIN {local_mbslicenseinfo_fmeta} fm ON fm.files_id = f.id ";
            $cond[] = " ((fm.title = '') OR (fm.title IS NULL) OR (fm.source = '') OR (fm.source IS NULL)) ";
        }

        // Show only own.
        if (!empty($pageparams['onlymine'])) {
            $cond[] = ' f.userid = :userid ';
            $params['userid'] = $USER->id;
        }

        $where = "WHERE " . implode(" AND ", $cond);

        // Searchparams.
        $search = ' ' . $DB->sql_like('f.filename', ':filename', false) . ' ';
        $params['filename'] = '%' . $searchtext . '%';
        if (empty($pageparams['onlyincomplete'])) {
            $from .= " LEFT JOIN {local_mbslicenseinfo_fmeta} fm ON fm.files_id = f.id ";
            $search = '(' . $search . ' OR ' . $DB->sql_like('fm.title', ':name', false) . ') ';
            $params['name'] = '%' . $searchtext . '%';
        }
        $cond[] = $search;

        $wheresearch = "WHERE " . implode(" AND ", $cond);

        // Build SQL.
        $sql = $select . $from . $wheresearch . "GROUP BY f.contenthash ORDER BY f.id desc";

         $result = array();
        // Step 1: Get the contenthashes ordered by empty title and most recent.
        if (!$orderedhashes = $DB->get_records_sql($sql, $params)) {
            return $result;
        }

        // Step 2: For each content hash retrieve other coursefiles with same content hash.
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

        // Order files by contenthashes.
        foreach ($allcoursefiles as $file) {
            if (!isset($result[$file->contenthash])) {
                $result[$file->contenthash] = array();
            }
            $result[$file->contenthash][$file->id] = new mbsfile($file);
        }

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
            $success *= self::set_fmeta($filemeta, $file->id);

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
     * Check which capability the user has on licenses.
     *
     * @param context $context the context, i. e. course context.
     * @return boolean false, when user has none of the capabilities otherwise cap constant
     */
    public static function get_license_capability($context) {

        if (has_capability('local/mbslicenseinfo:editalllicenses', $context)) {
            return self::$captype_editall;
        }

        if (has_capability('local/mbslicenseinfo:editownlicenses', $context)) {
            return self::$captype_editown;
        }

        if (has_capability('local/mbslicenseinfo:viewalllicenses', $context)) {
            return self::$captype_viewall;
        }

        return false;
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

        $captype = self::get_license_capability($coursecontext);

        $useronlymine = get_user_preferences('mbslicensesonlymine', 1);

        switch ($captype) {

            case self::$captype_editall :
                $onlymine = optional_param('onlymine', $useronlymine, PARAM_INT);
                break;

            case self::$captype_editown :
                $onlymine = 1;
                break;

            case self::$captype_viewall :
                $onlymine = 0;
                break;
        }

        if ($onlymine <> $useronlymine) {
            set_user_preference('mbslicensesonlymine', $onlymine);
        }

        return $onlymine;
    }

    /**
     * Get filemeta data: title and source.
     *
     * @global $DB
     * @param int $fileid id of the file in {files} table
     * @return object
     */
    public static function get_fmeta($fileid) {
        global $DB;

        return $DB->get_record('local_mbslicenseinfo_fmeta', array('files_id' => $fileid), 'title, source');
    }

    /**
     * Set filemeta data.
     *
     * @param object $filemeta metadata to insert/update
     * @param int $fileid id of the file in {files} table
     * @return bool|int true or new id
     */
    public static function set_fmeta($filemeta, $fileid) {
        global $DB;
        if ($fmid = $DB->get_field('local_mbslicenseinfo_fmeta', 'id', array('files_id' => $fileid))) {
            $filemeta->id = $fmid;
            return $DB->update_record('local_mbslicenseinfo_fmeta', $filemeta);
        } else {
            $filemeta->files_id = $fileid;
            return $DB->insert_record('local_mbslicenseinfo_fmeta', $filemeta);
        }
    }

    // +++ Functions below belong to the license meta data HACK! +++++++++++++++

    /**
     * Render the upload form of the filepicker to add Title and source field.
     *
     * This is intentionally NOT done by overriding this files renderer, because this
     * would require to copy a lot of code here, that is declared "private".
     *
     * @called Hack in:
     *  - /files/renderer.php
     *
     * @return string HTML
     */
    public static function fp_js_template_uploadform_add_license_formfields() {

        $additionallisenseformfields = '
                 <div class="fp-licensetitle control-group clearfix">
                    <label class="control-label">' . get_string('editlicensesformfiletitle', 'local_mbslicenseinfo') . '</label>
                    <div class="controls">
                        <input type="text" name="licensetitle" />
                    </div>
                </div>
                 <div class="fp-licensesource control-group clearfix">
                    <label class="control-label">' . get_string('editlicensesformfileurl', 'local_mbslicenseinfo') . '</label>
                    <div class="controls">
                        <input type="text" name="licensesource"/>
                    </div>
                </div>';
        return $additionallisenseformfields;
    }

    /**
     * Store license information for a file.
     *
     * @called from Hack in:
     *   - /repository/upload/lib.php - process_upload(), when file is uploaded.
     *   - /repository/lib.php - update_draftfile(), when file is edited.
     *
     * @param type $storedfile
     */
    public static function store_license_meta_from_request($storedfile) {

        $licensetitle = optional_param('licensetitle', '', PARAM_TEXT);
        $licensesource = optional_param('licensesource', '', PARAM_URL);

        // Save meta license info.
        $fileid = $storedfile->get_id();

        $fmeta = new\stdClass();
        $fmeta->source = $licensesource;
        $fmeta->title = $licensetitle;

        self::set_fmeta($fmeta, $fileid);
    }

    /**
     * Copy the license meta data from draft
     *
     * @called from Hack in:
     *   - /lib/filelib.php - file_prepare_draft_area(),
     *      when draft files are loaded (real file => draft file).
     *
     *   - /lib/filelib.php - file_save_draft_area_files(),
     *      when draft files are saved (draft files => real file).
     *
     * @param \stored_file $fromfile
     * @param \stored_file $tofile
     */
    public static function copy_license_meta_data($fromfile, $tofile) {

        // Check, whether there is a meta data for draft file.
        $fromfileid = $fromfile->get_id();

        // Is there any meta data?
        if (!$fromfmeta = self::get_fmeta($fromfileid)) {
            return true;
        }

        // Save meta to file.
        $tofileid = $tofile->get_id();
        self::set_fmeta($fromfmeta, $tofileid);
    }

    /**
     * Adding all the license meta data to list draft file objects.
     *
     * @called from Hack in:
     *  - /lib/filelib.php - file_get_drafarea_files(),
     *
     * @param array $files list of draft files.
     * @return array list of draft file objects.
     */
    public static function add_licensemeta_to_draft_files($files) {
        global $DB;

        if (empty($files)) {
            return $files;
        }

        // Collect file ids.
        $fileids = array();
        foreach ($files as $file) {
            $fileids[] = $file->id;
        }

        $fmeta = $DB->get_records_list('local_mbslicenseinfo_fmeta', 'files_id', $fileids, '', 'files_id, title, source');

        foreach ($files as $file) {

            if (isset($fmeta[$file->id])) {
                $file->licensesource = $fmeta[$file->id]->source;
                $file->licensetitle = $fmeta[$file->id]->title;
            } else {
                $file->licensesource = '';
                $file->licensetitle = '';
            }
        }

        return $files;
    }

    // --- Functions above belong to the license meta data HACK! +++++++++++++++

    /**
     * Delete all meta data, that belong to deleted files.
     */
    public static function cleanup_fmeta() {
        global $DB;

        $cleanupcount = get_config('local_mbslicenseinfo', 'cleanupcount');

        $sql = "SELECT meta.id
                FROM {local_mbslicenseinfo_fmeta} meta
                LEFT JOIN {files} f ON f.id = meta.files_id
                WHERE f.id IS NULL ";

        if (!$fmetaids = $DB->get_records_sql($sql, array(), 0, $cleanupcount)) {
            return;
        }

        $DB->delete_records_list('local_mbslicenseinfo_fmeta', 'id', array_keys($fmetaids));
    }


    public static function create_sql($sql, $params) {

        foreach ($params as $key => $param) {
            $sql = str_replace(':' . $key, $param, $sql);
        }

        $sql = str_replace("{", "mdl_", $sql);
        $sql = str_replace("}", "", $sql);

        return $sql;
    }

    /** 
     * Generates the image url with correct filearea
     *
     * @param int $contextid
     * @param string $imagename
     * @return boolean|string the plugin url to image if succeeded otherwise false
     */
    public static function get_previewimageurl($contextid, $imagename, $path) {

        if (empty($imagename)) {
            return false;
        }

        $url = new \moodle_url("/pluginfile.php/$contextid/" . self::$component . "/" . self::$fileareathumb . $path . $imagename);
        return $url->out();
    }

    /**
     * Get a preview file from file storage
     *
     * @param int $contextid
     * @param string $imagename
     * @param array $args extra arguments (original component, original filearea, original itemid, original filepath)
     * @return boolean|stored_file the file if succeeded otherwise false
     */
    public static function get_previewfile($contextid, $imagename, $args) {
        
        $component = array_shift($args);
        $filearea = array_shift($args);
        $itemid = array_shift($args);
        $filepath = '/'.array_shift($args).'/';
        
        $fs = get_file_storage();
        
        $stored_file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $imagename);
        
        if ($stored_file) {            
            $stored_file->component = self::$component;
            $stored_file->filearea = self::$fileareathumb;
            $preview_file = $fs->get_file_preview($stored_file, 'thumb');
            
            if ($preview_file) {                
                $icon = array('contextid' => $contextid, 'component' => self::$component, 'filearea' => self::$fileareathumb, 'itemid' => 0, 
                    'filepath' => '/'.$component.'/'.$filearea.'/'.$itemid.$filepath, 'filename' => $imagename); 
                $fs->create_file_from_storedfile($icon, $preview_file);

                return $preview_file;
            } 
            return false;
        } else {
            return false;
        }
    }

    /**
     * Remove Thumbnails of License Informations if original file is deleted.
     * 
     * @global moodle_database $DB
     * @param record $event event data.
     */
    public static function delete_previewfile($event) {
        global $DB;
        
        $eventdata = $event->get_data();
        $coursemoduleid = $eventdata['objectid'];
        $modulename = 'mod_' . $eventdata['other']['modulename'];
        
        $contextid = $DB->get_field_select('context', 'id', 'instanceid = :id AND contextlevel = :level', array('id' => $coursemoduleid, 'level' => 70));

        $thumbfiles = $DB->get_recordset('files', array('contextid' => $contextid, 'component' => self::$component, 'filearea' => self::$fileareathumb));
        if (!empty($thumbfiles)) {
            $select_orgfiles = 'contextid = '.$contextid. ' and component = "'.$modulename. '" and mimetype LIKE "image/%" and filename <> "."';
            $orgfiles = $DB->get_records_select('files', $select_orgfiles);
            if (empty($orgfiles)) {
                // delete all thumbfiles.
                $DB->delete_records('files', array('contextid' => $contextid, 'component' => self::$component, 'filearea' => self::$fileareathumb));
            } else {
                // delete thumbfile if original file was deleted.
                foreach ($thumbfiles as $thumb) {
                    $delete = true;
                    foreach ($orgfiles as $f) {
                        if ($thumb->filename == $f->filename) $delete = false;
                    }
                    if ($delete) $DB->delete_records('files', array('id' => $thumb->id));
                }
            }
        }
    }
    
    /**
     * Update mebis license tables with entered data from H5P Plugin
     * 
     * @param int $cmid Course module id of hvp instance
     */
    public static function update_licenseinfo_from_hvp_to_moodle($cmid) {
        global $DB;
        
        // Get the license info of h5p instances contents.
        $hvpcm = $DB->get_record('course_modules', array('id' => $cmid));
        $hvpstring = $DB->get_field('hvp', 'json_content', array('course' => $hvpcm->course, 'id' => $hvpcm->instance));
        $hvpcontent = json_decode($hvpstring);
        $fileinfos = self::find_hvpfileobject_with_license($hvpcontent);
        $contextid = $DB->get_field_select('context', 'id', 'instanceid = :id AND contextlevel = :level', array('id' => $cmid, 'level' => 70));

        // Store the license infos into appropriate mebis tables.
        // Note: If the user adds a file into a hvp plugin library instance, it's immediately added to moodles files table. So we can work with these records.
        foreach($fileinfos as $fileinfo) {
            if (!isset($fileinfo->path)) {
                continue;
            }
            $filename = explode('/', $fileinfo->path)[1];

            // Note: No need to write into license table or block_mbslicenseinfo_ul, because the hvp popup form doesn't support this.
            // Get the file from moodles files table.
            $moodlefile = $DB->get_record('files', array('filename' => $filename, 'component' => 'mod_hvp', 'filearea' => 'content', 'contextid' => $contextid));
            if(!isset($moodlefile) || !$moodlefile) {
                continue;
            }

            // Update the file in moodles filetable.
            $moodlefile->author = isset($fileinfo->copyright->author) ? $fileinfo->copyright->author : '';
            $moodlefile->license = isset($fileinfo->copyright->license) ? $fileinfo->copyright->license : '';
            $DB->update_record('files', $moodlefile);

            // Update the mbslicenseinfo file metadata.
            if($fmeta = $DB->get_record('local_mbslicenseinfo_fmeta', array('files_id' => $moodlefile->id))) {
                $fmeta->title = isset($fileinfo->copyright->title) ? $fileinfo->copyright->title : '';
                $fmeta->source= isset($fileinfo->copyright->source) ? $fileinfo->copyright->source : '';
                $DB->update_record('local_mbslicenseinfo_fmeta', $fmeta);
            } else {
                $fmeta = new \stdClass();
                $fmeta->files_id = $moodlefile->id;
                $fmeta->title = isset($fileinfo->copyright->title) ? $fileinfo->copyright->title : '';
                $fmeta->source= isset($fileinfo->copyright->source) ? $fileinfo->copyright->source : '';
                $DB->insert_record('local_mbslicenseinfo_fmeta', $fmeta);
            }
        }
    }
    
    /**
     * Search for copyright attribute of file objects and return an array containing objects with license infos.
     * 
     * @param object $haystack
     * @return array of file informations objects
     */
    public static function find_hvpfileobject_with_license($haystack) {
        $results = [];
        if (!is_object($haystack) && !is_array($haystack)) {
            return $results;
        }
        
        foreach($haystack as $key => $value) {
            if(isset($value->copyright)) {
                $results[] = $value;
            } else {
                $results = array_merge(self::find_hvpfileobject_with_license($value), $results);
            }
        }
        return $results;
    }

    /**
     * Update mebis license tables with entered data from H5P Plugin
     * 
     * @param obj $data of submitted edit license form data
     */
    public static function update_licenseinfo_from_moodle_to_hvp($data) {
        global $DB;
        
        // get files infos of submitted edit license form
        $fmetas = self::resort_formdata($data);

        // check all course files to be hvp content. If its hvp then update license info.
        foreach ($fmetas as $fmeta) {
            $file = $DB->get_record('files', array('id' => $fmeta->id));
            if(!($file->component == 'mod_hvp' && $file->filearea == 'content')) {
                continue;
            }
            $ctx = \context::instance_by_id($file->contextid);
            $cmid = $ctx->instanceid;
            $hvpcm = $DB->get_record('course_modules', array('id' => $cmid));
            $hvprow = $DB->get_record('hvp', array('course' => $hvpcm->course, 'id' => $hvpcm->instance));
            $hvpstring = $hvprow->json_content;
            $hvpcontent = json_decode($hvpstring);
            $hvpcontent = self::update_hvpfileobject_copyright_attribute($hvpcontent, $fmeta);
            // Anschließend zurückschreiben in die hvp-Tabelle
            $hvprow->filtered = json_encode($hvpcontent);
            $hvprow->json_content = json_encode($hvpcontent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $DB->update_record('hvp', $hvprow);
        }
    }

    /**
     * 
     * 
     * @param object $haystack
     * @param  object $fmeta file data of mebis edit license form
     * @return object updated with new file metadata
     */
    public static function update_hvpfileobject_copyright_attribute($haystack, $fmeta) {
        if (!is_object($haystack) && !is_array($haystack)) {
            return $haystack;
        }
        foreach($haystack as $key => $value) {
            if(isset($value->copyright) && isset($value->path) && (strpos($value->path, $fmeta->filename) !== false)) {
                $value->copyright->title = $fmeta->title;
                $value->copyright->author = $fmeta->author;
                $value->copyright->license = $fmeta->license->shortname;
                $value->copyright->source = $fmeta->source;
            } else {
                $value = self::update_hvpfileobject_copyright_attribute($value, $fmeta);
            }
            if(is_object($haystack)) {
                $haystack->$key = $value;
            } elseif (is_array($haystack)) {
                $haystack[$key] = $value;
            }
        }
        return $haystack;
    }
    
    /**
     * Event Callback of H5P Module Creation.
     * 
     * @param object $event
     */
    public static function hvp_module_created(\core\event\course_module_created $event) {
        self::update_licenseinfo_from_hvp_to_moodle($event->objectid);
    }

    /**
     * Event Callback of H5P Module Update.
     * 
     * @param object $event
     */
    public static function hvp_module_updated(\core\event\course_module_updated $event) {
        self::update_licenseinfo_from_hvp_to_moodle($event->objectid);
        self::delete_previewfile($event);
    }

}
