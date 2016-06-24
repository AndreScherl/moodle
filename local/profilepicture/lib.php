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
 * Code for the profile picture selector
 *
 * @package   local_profilepicture
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');
require_once("HTML/QuickForm/input.php");

MoodleQuickForm::registerElementType('profilepicture', "$CFG->dirroot/local/profilepicture/lib.php", 'MoodleQuickForm_profilepicture');

define('PROFILEPICTURE_BASEDIR', '/local/profilepicture/images/');

class MoodleQuickForm_profilepicture extends HTML_QuickForm_input {
    /** @var string html for help button, if empty then no help will icon will be dispalyed. */
    public $_helpbutton = '';

    /**
     * Constructor
     *
     * @param string $elementName (optional) name of the profilepicture
     * @param string $elementLabel (optional) profilepicture label
     * @param array $attributes (optional) Either a typical HTML attribute string
     *              or an associative array
     */
    public function __construct($elementName=null, $elementLabel=null, $attributes=null) {
        $this->_type = 'profilepicture';
        parent::__construct($elementName, $elementLabel, $attributes);
    }
    
    /*
     * Old syntax of class constructor. Deprecated in PHP7.
     */
    public function MoodleQuickForm_profilepicture($elementName=null, $elementLabel=null, $attributes=null) {
        self::_construct($elementName, $elementLabel, $attributes);
    }

    /**
     * Returns html for help button.
     *
     * @return string html for help button
     */
    function getHelpButton() {
        return $this->_helpbutton;
    }

    /**
     * Returns type of profilepicture element
     *
     * @return string
     */
    function getElementTemplateType() {
        if ($this->_flagFrozen){
            return 'nodisplay';
        } else {
            return 'default';
        }
    }

    function toHtml() {
        global $PAGE, $CFG;

        $elname = $this->_attributes['name'];
        $picelname = $elname.'_picture';
        $button = $elname.'_button';
        $id = $this->_attributes['id'];
        if (!$draftitemid = (int)$this->getValue()) {
            // no existing area info provided - let's use fresh new draft area
            $draftitemid = file_get_unused_draft_itemid();
            $this->setValue($draftitemid);
        }

        $files = local_profilepicture_listfiles();
        $out = '';
        $out .= '<input type="hidden" name="'.$elname.'" id="'.$id.'" value="'.$draftitemid.'" />';
        $out .= '<noscript>';
        $out .= html_writer::select($files, $picelname, '', array('' => ''));
        $out .= '</noscript>';
        $out .= html_writer::tag('button', get_string('selectpicture', 'local_profilepicture'),
                                 array('id' => $button, 'class' => 'local_profilepicture_button',
                                      'onclick' => 'return false;'));

        $thumbnails = '';
        if (empty($files)) {
            $thumbnails = get_string('noimage', 'local_profilepicture', PROFILEPICTURE_BASEDIR);
        } else {
            foreach ($files as $filename => $displayname) {
                if ($filename != clean_param($filename, PARAM_FILE)) {
                    $image = html_writer::tag('div', '<span style="color:red">Invalid filename:</span> '.$filename, array('class' => 'image'));
                } else {
                    $tmb = html_writer::empty_tag('img', array('src' => $CFG->wwwroot.PROFILEPICTURE_BASEDIR.$filename, 'class' => 'thumbnail'));
                    $tmb = html_writer::tag('div', $tmb, array('class' => 'thumbnailwrap'));
                    $input = html_writer::empty_tag('input', array('type' => 'hidden', 'value' => $filename));
                    $image = html_writer::tag('div', $tmb.$input, array('class' => 'image'));
                }

                $thumbnails .= $image;
            }
        }

        $out .= "<div id=\"local_profilepicture\" class=\"local_profilepicture\">
                   <div class=\"yui3-widget-hd\">
                     ".get_string('selectpicture', 'local_profilepicture')."
                   </div>
                   <div class=\"yui3-widget-bd\">
                     $thumbnails
                   </div>
                 </div>";
        $out .= '<input type="hidden" id="'.$picelname.'"/>';

        $jsmodule = array(
            'name' => 'local_profilepicture',
            'fullpath' => new moodle_url('/local/profilepicture/profilepicture.js'),
            'strings' => array(
                array('selectpicture', 'local_profilepicture')
            ),
            'requires' => array('node', 'event', 'panel')
        );
        $opts = array('buttonname' => $button, 'elname' => $picelname, 'imageel' => 'currentpicture');
        $PAGE->requires->js_init_call('M.local_profilepicture.init', array($opts), true, $jsmodule);

        return $out;
    }

    /**
     * export selected picture
     *
     * @param array $submitValues values submitted.
     * @param bool $assoc specifies if returned array is associative
     * @return array
     */
    function exportValue(&$submitValues, $assoc = false) {
        global $USER, $CFG;

        $draftitemid = null;
        $picturename = null;

        if (!empty($submitValues)) {
            if (isset($submitValues[$this->getName()])) {
                $draftitemid = $submitValues[$this->getName()];
            }
            if (isset($submitValues[$this->getName().'_picture'])) {
                $picturename = $submitValues[$this->getName().'_picture'];
            }
        }

        if (null === $draftitemid) {
            $draftitemid = $this->getValue();
        }

        // make sure max one file is present and it is not too big
        if (!is_null($draftitemid) && !empty($picturename)) {
            $picturename = clean_param($picturename, PARAM_FILE);
            $picturepath = $CFG->dirroot.PROFILEPICTURE_BASEDIR.$picturename;
            if (file_exists($picturepath) && !is_dir($picturepath)) {
                $fs = get_file_storage();
                $usercontext = context_user::instance($USER->id);
                $fs->delete_area_files($usercontext->id, 'user', 'draft', $draftitemid); // Delete any existing picture.

                $filerecord = array(
                    'contextid' => $usercontext->id,
                    'component' => 'user',
                    'filearea' => 'draft',
                    'itemid' => $draftitemid,
                    'filepath' => '/',
                    'filename' => $picturename,
                );

                $fs->create_file_from_pathname($filerecord, $picturepath);
            }
        }

        return $this->_prepareValue($draftitemid, true);
    }
}

/**
 * List all images available in the profile picture selection.
 * @return array
 */
function local_profilepicture_listfiles() {
    global $CFG;
    $files = array();
    $path = $CFG->dirroot.PROFILEPICTURE_BASEDIR;
    foreach (scandir($path) as $file) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        if (!file_extension_in_typegroup($file, 'web_image')) {
            continue;
        }
        $imgname = pathinfo($file, PATHINFO_FILENAME);
        $imgname = str_replace('_', ' ', $imgname);
        $imgname = ucfirst($imgname);
        $files[$file] = $imgname;
    }
    return $files;
}

/**
 * Check if the current user is able to use the filepicker to upload a profile picture (instead of the limited
 * selection of images).
 * @return bool
 */
function local_profilepicture_set_any_picture() {
    global $DB, $USER;

    $syscontext = context_system::instance();
    if (has_capability('moodle/user:update', $syscontext)) {
        return true; // Site admin - can always edit any user's picture.
    }

    // Find all the roles that are able to choose any profile picture.
    $roles = get_roles_with_capability('local/profilepicture:anypicture');
    if (empty($roles)) {
        return false; // No roles have ability to choose any profile picture.
    }
    $roleids = array_keys($roles);
    list($rsql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);
    $params['userid'] = $USER->id;

    // See if the current user has one of those roles (anywhere in the site).
    return $DB->record_exists_select('role_assignments', "roleid $rsql AND userid = :userid", $params);
}
