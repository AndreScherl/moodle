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
 * mebis my courses block (based on course overview block)
 *
 * @package    block_mbsmycourses
 * @copyright  2015 Andreas Wagner <andreas.wagener@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $url = new moodle_url('/admin/settings.php', array('section' => 'themesettings'));
    $link = html_writer::link($url, new lang_string('changeallowusertheme', 'block_mbschangetheme'), array('target' => '_blank'));

    if (empty($CFG->allowuserthemes)) {

        $settings->add(new admin_setting_heading('block_mbschangetheme/requireallowusertheme', '',
                        new lang_string('requireallowusertheme', 'block_mbschangetheme', $link)));
    } else {

        $choices = array();
        $choices[''] = get_string('default');
        $themes = get_list_of_themes();

        foreach ($themes as $key => $theme) {
            $choices[$key] = get_string('pluginname', 'theme_' . $theme->name);
        }

        $settings->add(new admin_setting_configselect('block_mbschangetheme/theme1',
                        new lang_string('theme1', 'block_mbschangetheme'),
                        new lang_string('theme1desc', 'block_mbschangetheme', $link), 'mebis', $choices));

        $settings->add(new admin_setting_configselect('block_mbschangetheme/theme2',
                        new lang_string('theme2', 'block_mbschangetheme'),
                        new lang_string('theme2desc', 'block_mbschangetheme'), 'dlb', $choices));
    }
}