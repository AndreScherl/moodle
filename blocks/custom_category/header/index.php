<?php
/*
 #########################################################################
 #                       DLB-Bayern
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 #
 # Copyright 2012 Andreas Wagner. All Rights Reserved.
 # This file may not be redistributed in whole or significant part.
 # Content of this file is Protected By International Copyright Laws.
 #
 # ~~~~~~~~~~~~~~~~~~ THIS CODE IS NOT FREE SOFTWARE ~~~~~~~~~~~~~~~~~~~~
 #
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 # @author Andreas Wagner, DLB	andreas.wagner@alp.dillingen.de
 #########################################################################
*/

require_once('../../../config.php');
require_once($CFG->dirroot.'/lib/formslib.php');

class editheader_form extends moodleform {

    /** definiert das Formular zur Kursbereichsbearbeitung
     *
     * @global object $CFG
     * @global int $categoryid, die ID des aktuell bearbeiteten Kursbereichs
     */
    function definition() {
        global $CFG, $categoryid;

        $mform =& $this->_form;

        $mform->addElement('header', '', get_string('cat_attributes','block_custom_category'));

        //Überschrift
        $mform->addElement('text', 'headline', get_string('cat_headline', 'block_custom_category'), "size=70");

        if (has_capability('block/custom_category:editheaderimage', context_coursecat::instance($categoryid))) {
            //Bild
            $mform->addElement('static', 'currentpicture', get_string('currentpicture'));

            $mform->addElement('checkbox', 'deletepicture', get_string('delete'));
            $mform->setDefault('deletepicture', 0);

            $mform->addElement('filepicker', 'imagefile', get_string('newpicture'), '', array('maxbytes'=>get_max_upload_file_size($CFG->maxbytes)));
            $mform->addHelpButton('imagefile', 'newpicture');
        }

        //Kategorieid
        $mform->addElement('hidden', 'categoryid', $categoryid);

        //Buttons
        $buttonarray=array();
        $buttonarray[] = $mform->createElement('submit', 'save', get_string('submit'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /** ermittelt das Vorschaubild für das Formular und entfernt das Formularfeld zum Entfernen
     * des Bildes, falls kein Bild vorhanden ist.
     *
     * @global object $CFG
     * @global object $DB
     * @global int $categoryid, die ID des aktuell bearbeiteten Kursbereichs
     */
    function definition_after_data() {
        global $CFG, $DB, $categoryid;
        
        if (empty($CFG->gdversion)) return;
        if (!has_capability('block/custom_category:editheaderimage', context_coursecat::instance($categoryid))) return;

        $mform =& $this->_form;

        $catdata = false;

        if ($categoryid = $mform->getElementValue('categoryid')) {

            $catdata = $DB->get_record('block_custom_category', array('categoryid'=>$categoryid));
        }

        $hasuploadedpicture = false;

        if ($catdata) {

            $context = get_context_instance(CONTEXT_COURSECAT, $catdata->categoryid, MUST_EXIST);

            $fs = get_file_storage();
            $hasuploadedpicture = $fs->file_exists($context->id, 'coursecat', 'description', 0, '/header/', $catdata->background_image);
            $file = $fs->get_file($context->id, 'coursecat', 'description', 0, '/header/', $catdata->background_image);

            if (!empty($catdata->background_image) && $hasuploadedpicture) {

                $src = "{$CFG->wwwroot}/pluginfile.php/{$file->get_contextid()}/coursecat/description/header/".$file->get_filename();
                $alt = get_string('imagepreview', 'block_custom_category');
                $attributes = array('src'=>$src, 'alt'=>$alt, 'title'=>$alt);
                $imagevalue = html_writer::empty_tag('img', $attributes);

            } else {
                $imagevalue = get_string('none');
            }
        } else {
            $imagevalue = get_string('none');
        }

        $imageelement = $mform->getElement('currentpicture');
        $imageelement->setValue($imagevalue);

        if ($mform->elementExists('deletepicture') && !$hasuploadedpicture) {
            $mform->removeElement('deletepicture');
        }
    }

    /** beschneidet und speichert ein hochgeladenes Bild im angegebenen Dateibereich von Moodle
     * @access private
     * @global object $CFG
     * @param object $context
     * @param String $component, die zugeordnete Komponente (coursecat)
     * @param String $filearea, der zugeordnete Dateibereich (description)
     * @param int $itemid,
     * @param String $filepath, der Dateipfad (/header/)
     * @param object $originalfile, das hochgeladene File
     * @param int $width, Breite des beschnittenen Bildes
     * @param int $height, Höhe des beschnittenen Bildes
     * @return Array, Informationen zum hinterlegten Bild
     */
    function _process_new_picture($context, $component, $filearea, $itemid, $filepath, $originalfile, $width, $height) {
        global $CFG;

        require_once("$CFG->libdir/gdlib.php");

        if (empty($CFG->gdversion)) {
            return false;
        }

        if (!is_file($originalfile)) {
            return false;
        }

        $imageinfo = GetImageSize($originalfile);

        if (empty($imageinfo)) {
            return false;
        }

        $image = new stdClass();
        $image->width  = $imageinfo[0];
        $image->height = $imageinfo[1];
        $image->type   = $imageinfo[2];

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
        if ($image->height > $height) $faktor = $height/$image->height;
        $newwidth = round($image->width * $faktor);

        if (function_exists('ImageCreateTrueColor') and $CFG->gdversion >= 2) {
            $im1 = ImageCreateTrueColor($newwidth,$height);

            if ($image->type == IMAGETYPE_PNG and $imagefnc === 'ImagePng') {
                imagealphablending($im1, false);
                $color = imagecolorallocatealpha($im1, 0, 0,  0, 127);
                imagefill($im1, 0, 0,  $color);
                imagesavealpha($im1, true);
            }
        } else {
            $im1 = ImageCreate($newwidth,$height);
        }

        ImageCopyBicubic($im1, $im, 0, 0, 0, 0, $newwidth, $height, $image->width, $image->height);

        $fs = get_file_storage();

        $icon = array('contextid'=>$context->id, 'component'=>$component, 'filearea'=>$filearea, 'itemid'=>$itemid, 'filepath'=>$filepath);

        ob_start();
        if (!$imagefnc($im1, NULL, $quality, $filters)) {
            // keep old icons
            ob_end_clean();
            return false;
        }
        $data = ob_get_clean();
        ImageDestroy($im1);
        $icon['filename'] = 'background'.$imageext;

        $fs->delete_area_files($context->id, $component, $filearea, $itemid, "/header/");

        $fs->create_file_from_string($icon, $data);

        return $icon;
    }

    /** löscht oder fügt ein ausgewähltes Hintergrundbild zum Kursbereich hinzu
     *
     * @global object $CFG
     * @global object $DB
     * @param object $data, die Formulardaten
     * @param int $categoryid, die aktuell bearbeitete Kategorie
     * @return true, falls ein Bild hinzugefügt wurde.
     */
    function update_picture($data, $categoryid) {
        global $CFG, $DB;

        $context = get_context_instance(CONTEXT_COURSECAT, $categoryid, MUST_EXIST);

        if (!has_capability('block/custom_category:editheaderimage', $context)) return;

        $picturetouse = null;

        if (!empty($data->deletepicture)) {
            //vorhandenes Bild löschen
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, 'coursecat', 'description', 0, "/header/");

            $picturetouse = '';

        } else {
            //temporäre Datei erzeugen
            $originalfile = $this->save_temp_file('imagefile');
            //auf gültiges Imageformat prüfen und skalieren.
            $image = $this->_process_new_picture($context, 'coursecat', 'description', 0, "/header/",
                    $originalfile, $CFG->custom_header_imgwidth, $CFG->custom_header_imgheight);

            $picturetouse = $image['filename'];
        }
        //Datenbank aktualisieren.
        if (!is_null($picturetouse)) {
            $DB->set_field('block_custom_category', 'background_image', $picturetouse, array('categoryid' => $categoryid));
            return true;
        }
        return false;
    }
}

//+++ Hauptprogramm ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

//Kategorie ID ist notwendig und gültig?
$categoryid = required_param('categoryid', PARAM_INT);

if (!$category = $DB->get_record('course_categories', array('id'=> $categoryid))) {
    print_error('invalidcategoryid');
}

//HTTPS-Verbindung prüfen
$PAGE->https_required();

//User eingeloggt?
require_login();

//mit entsprechenden Rechten
require_capability('block/custom_category:editheader', context_coursecat::instance($categoryid));

//Pageobjekt vorbereiten
$PAGE->set_url('/blocks/custom_category/header/index.php', array('id'=>$categoryid));
$PAGE->set_context(get_system_context());
$PAGE->set_heading($category->name.": ".get_string('editheader', 'block_custom_category'));
$PAGE->set_pagelayout('coursecategory');

//Bearbeitungsformular
$mform = new editheader_form ('index.php', array());

//Voreinstellungen laden...
$custom_catdata = $DB->get_record('block_custom_category', array('categoryid' => $categoryid));
if ($custom_catdata) $mform->set_data($custom_catdata);

if ($mform->is_cancelled()) {

    redirect($CFG->wwwroot."/course/category.php?id=".$categoryid);

} else {

    if ($data = $mform->get_data() and confirm_sesskey()) {

        //alte Daten holen
        $catdata = $DB->get_record('block_custom_category', array('categoryid' => $categoryid));

        if ($catdata) {//Update

            $catdata->timemodified = time();
            $catdata->headline = $data->headline;
            $DB->update_record('block_custom_category', $catdata);

        } else {//Insert
            $data->timemodified = time();
            $DB->insert_record('block_custom_category', $data);
        }

        if (!empty($CFG->gdversion)) {
            $mform->update_picture($data, $categoryid);
        }

        redirect($CFG->wwwroot."/course/category.php?id=".$categoryid);
    }
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
?>