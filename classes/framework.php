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
 * \mod_hvp\framework class
 *
 * @package    mod_hvp
 * @copyright  2016 Joubel AS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_hvp;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../autoloader.php';
require_once($CFG->libdir . '/filelib.php');

/**
 * Moodle's implementation of the H5P framework interface.
 *
 * @package    mod_hvp
 * @copyright  2016 Joubel AS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class framework implements \H5PFrameworkInterface {

    /**
     * Get type of hvp instance
     *
     * @param string $type Type of hvp instance to get
     * @return \H5PContentValidator|\H5PCore|\H5PMoodle|\H5PStorage|\H5PValidator
     */
    public static function instance($type = null) {
        global $CFG;
        static $interface, $core, $editor, $editorinterface;

        if (!isset($interface)) {
            $interface = new \mod_hvp\framework();

            $fs = new \mod_hvp\file_storage();

            $context = \context_system::instance();
            $url = "{$CFG->sessioncookiepath}pluginfile.php/{$context->id}/mod_hvp";

            $language = \current_language();

            $export = !(isset($CFG->mod_hvp_export) && $CFG->mod_hvp_export === '0');

            $core = new \H5PCore($interface, $fs, $url, $language, $export);
            $core->aggregateAssets = !(isset($CFG->mod_hvp_aggregate_assets) && $CFG->mod_hvp_aggregate_assets === '0');
        }

        switch ($type) {
            case 'validator':
                return new \H5PValidator($interface, $core);
            case 'storage':
                return new \H5PStorage($interface, $core);
            case 'contentvalidator':
                return new \H5PContentValidator($interface, $core);
            case 'interface':
                return $interface;
            case 'editor':
                if (empty($editorinterface)) {
                    $editorinterface = new \mod_hvp\editor_framework();
                }
                if (empty($editor)) {
                    $editor = new \H5peditor($core, $editorinterface);
                }
                return $editor;
            case 'core':
            default:
                return $core;
        }
    }

    /**
     * Implements getPlatformInfo
     */
    public function getPlatformInfo() {
        global $CFG;

        return array(
            'name' => 'Moodle',
            'version' => $CFG->version,
            'h5pVersion' => get_component_version('mod_hvp'),
        );
    }

    /**
     * Implements fetchExternalData
     *
     * @param string $url Url starting with http(s)://
     * @return bool|null|\stdClass|string Data object if successful fetch
     */
    public function fetchExternalData($url, $data = null) {
        $response = download_file_content($url, null, $data);
        return ($response === false ? null : $response);
    }

    /**
     * Implements setLibraryTutorialUrl
     *
     * Set the tutorial URL for a library. All versions of the library is set
     *
     * @param string $library_name
     * @param string $url
     */
    public function setLibraryTutorialUrl($library_name, $url) {
        global $DB;

        $DB->execute("UPDATE {hvp_libraries} SET tutorial_url = ? WHERE machine_name = ?", array($url, $library_name));
    }

    /**
     * Implements setErrorMessage
     *
     * @param string $message translated error message
     */
    public function setErrorMessage($message) {
        if ($message !== null) {
            self::messages('error', $message);
        }
    }

    /**
     * Implements setInfoMessage
     */
    public function setInfoMessage($message) {
        if ($message !== null) {
            self::messages('info', $message);
        }
    }

    /**
     * Store messages until they can be printed to the current user
     *
     * @param string $type Type of messages, e.g. 'info' or 'error'
     * @param string $newMessage Optional
     * @return array Array of stored messages
     */
    public static function messages($type, $newMessage = null) {
        static $m = 'mod_hvp_messages';

        if ($newMessage === null) {
            // Return and reset messages
            $messages = isset($_SESSION[$m][$type]) ? $_SESSION[$m][$type] : array();
            unset($_SESSION[$m][$type]);
            if (empty($_SESSION[$m])) {
                unset($_SESSION[$m]);
            }
            return $messages;
        }

        $_SESSION[$m][$type][] = $newMessage;
    }

    /**
     * Simple print of given messages.
     *
     * @param string $type One of error|info
     * @param array $messages
     */
    public static function printMessages($type, $messages) {
        global $OUTPUT;
        foreach ($messages as $message) {
            print $OUTPUT->notification($message, ($type === 'error' ? 'notifyproblem' : 'notifymessage'));
        }
    }

    /**
     * Implements t
     */
    public function t($message, $replacements = array()) {
        static $map;

        if (empty($map)) {
            // Create mapping
            $map = [
                'Your PHP version does not support ZipArchive.' => get_string('noziparchive', 'hvp'),
                'The file you uploaded is not a valid HTML5 Package (It does not have the .h5p file extension)' => get_string('noextension', 'hvp'),
                'The file you uploaded is not a valid HTML5 Package (We are unable to unzip it)' => get_string('nounzip', 'hvp'),
                'Could not parse the main h5p.json file' => get_string('noparse', 'hvp'),
                'The main h5p.json file is not valid' => get_string('nojson', 'hvp'),
                'Invalid content folder' => get_string('invalidcontentfolder', 'hvp'),
                'Could not find or parse the content.json file' => get_string('nocontent', 'hvp'),
                'Library directory name must match machineName or machineName-majorVersion.minorVersion (from library.json). (Directory: %directoryName , machineName: %machineName, majorVersion: %majorVersion, minorVersion: %minorVersion)' => get_string('librarydirectoryerror', 'hvp', $replacements),
                'A valid content folder is missing' => get_string('missingcontentfolder', 'hvp'),
                'A valid main h5p.json file is missing' => get_string('invalidmainjson', 'hvp'),
                'Missing required library @library' => get_string('missinglibrary', 'hvp', $replacements),
                "Note that the libraries may exist in the file you uploaded, but you're not allowed to upload new libraries. Contact the site administrator about this." => get_string('missinguploadpermissions', 'hvp'),
                'Invalid library name: %name' => get_string('invalidlibraryname', 'hvp', $replacements),
                'Could not find library.json file with valid json format for library %name' => get_string('missinglibraryjson', 'hvp', $replacements),
                'Invalid semantics.json file has been included in the library %name' => get_string('invalidsemanticsjson', 'hvp', $replacements),
                'Invalid language file %file in library %library' => get_string('invalidlanguagefile', 'hvp', $replacements),
                'Invalid language file %languageFile has been included in the library %name' => get_string('invalidlanguagefile2', 'hvp', $replacements),
                'The file "%file" is missing from library: "%name"' => get_string('missinglibraryfile', 'hvp', $replacements),
                'The library "%libraryName" requires H5P %requiredVersion, but only H5P %coreApi is installed.' => get_string('missingcoreversion', 'hvp', $replacements),
                "Invalid data provided for %property in %library. Boolean expected." => get_string('invalidlibrarydataboolean', 'hvp', $replacements),
                "Invalid data provided for %property in %library" => get_string('invalidlibrarydata', 'hvp', $replacements),
                "Can't read the property %property in %library" => get_string('invalidlibraryproperty', 'hvp', $replacements),
                'The required property %property is missing from %library' => get_string('missinglibraryproperty', 'hvp', $replacements),
                'Illegal option %option in %library' => get_string('invalidlibraryoption', 'hvp', $replacements),
                'Added %new new H5P libraries and updated %old old.' => get_string('addedandupdatelibraries', 'hvp', $replacements),
                'Added %new new H5P libraries.' => get_string('addednewlibraries', 'hvp', $replacements),
                'Updated %old H5P libraries.' => get_string('updatedlibraries', 'hvp', $replacements),
                'Missing dependency @dep required by @lib.' => get_string('missingdependency', 'hvp', $replacements),
                'Provided string is not valid according to regexp in semantics. (value: \"%value\", regexp: \"%regexp\")' => get_string('invalidstring', 'hvp', $replacements),
                'File "%filename" not allowed. Only files with the following extensions are allowed: %files-allowed.' => get_string('invalidfile', 'hvp', $replacements),
                'Invalid selected option in multi-select.' => get_string('invalidmultiselectoption', 'hvp'),
                'Invalid selected option in select.' => get_string('invalidselectoption', 'hvp'),
                'H5P internal error: unknown content type "@type" in semantics. Removing content!' => get_string('invalidsemanticstype', 'hvp', $replacements),
                'Library used in content is not a valid library according to semantics' => get_string('invalidsemantics', 'hvp'),
                'Copyright information' => get_string('copyrightinfo', 'hvp'),
                'Title' => get_string('title', 'hvp'),
                'Author' => get_string('author', 'hvp'),
                'Year(s)' => get_string('years', 'hvp'),
                'Source' => get_string('source', 'hvp'),
                'License' => get_string('license', 'hvp'),
                'Undisclosed' => get_string('undisclosed', 'hvp'),
                'Attribution 4.0' => get_string('attribution', 'hvp'),
                'Attribution-ShareAlike 4.0' => get_string('attributionsa', 'hvp'),
                'Attribution-NoDerivs 4.0' => get_string('attributionnd', 'hvp'),
                'Attribution-NonCommercial 4.0' => get_string('attributionnc', 'hvp'),
                'Attribution-NonCommercial-ShareAlike 4.0' => get_string('attributionncsa', 'hvp'),
                'Attribution-NonCommercial-NoDerivs 4.0' => get_string('attributionncnd', 'hvp'),
                'General Public License v3' => get_string('gpl', 'hvp'),
                'Public Domain' => get_string('pd', 'hvp'),
                'Public Domain Dedication and Licence' => get_string('pddl', 'hvp'),
                'Public Domain Mark' => get_string('pdm', 'hvp'),
                'Copyright' => get_string('copyrightstring', 'hvp'),
                'Unable to create directory.' => get_string('unabletocreatedir', 'hvp'),
                'Unable to get field type.' => get_string('unabletogetfieldtype', 'hvp'),
                "File type isn't allowed." => get_string('filetypenotallowed', 'hvp'),
                'Invalid field type.' => get_string('invalidfieldtype', 'hvp'),
                'Invalid image file format. Use jpg, png or gif.' => get_string('invalidimageformat', 'hvp'),
                'File is not an image.' => get_string('filenotimage', 'hvp'),
                'Invalid audio file format. Use mp3 or wav.' => get_string('invalidaudioformat', 'hvp'),
                'Invalid video file format. Use mp4 or webm.' => get_string('invalidvideoformat', 'hvp'),
                'Could not save file.' => get_string('couldnotsave', 'hvp'),
                'Could not copy file.' => get_string('couldnotcopy', 'hvp')
            ];
        }

        return str_replace(array_keys($replacements), $replacements, $map[$message]);
    }

    /**
     * Implements getH5PPath
     */
    public function getH5pPath() {
        global $CFG;

        return $CFG->dirroot . '/mod/hvp/files';
    }

    /**
     * Implements getUploadedH5PFolderPath
     */
    public function getUploadedH5pFolderPath($setPath = null) {
        static $path;

        if ($setPath !== null) {
            $path = $setPath;
        }

        if (!isset($path)) {
            throw new \coding_exception('Using getUploadedH5pFolderPath() before path is set');
        }

        return $path;
    }

    /**
     * Implements getUploadedH5PPath
     */
    public function getUploadedH5pPath($setPath = null) {
        static $path;

        if ($setPath !== null) {
            $path = $setPath;
        }

        if (!isset($path)) {
            throw new \coding_exception('Using getUploadedH5pPath() before path is set');
        }

        return $path;
    }

    /**
     * Implements loadLibraries
     */
    public function loadLibraries() {
        global $DB;

        $results = $DB->get_records_sql(
              "SELECT id, machine_name, title, major_version, minor_version,
                      patch_version, runnable, restricted
                 FROM {hvp_libraries}
             ORDER BY title ASC, major_version ASC, minor_version ASC");

        $libraries = array();
        foreach ($results as $library) {
            $libraries[$library->machine_name][] = $library;
        }

        return $libraries;
    }

    /**
     * Implements setUnsupportedLibraries.
     */
    public function setUnsupportedLibraries($libraries) {
        // Not supported
    }

    /**
     * Implements getUnsupportedLibraries.
     */
    public function getUnsupportedLibraries() {
        // Not supported
    }

    /**
     * Implements getAdminUrl.
     */
    public function getAdminUrl() {
        // Not supported
    }

    /**
     * Implements getLibraryId
     */
    public function getLibraryId($machineName, $majorVersion = null, $minorVersion = null) {
        global $DB;

        // Look for specific library
        $sql_where = 'WHERE machine_name = ?';
        $sql_args = array($machineName);

        if ($majorVersion !== null) {
            // Look for major version
            $sql_where .= ' AND major_version = ?';
            $sql_args[] = $majorVersion;
            if ($minorVersion !== null) {
                // Look for minor version
                $sql_where .= ' AND minor_version = ?';
                $sql_args[] = $minorVersion;
            }
        }

        // Get the lastest version which matches the input parameters
        $libraries = $DB->get_records_sql("
                SELECT id
                  FROM {hvp_libraries}
          {$sql_where}
              ORDER BY major_version DESC,
                       minor_version DESC,
                       patch_version DESC
                ", $sql_args, 0, 1);
        if ($libraries) {
            $library = reset($libraries);
            return $library ? $library->id : false;
        }
        else {
            return false;
        }
    }

    /**
     * Implements isPatchedLibrary
     */
    public function isPatchedLibrary($library) {
        global $DB, $CFG;

        if (isset($CFG->mod_hvp_dev) && $CFG->mod_hvp_dev) {
            // Makes sure libraries are updated, patch version does not matter.
            return true;
        }

        $operator = $this->isInDevMode() ? '<=' : '<';
        $library = $DB->get_record_sql(
                'SELECT id
                  FROM {hvp_libraries}
                    WHERE machine_name = ?
                    AND major_version = ?
                    AND minor_version = ?
                    AND patch_version ' . $operator . ' ?',
                  array($library['machineName'],
                  $library['majorVersion'],
                  $library['minorVersion'],
                  $library['patchVersion'])
        );

        return $library ? true : false;
    }

    /**
     * Implements isInDevMode
     */
    public function isInDevMode() {
        return false; // Not supported (Files in moodle not editable)
    }

    /**
     * Implements mayUpdateLibraries
     */
    public function mayUpdateLibraries() {
        // Check permissions
        $context = \context_system::instance();
        if (!has_capability('mod/hvp:updatelibraries', $context)) {
            return false;
        }

        return true;
    }

    /**
     * Implements getLibraryUsage
     *
     * Get number of content/nodes using a library, and the number of
     * dependencies to other libraries
     *
     * @param int $id
     * @param boolean $skipContent Optional. Set as TRUE to get number of content instances for library.
     * @return array The array contains two elements, keyed by 'content' and 'libraries'.
     *               Each element contains a number
     */
    public function getLibraryUsage($id, $skipContent = false) {
        global $DB;

        if ($skipContent) {
            $content = -1;
        }
        else {
            $content = intval($DB->get_field_sql(
                "SELECT COUNT(distinct c.id)
                FROM {hvp_libraries} l
                JOIN {hvp_contents_libraries} cl ON l.id = cl.library_id
                JOIN {hvp} c ON cl.hvp_id = c.id
                WHERE l.id = ?", array($id)
            ));
        }

        $libraries = intval($DB->get_field_sql(
            "SELECT COUNT(*)
            FROM {hvp_libraries_libraries}
            WHERE required_library_id = ?", array($id)
        ));

        return array(
            'content' => $content,
            'libraries' => $libraries,
        );
    }

    /**
     * Implements getLibraryContentCount
     */
    public function getLibraryContentCount() {
        global $DB;
        $contentCount = array();

        // Count content using the same content type
        $res = $DB->get_records_sql(
          "SELECT c.main_library_id,
                  l.machine_name,
                  l.major_version,
                  l.minor_version,
                  c.count
             FROM (SELECT main_library_id,
                          count(id) as count
                     FROM {hvp}
                 GROUP BY main_library_id) c,
                 {hvp_libraries} l
            WHERE c.main_library_id = l.id"
        );

        // Extract results
        foreach($res as $lib) {
            $contentCount["{$lib->machine_name} {$lib->major_version}.{$lib->minor_version}"] = $lib->count;
        }

        return $contentCount;
    }

    /**
     * Implements saveLibraryData
     */
    public function saveLibraryData(&$libraryData, $new = true) {
        global $DB;

        // Some special properties needs some checking and converting before they can be saved.
        $preloadedJs = $this->pathsToCsv($libraryData, 'preloadedJs');
        $preloadedCss = $this->pathsToCsv($libraryData, 'preloadedCss');
        $dropLibraryCss = '';

        if (isset($libraryData['dropLibraryCss'])) {
            $libs = array();
            foreach ($libraryData['dropLibraryCss'] as $lib) {
                $libs[] = $lib['machineName'];
            }
            $dropLibraryCss = implode(', ', $libs);
        }

        $embedTypes = '';
        if (isset($libraryData['embedTypes'])) {
            $embedTypes = implode(', ', $libraryData['embedTypes']);
        }
        if (!isset($libraryData['semantics'])) {
            $libraryData['semantics'] = '';
        }
        if (!isset($libraryData['fullscreen'])) {
            $libraryData['fullscreen'] = 0;
        }
        // TODO: Can we move the above code to H5PCore? It's the same for multiple
        // implementations. Perhaps core can update the data objects before calling
        // this function?
        // I think maybe it's best to do this when classes are created for
        // library, content, etc.

        $library = (object) array(
            'title' => $libraryData['title'],
            'machine_name' => $libraryData['machineName'],
            'major_version' => $libraryData['majorVersion'],
            'minor_version' => $libraryData['minorVersion'],
            'patch_version' => $libraryData['patchVersion'],
            'runnable' => $libraryData['runnable'],
            'fullscreen' => $libraryData['fullscreen'],
            'embed_types' => $embedTypes,
            'preloaded_js' => $preloadedJs,
            'preloaded_css' => $preloadedCss,
            'drop_library_css' => $dropLibraryCss,
            'semantics' => $libraryData['semantics'],
        );

        if ($new) {
            // Create new library and keep track of id
            $library->id = $DB->insert_record('hvp_libraries', $library);
            $libraryData['libraryId'] = $library->id;
        }
        else {
            // Update library data
            $library->id = $libraryData['libraryId'];

            // Save library data
            $DB->update_record('hvp_libraries', (object) $library);

            // Remove old dependencies
            $this->deleteLibraryDependencies($libraryData['libraryId']);
        }

        // Log library successfully installed/upgraded
        new \mod_hvp\event(
              'library', ($new ? 'create' : 'update'),
              NULL, NULL,
              $library->machine_name, $library->major_version . '.' . $library->minor_version
        );

        // Update library translations
        $DB->delete_records('hvp_libraries_languages', array('library_id' => $libraryData['libraryId']));
        if (isset($libraryData['language'])) {
            foreach ($libraryData['language'] as $languageCode => $languageJson) {
                $DB->insert_record('hvp_libraries_languages', array(
                    'library_id' => $libraryData['libraryId'],
                    'language_code' => $languageCode,
                    'language_json' => $languageJson,
                ));
            }
        }
    }

    /**
     * Convert list of file paths to csv
     *
     * @param array $libraryData
     *  Library data as found in library.json files
     * @param string $key
     *  Key that should be found in $libraryData
     * @return string
     *  file paths separated by ', '
     */
    private function pathsToCsv($libraryData, $key) {
        // TODO: Move to core?
        if (isset($libraryData[$key])) {
            $paths = array();
            foreach ($libraryData[$key] as $file) {
                $paths[] = $file['path'];
            }
            return implode(', ', $paths);
        }
        return '';
    }

    /**
     * Implements lockDependencyStorage
     */
    public function lockDependencyStorage() {
        // Library development mode not supported
    }

    /**
     * Implements unlockDependencyStorage
     */
    public function unlockDependencyStorage() {
        // Library development mode not supported
    }

    /**
     * Implements deleteLibrary
     */
    public function deleteLibrary($library) {
        global $DB;

        // Delete library files
        \H5PCore::deleteFileTree($this->getH5pPath() . '/libraries/' . $library->name . '-' . $library->major_version . '.' . $library->minor_version);

        // Remove library data from database
        $DB->delete('hvp_libraries_libraries', array('library_id' => $library->id));
        $DB->delete('hvp_libraries_languages', array('library_id' => $library->id));
        $DB->delete('hvp_libraries', array('id' => $library->id));
    }

    /**
     * Implements saveLibraryDependencies
     */
    public function saveLibraryDependencies($libraryId, $dependencies, $dependency_type) {
        global $DB;

        foreach ($dependencies as $dependency) {
            // Find dependency library.
            $dependencyLibrary = $DB->get_record('hvp_libraries', array(
                'machine_name' => $dependency['machineName'],
                'major_version' => $dependency['majorVersion'],
                'minor_version' => $dependency['minorVersion']
            ));

            // Create relation.
            $DB->insert_record('hvp_libraries_libraries', array(
                'library_id' => $libraryId,
                'required_library_id' => $dependencyLibrary->id,
                'dependency_type' => $dependency_type
            ));
        }
    }

    /**
     * Implements updateContent
     *
     * Inserts or updates H5P content.
     *
     * @param array $content
     *   An associative array containing:
     *   - id: The content id
     *   - params: The content in json format
     *   - library: An associative array containing:
     *     - libraryId: The id of the main library for this content
     * @param int $contentMainId
     *   Main id for the content if this is a system that supports versioning
     *
     * @return bool|int
     */
    public function updateContent($content, $contentMainId = null) {
        global $DB;

        if (!isset($content['disable'])) {
            $content['disable'] = \H5PCore::DISABLE_NONE;
        }

        $data = array(
            'name' => $content['name'],
            'course' => $content['course'],
            'intro' => $content['intro'],
            'introformat' => $content['introformat'],
            'json_content' => $content['params'],
            'embed_type' => 'div',
            'main_library_id' => $content['library']['libraryId'],
            'filtered' => '',
            'disable' => $content['disable'],
            'timemodified' => time()
        );

        if (!isset($content['id'])) {
            $data['slug'] = '';
            $data['timecreated'] = $data['timemodified'];
            $event_type = 'create';
            $id = $DB->insert_record('hvp', $data);
        }
        else {
            $data['id'] = $content['id'];
            $DB->update_record('hvp', $data);
            $event_type = 'update';
            $id = $data['id'];
        }

        // Log content create/update/upload
        if (!empty($content['uploaded'])) {
            $event_type .= ' upload';
        }
        new \mod_hvp\event(
                'content', $event_type,
                $id, $content['name'],
                $content['library']['machineName'],
                $content['library']['majorVersion'] . '.' . $content['library']['minorVersion']
        );

        return $id;
    }

    /**
     * Implements insertContent
     */
    public function insertContent($content, $contentMainId = null) {
        return $this->updateContent($content);
    }

    /**
     * Implements resetContentUserData
     */
    public function resetContentUserData($contentId) {
        global $DB;

        // Reset user data for this content
        $DB->execute("UPDATE {hvp_content_user_data}
                         SET data = 'RESET'
                       WHERE hvp_id = ?
                         AND delete_on_content_change = 1",
                     array($contentId));
    }

    /**
     * Implements getWhitelist
     */
    public function getWhitelist($isLibrary, $defaultContentWhitelist, $defaultLibraryWhitelist) {
        return $defaultContentWhitelist . ($isLibrary ? ' ' . $defaultLibraryWhitelist : '');
    }

    /**
     * Implements copyLibraryUsage
     */
    public function copyLibraryUsage($contentId, $copyFromId, $contentMainId = null) {
        global $DB;

        $libraryUsage = $DB->get_record('hvp_contents_libraries', array(
            'id' => $copyFromId
        ));

        $libraryUsage->id = $contentId;
        $DB->insert_record_raw('hvp_contents_libraries', (array)$libraryUsage, false, false, true);

        // TODO: This must be verified at a later time.
        // Currently in Moodle copyLibraryUsage() will never be called.
    }

    /**
     * Implements loadLibrarySemantics
     */
    public function loadLibrarySemantics($name, $majorVersion, $minorVersion) {
        global $DB;

        $semantics = $DB->get_field_sql(
            "SELECT semantics
            FROM {hvp_libraries}
            WHERE machine_name = ?
            AND major_version = ?
            AND minor_version = ?",
            array($name, $majorVersion, $minorVersion));

        return ($semantics === false ? null : $semantics);
    }

    /**
     * Implements alterLibrarySemantics
     */
    public function alterLibrarySemantics(&$semantics, $name, $majorVersion, $minorVersion) {
        // TODO: Implement some way to alter semantics
    }

    /**
     * Implements loadContent
     */
    public function loadContent($id) {
        global $DB;

        $data = $DB->get_record_sql(
                "SELECT hc.id
                      , hc.name
                      , hc.intro
                      , hc.introformat
                      , hc.json_content
                      , hc.filtered
                      , hc.slug
                      , hc.embed_type
                      , hc.disable
                      , hl.id AS library_id
                      , hl.machine_name
                      , hl.major_version
                      , hl.minor_version
                      , hl.embed_types
                      , hl.fullscreen
                FROM {hvp} hc
                JOIN {hvp_libraries} hl ON hl.id = hc.main_library_id
                WHERE hc.id = ?", array($id));

        // Return NULL if not found
        if ($data === false) {
            return null;
        }

        // Some databases do not support camelCase, so we need to manually
        // map the values to the camelCase names used by the H5P core.
        $content = array(
            'id' => $data->id,
            'title' => $data->name,
            'intro' => $data->intro,
            'introformat' => $data->introformat,
            'params' => $data->json_content,
            'filtered' => $data->filtered,
            'slug' => $data->slug,
            'embedType' => $data->embed_type,
            'disable' => $data->disable,
            'libraryId' => $data->library_id,
            'libraryName' => $data->machine_name,
            'libraryMajorVersion' => $data->major_version,
            'libraryMinorVersion' => $data->minor_version,
            'libraryEmbedTypes' => $data->embed_types,
            'libraryFullscreen' => $data->fullscreen,
        );

        return $content;
    }

    /**
     * Implements loadContentDependencies
     */
    public function loadContentDependencies($id, $type = null) {
        global $DB;

        $query = "SELECT hcl.id AS unidepid
                       , hl.id
                       , hl.machine_name
                       , hl.major_version
                       , hl.minor_version
                       , hl.patch_version
                       , hl.preloaded_css
                       , hl.preloaded_js
                       , hcl.drop_css
                       , hcl.dependency_type
                   FROM {hvp_contents_libraries} hcl
                   JOIN {hvp_libraries} hl ON hcl.library_id = hl.id
                  WHERE hcl.hvp_id = ?";
        $queryArgs = array($id);

        if ($type !== null) {
            $query .= " AND hcl.dependency_type = ?";
            $queryArgs[] = $type;
        }

        $query .= " ORDER BY hcl.weight";
        $data = $DB->get_records_sql($query, $queryArgs);

        $dependencies = array();
        foreach ($data as $dependency) {
            unset($dependency->unidepid);
            $dependencies[] = \H5PCore::snakeToCamel($dependency);
        }

        return $dependencies;
    }

    /**
     * Implements getOption().
     */
    public function getOption($name, $default = false) {
        $value = get_config('mod_hvp', $name);
        if ($value === false) {
            return $default;
        }
        return $value;
    }

    /**
     * Implements setOption().
     */
    public function setOption($name, $value) {
        set_config($name, $value, 'mod_hvp');
    }

    /**
     * Implements updateContentFields().
     */
    public function updateContentFields($id, $fields) {
        global $DB;

        $content = new \stdClass();
        $content->id = $id;

        foreach ($fields as $name => $value) {
            $content->$name = $value;
        }

        $DB->update_record('hvp', $content);
    }

    /**
     * Implements deleteLibraryDependencies
     */
    public function deleteLibraryDependencies($libraryId) {
        global $DB;

        $DB->delete_records('hvp_libraries_libraries', array('library_id' => $libraryId));
    }

    /**
     * Implements deleteContentData
     */
    public function deleteContentData($contentId) {
        global $DB;

        // Remove content
        $DB->delete_records('hvp', array('id' => $contentId));

        // Remove content library dependencies
        $this->deleteLibraryUsage($contentId);

        // Remove user data for content
        $DB->delete_records('hvp_content_user_data', array('hvp_id' => $contentId));
    }

    /**
     * Implements deleteLibraryUsage
     */
    public function deleteLibraryUsage($contentId) {
        global $DB;

        $DB->delete_records('hvp_contents_libraries', array('hvp_id' => $contentId));
    }

    /**
     * Implements saveLibraryUsage
     */
    public function saveLibraryUsage($contentId, $librariesInUse) {
        global $DB;

        $dropLibraryCssList = array();
        foreach ($librariesInUse as $dependency) {
            if (!empty($dependency['library']['dropLibraryCss'])) {
                $dropLibraryCssList = array_merge($dropLibraryCssList, explode(', ', $dependency['library']['dropLibraryCss']));
            }
        }
        // TODO: Consider moving the above code to core. Same for all impl.

        foreach ($librariesInUse as $dependency) {
            $dropCss = in_array($dependency['library']['machineName'], $dropLibraryCssList) ? 1 : 0;
            $DB->insert_record('hvp_contents_libraries', array(
                'hvp_id' => $contentId,
                'library_id' => $dependency['library']['libraryId'],
                'dependency_type' => $dependency['type'],
                'drop_css' => $dropCss,
                'weight' => $dependency['weight']
            ));
        }
    }

    /**
     * Implements loadLibrary
     */
    public function loadLibrary($machineName, $majorVersion, $minorVersion) {
        global $DB;

        $library = $DB->get_record('hvp_libraries', array(
            'machine_name' => $machineName,
            'major_version' => $majorVersion,
            'minor_version' => $minorVersion
        ));

        $libraryData = array(
            'libraryId' => $library->id,
            'machineName' => $library->machine_name,
            'title' => $library->title,
            'majorVersion' => $library->major_version,
            'minorVersion' => $library->minor_version,
            'patchVersion' => $library->patch_version,
            'embedTypes' => $library->embed_types,
            'preloadedJs' => $library->preloaded_js,
            'preloadedCss' => $library->preloaded_css,
            'dropLibraryCss' => $library->drop_library_css,
            'fullscreen' => $library->fullscreen,
            'runnable' => $library->runnable,
            'semantics' => $library->semantics,
            'restricted' => $library->restricted
        );

        $dependencies = $DB->get_records_sql(
                'SELECT hl.id, hl.machine_name, hl.major_version, hl.minor_version, hll.dependency_type
                   FROM {hvp_libraries_libraries} hll
                   JOIN {hvp_libraries} hl ON hll.required_library_id = hl.id
                  WHERE hll.library_id = ?', array($library->id));
        foreach ($dependencies as $dependency) {
            $libraryData[$dependency->dependency_type . 'Dependencies'][] = array(
                'machineName' => $dependency->machine_name,
                'majorVersion' => $dependency->major_version,
                'minorVersion' => $dependency->minor_version
            );
        }

        return $libraryData;
    }

    /**
     * Implements clearFilteredParameters().
     */
    public function clearFilteredParameters($library_id) {
        global $DB;

        $DB->execute("UPDATE {hvp} SET filtered = NULL WHERE main_library_id = ?", array($library_id));
    }

    /**
     * Implements getNumNotFiltered().
     */
    public function getNumNotFiltered() {
        global $DB;

        return (int) $DB->get_field_sql(
                "SELECT COUNT(id)
                   FROM {hvp}
                  WHERE filtered = ''");
    }

    /**
     * Implements getNumContent().
     */
    public function getNumContent($library_id) {
        global $DB;

        return (int) $DB->get_field_sql(
                "SELECT COUNT(id) FROM {hvp} WHERE main_library_id = ?",
                array($library_id));
    }

    /**
     * Implements isContentSlugAvailable
     */
    public function isContentSlugAvailable($slug) {
        global $DB;

        return !$DB->get_field_sql("SELECT slug FROM {hvp} WHERE slug = ?", array($slug));
    }

    /**
     * Implements saveCachedAssets
     */
    public function saveCachedAssets($key, $libraries) {
        global $DB;

        foreach ($libraries as $library) {
            $cachedAsset = (object) array(
                'library_id' => $library['id'],
                'hash' => $key
            );
            $DB->insert_record('hvp_libraries_cachedassets', $cachedAsset);
        }
    }

    /**
     * Implements deleteCachedAssets
     */
    public function deleteCachedAssets($library_id) {
        global $DB;

        // Get all the keys so we can remove the files
        $results = $DB->get_records_sql(
                'SELECT hash
                   FROM {hvp_libraries_cachedassets}
                  WHERE library_id = ?',
                array($library_id));

        // Remove all invalid keys
        $hashes = array();
        foreach ($results as $key) {
            $hashes[] = $key->hash;
            $DB->delete_records('hvp_libraries_cachedassets', array('hash' => $key->hash));
        }

        return $hashes;
    }

    /**
     * Implements getLibraryStats
     */
    public function getLibraryStats($type) {
        global $DB;
        $count = array();

        // Get the counts for the given type of event
        $records = $DB->get_records_sql(
                "SELECT library_name AS name,
                        library_version AS version,
                        num
                   FROM {hvp_counters}
                  WHERE type = ?",
                array($type));

        // Extract num from records
        foreach($records as $library) {
            $count[$library->name . ' ' . $library->version] = $library->num;
        }

        return $count;
    }

    /**
     * Implements getNumAuthors
     */
    public function getNumAuthors() {
        global $DB;

        // Get number of unique courses using H5P
        return intval($DB->get_field_sql(
                "SELECT COUNT(DISTINCT course)
                   FROM {hvp}"
        ));
    }

}
