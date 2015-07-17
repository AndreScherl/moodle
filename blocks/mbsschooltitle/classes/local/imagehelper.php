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
 * @package   block_mbsschooltitle
 * @copyright Andreas Wagner, ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbsschooltitle\local;

/** image for school categories are stored in component "category" filearea "schootitle"
 *  This class handles all the necessary steps for storing and fetching the image.
 */
class imagehelper {

    /*public static $component = 'coursecat';
    public static $filearea = 'description';
    public static $filepath = '/';*/
    
    public static $component = 'block_mbsschooltitle';
    public static $filearea = 'schoollogo';
    public static $filepath = '/';
    
    /** generates the image url with correct filearea
     * 
     * @param int $categoryid
     * @param string $imagename
     * @return string the plugin url to image
     */
    public static function get_imageurl($categoryid, $imagename) {

        if (empty($imagename)) {
            return false;
        }
        
        $catcontext = \context_coursecat::instance($categoryid);

        $url = new \moodle_url("/pluginfile.php/{$catcontext->id}/".self::$component."/".self::$filearea. self::$filepath . $imagename);
        return $url->out();
    }

    /** get the file from file storage
     * 
     * @param int $categoryid
     * @param string $imagename
     * @return boolean|stored_file the file if succeeded ohterwise false
     */
    public static function get_imagefile($categoryid, $imagename) {

        $catcontext = \context_coursecat::instance($categoryid);

        $fs = get_file_storage();
        return $fs->get_file($catcontext->id, self::$component, self::$filearea, 0, self::$filepath, $imagename);
    }

    
    private static function process_picture($context, $component, $filearea,
                                        $itemid, $filepath, $originalfile,
                                        $width, $height) {
        global $CFG;

        require_once($CFG->libdir.'/gdlib.php');

        if (empty($CFG->gdversion)) {
            debuggin('gdlib is required, please check whether global config gdversion is set!');
            return false;
        }

        if (!is_file($originalfile)) {
            return false;
        }

        $imageinfo = GetImageSize($originalfile);

        if (empty($imageinfo)) {
            return false;
        }

        $image = new \stdClass();
        $image->width = $imageinfo[0];
        $image->height = $imageinfo[1];
        $image->type = $imageinfo[2];

        switch ($image->type) {
            case IMAGETYPE_GIF:
                if (function_exists('ImageCreateFromGIF')) {
                    $im = ImageCreateFromGIF($originalfile);
                } else {
                    debugging('GIF not supported on this server');
                    return false;
                }
                break;
            case IMAGETYPE_JPEG:
                if (function_exists('ImageCreateFromJPEG')) {
                    $im = ImageCreateFromJPEG($originalfile);
                } else {
                    debugging('JPEG not supported on this server');
                    return false;
                }
                break;
            case IMAGETYPE_PNG:
                if (function_exists('ImageCreateFromPNG')) {
                    $im = ImageCreateFromPNG($originalfile);
                } else {
                    debugging('PNG not supported on this server');
                    return false;
                }
                break;
            default:
                return false;
        }

        if (function_exists('ImagePng')) {
            $imagefnc = 'ImagePng';
            $imageext = '.png';
            $filters = PNG_NO_FILTER;
            $quality = 1;
        } else if (function_exists('ImageJpeg')) {
            $imagefnc = 'ImageJpeg';
            $imageext = '.jpg';
            $filters = null; // not used
            $quality = 90;
        } else {
            debugging('Jpeg and png not supported on this server, please fix server configuration');
            return false;
        }

        //falls das Bild zu groß ist skalierungsfaktor berechnen
        $faktor = 1;
        if ($image->height > $height)
            $faktor = $height / $image->height;
        $newwidth = round($image->width * $faktor);

        if (function_exists('ImageCreateTrueColor') and $CFG->gdversion >= 2) {
            $im1 = ImageCreateTrueColor($newwidth, $height);

            if ($image->type == IMAGETYPE_PNG and $imagefnc === 'ImagePng') {
                imagealphablending($im1, false);
                $color = imagecolorallocatealpha($im1, 0, 0, 0, 127);
                imagefill($im1, 0, 0, $color);
                imagesavealpha($im1, true);
            }
        } else {
            $im1 = ImageCreate($newwidth, $height);
        }

        ImageCopyBicubic($im1, $im, 0, 0, 0, 0, $newwidth, $height, $image->width, $image->height);

        $fs = get_file_storage();

        $icon = array('contextid' => $context->id, 'component' => $component, 'filearea' => $filearea, 'itemid' => $itemid, 'filepath' => $filepath);

        ob_start();
        if (!$imagefnc($im1, NULL, $quality, $filters)) {
            // keep old icons
            ob_end_clean();
            return false;
        }
        $data = ob_get_clean();
        ImageDestroy($im1);
        $icon['filename'] = 'background' . $imageext;

        $fs->delete_area_files($context->id, $component, $filearea, $itemid);

        $fs->create_file_from_string($icon, $data);

        return $icon;
    }

    
    /** update the picture after submitting the form 
     * 
     * @global object $DB
     * @param record $form the submitted data
     * @param  $data
     * @param int $categoryid
     * @return boolean true if succeded
     */
    public static function update_picture($form, $data, $categoryid) {
        global $DB;

        $picturetouse = null;
        
        $context = \context_coursecat::instance($categoryid, MUST_EXIST);

        if (!empty($data->deletepicture)) {

            $fs = get_file_storage();
            $fs->delete_area_files($context->id, self::$component, self::$filearea, 0);
            $picturetouse = '';
            
        } else {

            $config = get_config('block_mbsschooltitle');
            
            $originalfile = $form->save_temp_file('imagefile');
            if (!$image = self::process_picture($context, self::$component, self::$filearea, 0, self::$filepath, $originalfile, $config->imgwidth, $config->imgheight)) {
                return false;
            }
            $picturetouse = $image['filename'];
        }

        if (!is_null($picturetouse)) {
            $DB->set_field('block_mbsschooltitle', 'image', $picturetouse, array('categoryid' => $categoryid));
            return true;
        }
        return false;
    }

}