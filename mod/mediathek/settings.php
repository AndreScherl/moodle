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
 * mediathek module admin settings and defaults
 *
 * @package    mod
 * @subpackage mediathek
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
                                                           RESOURCELIB_DISPLAY_EMBED,
                                                           RESOURCELIB_DISPLAY_FRAME,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
                                   RESOURCELIB_DISPLAY_EMBED,
                                   RESOURCELIB_DISPLAY_OPEN,
                                   RESOURCELIB_DISPLAY_POPUP,
                                  );

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('mediathek/framesize',
        get_string('framesize', 'mediathek'), get_string('configframesize', 'mediathek'), 130, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('mediathek/requiremodintro',
        get_string('requiremodintro', 'admin'), get_string('configrequiremodintro', 'admin'), 1));
    $settings->add(new admin_setting_configpasswordunmask('mediathek/secretphrase', get_string('password'),
        get_string('configsecretphrase', 'mediathek'), ''));
    $settings->add(new admin_setting_configcheckbox('mediathek/rolesinparams',
        get_string('rolesinparams', 'mediathek'), get_string('configrolesinparams', 'mediathek'), false));
    $settings->add(new admin_setting_configmultiselect('mediathek/displayoptions',
        get_string('displayoptions', 'mediathek'), get_string('configdisplayoptions', 'mediathek'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('mediathekmodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox_with_advanced('mediathek/printheading',
        get_string('printheading', 'mediathek'), get_string('printheadingexplain', 'mediathek'),
        array('value'=>0, 'adv'=>false)));
    $settings->add(new admin_setting_configcheckbox_with_advanced('mediathek/printintro',
        get_string('printintro', 'mediathek'), get_string('printintroexplain', 'mediathek'),
        array('value'=>1, 'adv'=>false)));
    $settings->add(new admin_setting_configselect_with_advanced('mediathek/display',
        get_string('displayselect', 'mediathek'), get_string('displayselectexplain', 'mediathek'),
        array('value'=>RESOURCELIB_DISPLAY_AUTO, 'adv'=>false), $displayoptions));
    $settings->add(new admin_setting_configtext_with_advanced('mediathek/popupwidth',
        get_string('popupwidth', 'mediathek'), get_string('popupwidthexplain', 'mediathek'),
        array('value'=>620, 'adv'=>true), PARAM_INT, 7));
    $settings->add(new admin_setting_configtext_with_advanced('mediathek/popupheight',
        get_string('popupheight', 'mediathek'), get_string('popupheightexplain', 'mediathek'),
        array('value'=>450, 'adv'=>true), PARAM_INT, 7));
}
