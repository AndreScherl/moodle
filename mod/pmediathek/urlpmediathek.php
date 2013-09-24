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
 * Adjusted URL form element that only displays the Prüfungsarchiv activity
 *
 * @package   mod_pmediathek
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/form/url.php');

class MoodleQuickForm_urlpmediathek extends MoodleQuickForm_url {

    function MoodleQuickForm_urlpmediathek($elementName = null, $elementLabel = null, $attributes = null, $options = null) {
        parent::MoodleQuickForm_url($elementName, $elementLabel, $attributes, $options);
    }

    function toHtml(){
        global $CFG, $COURSE, $USER, $PAGE, $OUTPUT;

        $id     = $this->_attributes['id'];
        $elname = $this->_attributes['name'];

        if ($this->_hiddenLabel) {
            $this->_generateId();
            $str = '<label class="accesshide" for="'.$this->getAttribute('id').'" >'.
                $this->getLabel().'</label>'.parent::toHtml();
        } else {
            $str = HTML_QuickForm_text::toHtml();
        }
        if (empty($this->_options['usefilepicker'])) {
            return $str;
        }
        $strsaved = get_string('filesaved', 'repository');
        $straddlink = get_string('choosealink', 'repository');
        if ($COURSE->id == SITEID) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($COURSE->id);
        }
        $client_id = uniqid();

        $str .= <<<EOD
<button id="filepicker-button-{$client_id}" style="display:none">
$straddlink
</button>
EOD;
        $args = new stdClass();
        $args->accepted_types = '*';
        $args->return_types = FILE_EXTERNAL;
        $args->context = $PAGE->context;
        $args->client_id = $client_id;
        $args->env = 'url';

        // SYNERGY LEARNING - this is the only different bit
        global $DB;
        $otherrepos = $DB->get_fieldset_select('repository', 'type', "type <> 'pmediathek'");
        $args->disable_types = $otherrepos;
        // SYNERGY LEARNING - this is the only different bit

        $fp = new file_picker($args);
        $options = $fp->options;

        // print out file picker
        $str .= $OUTPUT->render($fp);

        $module = array('name'=>'form_url', 'fullpath'=>'/lib/form/url.js', 'requires'=>array('core_filepicker'));
        $PAGE->requires->js_init_call('M.form_url.init', array($options), true, $module);
        $PAGE->requires->js_function_call('show_item', array('filepicker-button-'.$client_id));

        return $str;
    }
}

MoodleQuickForm::registerElementType('urlpmediathek', "$CFG->dirroot/mod/pmediathek/urlpmediathek.php", 'MoodleQuickForm_urlpmediathek');
