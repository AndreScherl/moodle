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
 * This class holds a static call to get our mebis license semantics info.
 * The static class function getCopyrightSemantics() is called via Hack in the files:
 * - /mod/hvp/library/h5p.classes.php:3001
 * - /mod/hvp/locallib.php:194
 *
 * @package   local_mbslicenseinfo
 * @copyright 2016, ISB Bayern
 * @author    Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mbslicenseinfo\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot."/mod/hvp/library/h5p.classes.php");

class mbshvpcontentvalidator {

    public static function getCopyrightSemantics($h5pf) {
        static $semantics;
        
        if ($semantics === NULL) {
          $semantics = (object) array(
            'name' => 'copyright',
            'type' => 'group',
            'label' => $h5pf->t('Copyright information'),
            'fields' => array(
              (object) array(
                'name' => 'title',
                'type' => 'text',
                'label' => $h5pf->t('Title'),
                'placeholder' => 'La Gioconda',
                'optional' => TRUE
              ),
              (object) array(
                'name' => 'source',
                'type' => 'text',
                'label' => $h5pf->t('Source'),
                'placeholder' => 'http://en.wikipedia.org/wiki/Mona_Lisa',
                'optional' => true,
                'regexp' => (object) array(
                  'pattern' => '^http[s]?://.+',
                  'modifiers' => 'i'
                )
              ),
              (object) array(
                'name' => 'author',
                'type' => 'text',
                'label' => $h5pf->t('Author'),
                'placeholder' => 'Leonardo da Vinci',
                'optional' => TRUE
              ),
              (object) array(
                'name' => 'license',
                'type' => 'select',
                'label' => $h5pf->t('License'),
                'default' => 'U',
                'options' => self::build_license_array($h5pf)
              )
            )
          );
        }
        return $semantics;
    }
    
    public static function build_license_array($h5pf) {
        error_log('überschriebene methode');
        $licensearray = array();
        $licensearray[] = (object) array(
            'value' => 'U',
            'label' => $h5pf->t('Undisclosed')
        );
        $recordsarray = \local_mbs\local\licensemanager::get_licenses();
        foreach ($recordsarray as $record) {
            $licensearray[] = (object) array(
                'value' => $record->shortname,
                'label' => $record->fullname
            );
        }
        return $licensearray;
    }
}