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
 * Renderer for Block mbschangetheme
 *
 * @package    block_mbschangetheme
 * @copyright  Andreas Wagner <andreas.wagner@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_mbschangetheme_renderer extends plugin_renderer_base {

    public function render_content() {
        global $USER, $PAGE, $OUTPUT;

        $config = get_config('block_mbschangetheme');

        if (!isset($config->theme1)) {
            return html_writer::tag('div', get_string('notconfiguredproperly', 'block_mbschangetheme'));
        }

        $theme1 = $config->theme1;
        $theme2 = $config->theme2;

        $totheme = '';
        if (empty($USER->theme) || $USER->theme == $theme1) {

            $totheme = $theme2;
            $label = get_string('changetotheme2', 'block_mbschangetheme');

        } else {

            $totheme = $theme1;
            $label = get_string('changetotheme1', 'block_mbschangetheme');
        }

        $url = new moodle_url('/blocks/mbschangetheme/changetheme.php', array('theme' => $totheme, 'redirect' => $PAGE->url));

        $button = $OUTPUT->single_button($url, $label);
        return html_writer::tag('div', $button, array('class' => 'change-theme'));
    }

    
    public function render_alert() {
        
        $o = html_writer::tag('h2', get_string('newalertheading', 'block_mbschangetheme'));
        $o .= html_writer::tag('p', get_string('newalertexpl', 'block_mbschangetheme'));
        
        $o .= html_writer::checkbox('newalerthideme', '1', 
                true, get_string('newalerthideme', 'block_mbschangetheme'),
                array('id' => 'newalerthideme', 'class' => ''));
        $b  = html_writer::tag('button', get_string('newalertclose', 'block_mbschangetheme'), array('id' => 'newalertclose'));  
        $o .= html_writer::tag('div', $b);
        return html_writer::tag('div', $o, array('id' => 'newalertoverlay'));
        
    }
}