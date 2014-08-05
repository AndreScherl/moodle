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
 * Instance settings for Prüfungsarchiv activity
 *
 * @package   mod_pmediathek
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/pmediathek/urlpmediathek.php');

class mod_pmediathek_mod_form extends moodleform_mod {
    function definition() {
        global $CFG;
        require_once($CFG->libdir.'/resourcelib.php');

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->add_intro_editor();

        $mform->addElement('header', 'content', get_string('contentheader', 'url'));
        $mform->addElement('urlpmediathek', 'externalurl', get_string('externalurl', 'url'), array('size'=>'60'), array('usefilepicker'=>true));
        $mform->addRule('externalurl', null, 'required', null, 'client');
        $mform->setType('externalurl', PARAM_URL);

        $options = array(
            RESOURCELIB_DISPLAY_POPUP => get_string('resourcedisplaypopup'),
            RESOURCELIB_DISPLAY_EMBED => get_string('resourcedisplayembed'),
        );
        $mform->addElement('select', 'display', get_string('displayselect', 'mod_pmediathek'), $options);
        $mform->setDefault('display', RESOURCELIB_DISPLAY_POPUP);
        $mform->addHelpButton('display', 'displayselect', 'mod_pmediathek');


        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    function validation($data, $files) {
        global $CFG;

        require_once($CFG->dirroot.'/mod/url/locallib.php');
        $errors = parent::validation($data, $files);

        // Validating Entered url, we are looking for obvious problems only,
        // teachers are responsible for testing if it actually works.

        // This is not a security validation!! Teachers are allowed to enter "javascript:alert(666)" for example.

        // NOTE: do not try to explain the difference between URL and URI, people would be only confused...

        if (empty($data['externalurl'])) {
            $errors['externalurl'] = get_string('required');

        } else {
            $url = trim($data['externalurl']);
            if (empty($url)) {
                $errors['externalurl'] = get_string('required');

            } else if (preg_match('|^/|', $url)) {
                // links relative to server root are ok - no validation necessary

            } else if (preg_match('|^[a-z]+://|i', $url) or preg_match('|^https?:|i', $url) or preg_match('|^ftp:|i', $url)) {
                // normal URL
                if (!url_appears_valid_url($url)) {
                    $errors['externalurl'] = get_string('invalidurl', 'url');
                }

            } else if (preg_match('|^[a-z]+:|i', $url)) {
                // general URI such as teamspeak, mailto, etc. - it may or may not work in all browsers,
                // we do not validate these at all, sorry

            } else {
                // invalid URI, we try to fix it by adding 'http://' prefix,
                // relative links are NOT allowed because we display the link on different pages!
                if (!url_appears_valid_url('http://'.$url)) {
                    $errors['externalurl'] = get_string('invalidurl', 'url');
                }
            }

            if (!isset($errors['externalurl'])) {
                require_once($CFG->dirroot.'/repository/pmediathek/mediathekapi.php');
                $api = new repository_pmediathek_api();
                if (!$api->check_embed_url($data['externalurl'])) {
                    $errors['externalurl'] = get_string('notpmediathekurl', 'mod_pmediathek');
                }
            }
        }
        return $errors;
    }
}
