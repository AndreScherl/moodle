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
 * Brings license informations direct to media
 *
 * @package   filter_mbslicenseinfo
 * @copyright 2015 ISB Bayern
 * @author    Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

class filter_mbslicenseinfo extends moodle_text_filter {
    public function filter($text, array $options = array()) {  
        // img tag solution
        $text =  preg_replace_callback('/<img[^>].*(pluginfile.php.[^ ]*\").[^<]*>/i', 'self::enhance_img_tag', $text);
        
        return $text;
    }
    
    private function enhance_img_tag($match) {
        global $DB;
        // Extract the file info out of the matchs file path.
        // Note! The path search is the first (and only) grouped subpattern in img tag regular expression.
        // If you want do add some more subpatterns, be sure to also change the index of $match array!
        //print_r($match);
        $path = str_replace("\"", "", $match[1]);
        $pathparts = preg_split('/\//', $path);
        $contextid = $pathparts[1];
        $component = $pathparts[2];
        $filearea = $pathparts[3];
        $filename = $pathparts[count($pathparts)-1];
        
        // get the right file row out of db files table and its included license infos
        $file = $DB->get_record('files', array(
            'contextid' => $contextid, 
            'component' => $component, 
            'filearea' => $filearea, 
            'filename' => $filename));
        
        // get the license info of the file
        $license = $DB->get_record('license', array('shortname' => $file->license));
        
        // build the markup (container div, img, license info)
        $licenselink = html_writer::link($license->source, $license->fullname, array('target' => '_blank'));
        $author = (empty($file->author)) ? '' : $file->author . ', '; 
        $licenseinfo = html_writer::div($author.$licenselink, 'licenseinfo');
        $enhancedimage = html_writer::div($match[0].$licenseinfo, 'imageandlicense');
        
        return $enhancedimage;
    }
}