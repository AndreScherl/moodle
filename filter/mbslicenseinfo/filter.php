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
        $text =  preg_replace_callback('/<img[^>].*(pluginfile.php.[^ ]*\").[^<]*>/i', 'self::enhance_media_tag', $text);
        
        // span tag mediaplugin solution (for video and audio after mediaplugin filter)
        $text = preg_replace_callback('/<span[^>].*class=\"mediaplugin[^(<\/span>)](.*[\r\n])*.*(pluginfile.php.[^ ]*\")(.*[\r\n])*.*((audio>|video>|object>){1}[\r\n]*.*span>)/i', 'self::enhance_media_tag', $text);
        
        // audio and video tag solution
        $text = preg_replace_callback('/(<audio|<video)[^>].*[^(<\/audio>|<\/video>)].*(pluginfile.php.[^ ]*\")(.*[\r\n])*.*(audio>|video>)/i', 'self::enhance_media_tag', $text);
        
        return $text;
    }
    
    /*
     * callback for preg_replace_callback() function
     */
    private function enhance_media_tag($match) {
        $fileinfo = self::extract_file_information($match);
        $licenseinfo = self::build_license_div($fileinfo);
        $enhancedmediatag = html_writer::div($match[0].$licenseinfo, 'mediaandlicense');
                
        return $enhancedmediatag;
    }
    
    /*
     * Get the file path information out of the right match in the match array
     * 
     * @param array $matcharray - the matches as delivered by preg_replace_callback()
     * @return object with processed path informations of the file
     */
    private function extract_file_information($matcharray) {
        $fileinfo = new \stdClass();
        for($i=1; $i<count($matcharray); $i++) {
            if(strpos($matcharray[$i], 'pluginfile.php') === 0) {
                $path = str_replace("\"", "", $matcharray[$i]);
                $pathparts = preg_split('/\//', $path);
                $fileinfo->contextid = $pathparts[1];
                $fileinfo->component = $pathparts[2];
                $fileinfo->filearea = $pathparts[3];
                $fileinfo->filename = $pathparts[count($pathparts)-1];
                break;
            }
        }
        return $fileinfo;
    }
    
    /*
     * Get all relevant metadata of the file to display and build the license div tag
     * 
     * @global $DB
     * @param object $fileinfo - fileinfo object with path sorted informations
     * @return string - license info div tag
     */
    private function build_license_div($fileinfo) {
        if (empty($fileinfo)) {
            return false;
        }
        
        global $DB;
        // get the id of right file out of db files table
        $fileid = $DB->get_field('files', 'id', array(
            'contextid' => $fileinfo->contextid, 
            'component' => $fileinfo->component, 
            'filearea' => $fileinfo->filearea, 
            'filename' => $fileinfo->filename));
        
        // create mbsfile instance to grap file infos easily
        $file = new \block_mbslicenseinfo\local\mbsfile($fileid);
                
        // build the markup
        $licenselink = '';
        if (!empty($file->license)) {
            $licenselink = html_writer::link($file->license->source, $file->license->fullname, array('target' => '_blank'));
        }
        if (empty($file->title)) {
            $filelink = $file->filename;
        } else {
            $filelink = html_writer::link($file->source, $file->title, array('target' => '_blank'));
        }
        $author = (empty($file->author)) ? '' : $file->author . ', '; 
        $licenseinfo = html_writer::div(get_string('source', 'filter_mbslicenseinfo').': '.$filelink.', '.$author.$licenselink, 'licenseinfo');
        
        return $licenseinfo;
    }
}